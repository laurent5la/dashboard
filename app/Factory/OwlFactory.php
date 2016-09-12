<?php

namespace App\Factory;

use App\Lib\Dashboard\Owl\OwlClient;
use App\Models\Helpers\UserHelper;
use Session;
use Config;


/**
 * Class OwlFactory
 *
 * This class is used to create request for the Owl Endpoints
 *
 * @package Ecomm\Factory
 */
class OwlFactory extends OwlClient
{
    private $config;
    private $userToken = '';
    private $logFactory;
    private $userActivities = [];

    public function __construct()
    {
        $this->config = app()['config'];
        $this->logFactory = new LogFactory();
    }

    public function retrieveUserToken($email, $password)
    {
        $owlPostClient = null;
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? [] : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 1 - User is logging in with Email - $email");
        $userTokenURL = $this->config->get('owl_endpoints.user_token');
        if($this->isValidEndpoint($userTokenURL))
        {
            if (filter_var($email, FILTER_VALIDATE_EMAIL))
            {
                $userLoginParams = [
                    'email' => $email,
                    'password' => $password
                ];

                $owlInstance = OwlClient::getInstance();
                $owlPostClient = $owlInstance->owlPostRequest($userTokenURL, $userLoginParams, false);
                Session::set('user_activity', $this->userActivities);
            }
            else
            {
                $logMessage['OwlFactory->userLogin']['Email'] = 'Invalid Email';
                $this->logFactory->writeErrorLog($logMessage);
            }

        }
        else
        {
            $logMessage['OwlFactory->userLogin']['Login_Endpoint'] = 'Invalid Login Endpoint';
            $this->logFactory->writeErrorLog($logMessage);
        }
        return $owlPostClient;

    }

	public function userLogout($userToken)
	{
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? [] : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 2 - User is logging out with User Token - $userToken");
		$userLogoutURL = $this->config->get('owl_endpoints.user_logout');

		if ($this->isValidEndpoint($userLogoutURL)) {
            $userTokenAsParam = $this->config->get('module.user_token_as_parameter');
            if($userTokenAsParam == 1) {
                $params['user_token'] = $userToken;
                $jsonFormatParams = json_encode($params);
            } else {
                $jsonFormatParams = '';
            }

            $owlInstance = OwlClient::getInstance();
            $owlPostClient = $owlInstance->owlPostRequest($userLogoutURL, $jsonFormatParams, true);
            Session::set('user_activity', $this->userActivities);
            return $owlPostClient;
        } else {
			$logMessage['OwlFactory->userLogout']['Logout_Endpoint'] = 'Invalid Logout Endpoint';
			$this->logFactory->writeErrorLog($logMessage);
            return null;
		}
	}

    public function userRegister($params)
    {
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);

        $userSession = Session::get('user_activity');
        $userHelper = new UserHelper();
        $this->userActivities = empty($userSession) ? [] : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 3 - User is registering with email - ".$params['email']);
        $userRegistrationURL = $this->config->get('owl_endpoints.user_register');
        $userRegistrationValidation = $userHelper->areValidRegisterParams($params);
        if(!empty($userRegistrationValidation)) {
            $this->logFactory->writeErrorLog($userRegistrationValidation);
        } else {
            if($this->isValidEndpoint($userRegistrationURL)) {
                $userRegisterParams = [
                    'email' => $params['email'],
                    'first_name' => $params['personal-first-name'],
                    'last_name' => $params['personal-last-name'],
                    'password' => $params['password1'],
                    'source' => 'WAC',
                ];

                $owlInstance = OwlClient::getInstance();
                $registrationPostRequest = $owlInstance->owlPostRequest($userRegistrationURL, $userRegisterParams);
                Session::set('user_activity', $this->userActivities);
                return $registrationPostRequest;
            } else {
                $logMessage['OwlFactory->userRegister']['Registration_Endpoint'] = 'Invalid Registration Endpoint';
                $this->logFactory->writeErrorLog($logMessage);
                return null;
            }
        }
    }

    public function userPersonalUpdate($params, $userToken)
    {
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? array() : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 4 - User is updating his personal information.");
        $userPersonalUpdateURL = $this->config->get('owl_endpoints.user_personal_update');
        $userPersonalUpdateValidation = $this->helper->areValidUpdateParams($params);
        if(!empty($userPersonalUpdateValidation)) {
            $this->logFactory->writeErrorLog($userPersonalUpdateValidation);
        } else {
            if ($this->isValidEndpoint($userPersonalUpdateURL)) {
                $personalUpdateParams = array(
                    'user_token' => $userToken,
                    'address_line_1' => $params['new-address-line1'],
                    'address_line_2' => isset($params['new-address-line2']) ? $params['new-address-line2'] : null,
                    'city' => $params['new-city'],
                    'state_code' => $params['new-state'],
                    'country_code' => $params['new-country'],
                    'zip' => $params['new-zip'],
                    'phone_number' => $params['new-phone']
                );

                $owlInstance = OwlClient::getInstance();
                $personalPostRequest = $owlInstance->owlPostRequest($userPersonalUpdateURL, $personalUpdateParams);
                Session::set('user_activity', $this->userActivities);
                return $personalPostRequest;
            } else {
                $this->logMessage['OwlFactory->userPersonalUpdate']['Personal_Update_Endpoint'] = 'Invalid Personal Update Endpoint';
                $this->logFactory->writeErrorLog($this->logMessage);
                return null;
            }
        }
    }

    public function isUserTokenValid($userToken)
    {
        $validUserTokenURL = $this->config->get('owl_endpoints.valid_user_token');
        $response = null;

        if($this->isValidEndpoint($validUserTokenURL)) {
            $params = [
                'query' => [
                    'user_token' => $userToken
                ]
            ];

            $owlInstance = OwlClient::getInstance();
            $response = $owlInstance->owlGetRequest($validUserTokenURL, $params);
        }
        else {
            $logMessage['OwlFactory->isUserTokenValid']['Valid_Token'] = 'Invalid User Token Endpoint';
            $this->logFactory->writeErrorLog($logMessage);
        }

        return $response;
    }

    /**
     * This function will return both Personal and Billing info of the user.
     * @param  string $userToken
     * @return Array userInfo including Personal and Billing Info.
     */
    public function getUserTokenStatus($userToken = null)
    {
        $userToken = ($userToken) ? $userToken : $this->userToken;

        $userDetailURL = $this->config->get('owl_endpoints.valid_user_token');

        if ($this->isUserTokenValid($userToken)) {
            $params = [
                'query' => [
                    'user_token' => $userToken
                ]
            ];

            if ($this->isValidEndpoint($userDetailURL)) {
                $owlInstance = OwlClient::getInstance();
                $userDetailResponse = $owlInstance->owlGetRequest($userDetailURL, $params, $userToken);

                $userResponse = [];

                switch ($userDetailResponse['meta']['code']) {
                    case '200':

                        if (!empty($userDetailResponse['response']['user']['_userIdentifier'])) {
                            $userResponse['user']['user_detail'] = $userDetailResponse['response']['user'];
                        } else {
                            return array('error' => 'MISSING_ID');
                        }
                        break;
                    case '400':
                    case '401':
                    case '402':
                    case '403':
                        $activityLog = Array();
                        $activityLog["activity_log"] = Session::get('user_activity');
                        $this->logFactory->writeActivityLog($activityLog);
                        if (isset($userDetailResponse['error'][0])) {
                            //try one more time
                            $userDetailResponse = $owlInstance->owlGetRequest($userDetailURL, $params, $userToken);
                            if($userDetailResponse['meta']['code'] != '200') {
                                $userResponse['user']['user_detail'] = '';
                            } else {
                                $userResponse['user']['user_detail'] = $userDetailResponse['response']['user'];
                            }
                        }
                        break;
                    default:
                        $userResponse['user']['user_detail'] = '';
                        break;
                }
                return $userResponse;

            } else {
                $logMessage['OwlFactory->getUserInfo']['User_Information'] = 'Invalid User Information Endpoint';
                $this->logFactory->writeErrorLog($logMessage);
                return null;
            }
        }
        return null;
    }

    /**
     * Sends the reset password email through OWL services.
     * @param  Array $resetPasswordInput email, campaign_folder, campaign_name, password_reset_url
     * @return Array $resetPasswordResponse returns response from OWL call for Reset Password
     * @author gmathur
     */
    public function sendResetPasswordEmail($resetPasswordInput)
    {
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);

        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? [] : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 5 - User is trying to reset the password with email - ".$resetPasswordInput['email']);
        $resetPasswordURL = $this->config->get('cart_endpoints.user_password_reset');
        $resetPasswordResponse = [];
        if(parent::isValidEndpoint($resetPasswordURL)) {
            $params = [
                'email'             => $resetPasswordInput['email'],
                'campaign_folder'   => $resetPasswordInput['campaign_folder'],
                'campaign_name'     => $resetPasswordInput['campaign_name'],
                'password_reset_url'=> $resetPasswordInput['password_reset_url']
            ];

            $logMessage["OwlFactory->sendResetPasswordEmail"] = $params;
            $this->logFactory->writeInfoLog($logMessage);

            $owlInstance = OwlClient::getInstance();
            $resetPasswordResponse = $owlInstance->owlPostRequest($resetPasswordURL, $params);
            Session::set('user_activity', $this->userActivities);
        } else {
            $logMessage['OwlFactory->sendResetPasswordEmail']['Reset_Password_Endpoint'] = 'Invalid Reset Password Endpoint';
            $this->logFactory->writeErrorLog($logMessage);
        }
        return $resetPasswordResponse;
    }

    /**
     * This function will return both Personal and Billing info of the user.
     * @param  Array $passwords old_password, new_password
     * @return Array  $changePasswordResponse returns response from OWL call for Changing Password
     * @author gmathur
     */
    public function changePassword($passwords)
    {
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? array() : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 6 - User is trying to change the password");
        $resetPasswordURL = $this->config->get('cart_endpoints.user_password_change');
        $changePasswordResponse = [];
        if(parent::isValidEndpoint($resetPasswordURL)) {
            $userHelper = new UserHelper();
            $userToken = $userHelper->getUserTokenFromSession();
            array_push($this->userActivities, "User is trying to update the password with User Token - ".$userToken);
            $params = array(
                'old_password' => $passwords['old_password'],
                'new_password' => $passwords['new_password'],
                'user_token' => $userToken
            );

            $owlInstance = OwlClient::getInstance();
            $changePasswordResponse = $owlInstance->owlPostRequest($resetPasswordURL, $params);
            Session::set('user_activity', $this->userActivities);
        } else {
            $logMessage['OwlFactory->userLogin']['Change_Password_Endpoint'] = 'Invalid Change Password Endpoint';
            $this->logFactory->writeErrorLog($logMessage);
        }
        return $changePasswordResponse;
    }
}