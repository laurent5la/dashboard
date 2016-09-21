<?php
namespace App\Factory;

//TODO: Remove references to objects in the cart project we don't need anymore. i.e. App/Models/ShoppingCart

use App\Lib\Dashboard\Helper\CrossCookie;
use App\Models\User;
use App\Traits\Logging;
use Illuminate\Support\Facades\Session;
use App\Models\Helpers\UserHelper;
use JWTFactory;
use JWTAuth;

class UserObjectFactory
{
    use Logging;

    private $owlFactory;
    private $crossCookie;
    private $userModel;
    private $logMessage = array();

    public function __construct($crossCookie = null)
    {
        $this->owlFactory = new OwlFactory();
        if(empty($crossCookie)) {
            $this->crossCookie = new CrossCookie();
        }
        else {
            $this->crossCookie = $crossCookie;
        }
        $this->userModel = new User();
        $this->config = app()['config'];
    }



    /**
     * This function call OWL's Logout Endpoint passing user token which is stored in Session.
     * @return array finalLogoutResponse
     */

    public function logoutUser($crossCookieMock = null)
    {
        $userHelper = new UserHelper();
        $finalLogoutResponse = [];
        //Once the login flow for storing user token in session is complete, getUserTokenFromSession can be worked on
        $userToken = $userHelper->getUserTokenFromSession();

        if(strlen($userToken)!=0) {
            $logoutOwlResponse = $this->owlFactory->userLogout($userToken);
            if(isset($logoutOwlResponse['meta']['code'])) {
                switch($logoutOwlResponse['meta']['code'])
                {
                    case '200':
                    {
                        if($logoutOwlResponse['response']['logout']==1) {
                            $finalLogoutResponse['status'] = $this->config->get('Enums.Status.SUCCESS');
                            $finalLogoutResponse['error_code'] = '';
                            $finalLogoutResponse['error_message'] = '';
                        } else {
                            $this->logMessage['UserObjectFactory->logoutUser']['Errors'] = isset($logoutOwlResponse['error'][0]) ? $logoutOwlResponse['error'][0] : "OWL returned ".$logoutOwlResponse['meta']['code'];
                            $this->error($this->logMessage);
                        }
                        break;
                    }
                    case '400':
                    {
                        $activityLog = Array();
                        $activityLog["activity_log"] = Session::get('user_activity');
                        $this->activity($activityLog);
                        break;
                    }

                    case '403':
                    {
                        $this->logMessage['UserObjectFactory->logoutUser']['meta'] = $logoutOwlResponse['meta']['code'];
                        $this->logMessage['UserObjectFactory->logoutUser']['Errors'] = "Invalid User Token";
                        $this->error($this->logMessage);
                        break;
                    }
                    default:
                    {
                        $this->logMessage['UserObjectFactory->logoutUser']['meta'] = $logoutOwlResponse['meta']['code'];
                        $this->logMessage['UserObjectFactory->logoutUser']['Errors'] = isset($logoutOwlResponse['error'][0]) ? $logoutOwlResponse['error'][0] : "OWL returned ".$logoutOwlResponse['meta']['code'];
                        $this->error($this->logMessage);
                        break;
                    }
                }
            } else {
                $this->logMessage['UserObjectFactory->logoutUser']['Errors'] = "Meta code missing from the logout response.";
                $this->error($this->logMessage);
            }

        } else {
            $this->logMessage['UserObjectFactory->logoutUser']['Errors'] = "User Token not found in session. User might already have logged out";
            $this->info($this->logMessage);
        }
        $userHelper->unsetUserSession();

        return json_encode($finalLogoutResponse);
    }


    /**
     * Updates the User's Personal Information
     * @param array $params {
     *     @var string $address_line_1 address line 1 of the user
     *     @var string $address_line_2 address line 2 of the user
     *     @var string $city city of the user
     *     @var string $state_code state code of the user
     *     @var string $country_code country code of the user
     *     @var string $postal_code postal code of the user
     * }
     *
     * @return JSON UserInfoObject $finalUpdateResponse Final User Update Response Object consisting of updated personal and tax information
     * @use App\Mapper\OwlFactory::__construct()
     * @use App\Models\User::__construct()
     * @author Kunal
     */

    public function updateUserPersonalInfo($params)
    {
        $userHelper = new UserHelper();
        $userToken = $userHelper->getUserTokenFromSession();

        $userUpdateInfoObject = $this->owlFactory->userPersonalUpdate($params, $userToken);
        if (isset($userUpdateInfoObject['meta'])) {
            switch ($userUpdateInfoObject['meta']['code']) {
                case '200':
                    $userInfoObject = $this->owlFactory->getUserInfo($userToken);

                    if(isset($userInfoObject['user']['Personal_Information']))
                        $userPersonalInfoObject =  $userInfoObject['user']['Personal_Information'];
                    else
                        $this->logMessage['UserObjectFactory->updateUserInfo']['Personal_Information'] = "Missing Personal Information";

                    $calculatedTaxResponse = $this->prepareTaxRequestResponse($userPersonalInfoObject);
                    $updatedCartResponse = $this->updateCartContentsWithTax($calculatedTaxResponse);
                    $userPersonalInfoObject = $this->formatUserObjectResponse($userPersonalInfoObject);

                    $errorFlag = false;
                    if($userPersonalInfoObject == '' || count($userPersonalInfoObject) == 0)
                    {
                        $finalUpdatedUserResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                        $finalUpdatedUserResponse['error_code'] = 'update_billing_tax';
                        $finalUpdatedUserResponse['error_message'] = '';
                        $errorFlag = true;
                        $this->logMessage['UserObjectFactory->updateUserPersonalInfo']['Personal_Information'] = "Missing Personal Information";
                    }
                    if(strlen($calculatedTaxResponse['response']['TaxRate']) == 0)
                    {
                        $finalUpdatedUserResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                        $finalUpdatedUserResponse['error_code'] = 'update_user_billing';
                        $finalUpdatedUserResponse['error_message'] = '';
                        $errorFlag = true;
                        $this->logMessage['UserObjectFactory->updateUserPersonalInfo']['Tax_Information'] = "Missing Avalara Tax Information";
                    }
                    if($errorFlag == false)
                    {
                        $finalUpdatedUserResponse['status'] = $this->config->get('Enums.Status.SUCCESS');
                        $finalUpdatedUserResponse['error_code'] = '';
                        $finalUpdatedUserResponse['error_message'] = '';
                        $this->info("Personal Update Success");
                    }
                    $finalUpdatedUserResponse['response']['user']['Personal_Information'] = $userPersonalInfoObject;
                    $finalUpdatedUserResponse['response']['user']['Personal_Information']['response']['user_token'] = $userToken;

                    /*
                     * Getting billing info of the user from Session, since it is already set after login.
                     */
                    $billingInfo = Session::has('user.response.user.Billing_Information') ? Session::get('user.response.user.Billing_Information') : array();

                    if(isset($billingInfo) && count($billingInfo)!=0)
                    {
                        $userHelper = new UserHelper();
                        if(isset($billingInfo['response']['credit_card_details']))
                            $finalUpdatedUserResponse['response']['user']['Billing_Information']['response']['credit_card_details'] = $userHelper->getCreditCardInfo($billingInfo['response']['credit_card_details']);
                        else
                            $this->logMessage['UserObjectFactory->updateUserPersonalInfo']['Billing_Information'] = "Missing Billing Information";
                    }

                    $finalUpdatedUserResponse['response']['cart'] = $updatedCartResponse;
                    if($this->logMessage != '')
                        $this->error($this->logMessage);

                    $this->logMessage['UserObjectFactory->updateUserPersonalInfo']['Response'] = $finalUpdatedUserResponse;
                    $this->info($finalUpdatedUserResponse);

                    $updateResponse = $this->setUserInfoSession($finalUpdatedUserResponse);

                    return json_encode($updateResponse);

                    break;

                case '400':
                case '401':
                case '402':
                    $activityLog = Array();
                    $activityLog["activity_log"] = Session::get('user_activity');
                    $this->activity($activityLog);
                    $finalUpdatedUserResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                    $finalUpdatedUserResponse['error_code'] = 'display_message';
                    $finalUpdatedUserResponse['error_message'] = $this->config->get('Enums.Status.MESSAGE');
                    return json_encode($finalUpdatedUserResponse);
                    break;

                case '403':
                    /**
                     * TODO: Use refresh_token if OWL returns Invalid user_token.
                     */

                    $errorMsg = is_string($userUpdateInfoObject['error']) ? $userUpdateInfoObject['error'] : $userUpdateInfoObject['error']['0'];
                    $errorArray = array(
                        'meta_code' => $userUpdateInfoObject['meta']['code'],
                        'response' => array(
                            'message' => $errorMsg,
                        ),
                    );
                    $this->logMessage['UserObjectFactory->updateUserPersonalInfo']['Errors'] = $errorArray;
                    $this->error($errorArray);

                    $finalUpdatedUserResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                    $finalUpdatedUserResponse['error_code'] = 'display_message';
                    $finalUpdatedUserResponse['error_message'] = $this->config->get('Enums.Status.MESSAGE');
                    return json_encode($finalUpdatedUserResponse);
                    break;

                case '404':
                    break;

                default:
                    $errorMsg = $this->config->get('Enums.Status.MESSAGE');
                    $errorArray = array(
                        'meta_code' => 500,
                        'response' => array(
                            'message' => $errorMsg,
                        ),
                    );
                    $this->logMessage['UserObjectFactory->updateUserPersonalInfo']['Errors'] = $errorArray;
                    $this->error($errorArray);
                    return $errorArray;
                    break;
            }
        } else {
            $this->error("Personal Update Failure");
        }
    }

    /**This function will send request for Forgot Password.
     * @param $params : $email, $campaign_folder, $campaign_name, $password_reset_url
     * @return array
     * @author gmathur
     */
    public function createByEmail($params)
    {
        $userHelper = new UserHelper();
        $forgotPasswordErrorArray = $userHelper->validForgotPasswordParams($params);
        if(count($forgotPasswordErrorArray)==0) {
            $forgotPasswordResponse = $this->owlFactory->sendResetPasswordEmail($params);
            $finalForgotPasswordResponse = array();

            if (isset($forgotPasswordResponse['meta'])) {
                switch ($forgotPasswordResponse['meta']['code']) {
                    case '200':
                        if (isset($forgotPasswordResponse['response']['success']) && $forgotPasswordResponse['response']['success'] == 1) {
                            $finalForgotPasswordResponse['status'] = $this->config->get('Enums.Status.SUCCESS');
                            $finalForgotPasswordResponse['error_code'] = '';
                            $finalForgotPasswordResponse['error_message'] = '';
                            $this->info("Reset Password Sent Successfully");
                        } else {
                            $finalForgotPasswordResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                            $finalForgotPasswordResponse['error_code'] = '';
                            $finalForgotPasswordResponse['error_message'] = '';
                            $this->info("Reset Password Failed");
                        }
                        break;
                    case '400':
                        $activityLog = Array();
                        $activityLog["activity_log"] = Session::get('user_activity');
                        $this->activity($activityLog);
                        break;

                    case '403':
                        $finalForgotPasswordResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                        $finalForgotPasswordResponse['error_code'] = 'display_message';
                        $finalForgotPasswordResponse['error_message'] = $forgotPasswordResponse["error"][0];
                        $this->info("Reset Password Failed. No meta code key");
                        break;

                    default:
                        $finalForgotPasswordResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                        $finalForgotPasswordResponse['error_code'] = '';
                        $finalForgotPasswordResponse['error_message'] = '';
                        $this->info("Reset Password Failed. No meta code key");
                        break;
                }
            }
        } else {
            $finalForgotPasswordResponse['status'] = $this->config->get('Enums.Status.FAILURE');
            if(isset($forgotPasswordErrorArray['email'])) {
                $finalForgotPasswordResponse['error_code'] = 'display_error';
                $finalForgotPasswordResponse['error_message'] = $params['email']." is not a valid email address.";
            } else {
                $finalForgotPasswordResponse['error_code'] = '';
                $finalForgotPasswordResponse['error_message'] = '';
            }
            $this->error($forgotPasswordErrorArray);
        }

        return $finalForgotPasswordResponse;
    }

    /**This function will send request for Resetting New Password.
     * @param array $passwords
     * @return array
     * @author gmathur
     */
    public function changePassword($passwords)
    {
        if (Session::has("temporary_password") &&
            strlen(Session::get("temporary_password")) != 0) {
            $passwords["old_password"] = Session::get("temporary_password");
        }

        $changePasswordResponse = $this->owlFactory->changePassword($passwords);

        $finalChangePasswordResponse = array();

        if (isset($changePasswordResponse['meta']['code'])) {
            switch ($changePasswordResponse['meta']['code']) {
                case '200':
                    if(isset($changePasswordResponse['response']['success']) && $changePasswordResponse['response']['success']==1) {
                        if (Session::has("temporary_password"))
                            Session::forget("temporary_password");
                        /** Because of the successful password change
                         *  The user's status_reset flag will be dropped. */
                        if (Session::has("user_status_reset") && Session::get("user_status_reset"))
                            Session::forget("user_status_reset");

                        $finalChangePasswordResponse['status'] = $this->config->get('Enums.Status.SUCCESS');
                        $finalChangePasswordResponse['error_code'] = '';
                        $finalChangePasswordResponse['error_message'] = '';
                        $this->info("Change Password Success");
                    }
                    else
                    {
                        $finalChangePasswordResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                        $finalChangePasswordResponse['error_code'] = '';
                        $finalChangePasswordResponse['error_message'] = '';
                        $this->info("Change Password Failed");
                    }
                    break;
                case '400':
                    $activityLog = Array();
                    $activityLog["activity_log"] = Session::get('user_activity');
                    $this->activity($activityLog);
                    break;

                default:
                    $finalChangePasswordResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                    $finalChangePasswordResponse['error_code'] = '';
                    $finalChangePasswordResponse['error_message'] = '';
                    $this->info("Change Password Failed as ". $changePasswordResponse['error'][0]);
                    break;
            }

        }

        return $finalChangePasswordResponse;
    }

    private function formatUserObjectResponse($userInfoObject)
    {
        $finalUserResponse = Array();
        if(isset($userInfoObject['user_identifier']) || is_null($userInfoObject['user_identifier'])) {
            $this->userModel->setUserId($userInfoObject['user_identifier']);
            $finalUserResponse['response']['user_identifier'] = $this->userModel->getUserId();
        }
        if(isset($userInfoObject['email']) || is_null($userInfoObject['email'])) {
            $this->userModel->setEmail($userInfoObject['email']);
            $finalUserResponse['response']['email'] = $this->userModel->getEmail();
        }
        if(isset($userInfoObject['first_name']) || is_null($userInfoObject['first_name'])) {
            $this->userModel->setFirstName($userInfoObject['first_name']);
            $finalUserResponse['response']['first_name'] = $this->userModel->getFirstName();
        }
        if(isset($userInfoObject['last_name']) || is_null($userInfoObject['last_name'])) {
            $this->userModel->setLastName($userInfoObject['last_name']);
            $finalUserResponse['response']['last_name'] = $this->userModel->getLastName();
        }
        if(isset($userInfoObject['personal_address']['phone_number']) || is_null($userInfoObject['personal_address']['phone_number'])) {
            $this->userModel->setPhoneNumber($userInfoObject['personal_address']['phone_number']);
            $finalUserResponse['response']['phone_number'] = $this->userModel->getPhoneNumber();
        }
        if(isset($userInfoObject['user_status_code']) || is_null($userInfoObject['user_status_code'])) {
            $this->userModel->setStatusCode($userInfoObject['user_status_code']);
            $finalUserResponse['response']['status_code'] = $this->userModel->getStatusCode();
            /** This session is created to keep track of the user's reset status,
             * during their interaction with the cart. **/
            if ($this->userModel->getStatusCode() == "RESET")
                Session::put("user_status_reset", true);
        }
        if(isset($userInfoObject['personal_address']['address_line_1']) || is_null($userInfoObject['personal_address']['address_line_1'])) {
            $this->userModel->setAddressLine1($userInfoObject['personal_address']['address_line_1']);
            $finalUserResponse['response']['address_line_1'] = $this->userModel->getAddressLine1();
        }
        if(isset($userInfoObject['personal_address']['address_line_2']) || is_null($userInfoObject['personal_address']['address_line_2'])) {
            $this->userModel->setAddressLine2($userInfoObject['personal_address']['address_line_2']);
            $finalUserResponse['response']['address_line_2'] = $this->userModel->getAddressLine2();
        }
        if(isset($userInfoObject['personal_address']['city_name']) || is_null($userInfoObject['personal_address']['city_name'])) {
            $this->userModel->setCityName($userInfoObject['personal_address']['city_name']);
            $finalUserResponse['response']['city'] = $this->userModel->getCityName();
        }
        if(isset($userInfoObject['personal_address']['zip_code']) || is_null($userInfoObject['personal_address']['zip_code'])) {
            $this->userModel->setZipCode($userInfoObject['personal_address']['zip_code']);
            $finalUserResponse['response']['zip_code'] = $this->userModel->getZipCode();
        }
        if(isset($userInfoObject['personal_address']['state_code']) || is_null($userInfoObject['personal_address']['state_code'])) {
            $this->userModel->setStateCode($userInfoObject['personal_address']['state_code']);
            $finalUserResponse['response']['state_code'] = $this->userModel->getStateCode();
        }
        if(isset($userInfoObject['personal_address']['state_name']) || is_null($userInfoObject['personal_address']['state_name'])) {
            $this->userModel->setStateName($userInfoObject['personal_address']['state_name']);
            $finalUserResponse['response']['state_name'] = $this->userModel->getStateName();
        }
        if(isset($userInfoObject['personal_address']['country_code']) || is_null($userInfoObject['personal_address']['country_code'])) {
            $this->userModel->setCountryCode($userInfoObject['personal_address']['country_code']);
            $finalUserResponse['response']['country_code'] = $this->userModel->getCountryCode();
        }
        if(isset($userInfoObject['personal_address']['country_name']) || is_null($userInfoObject['personal_address']['country_name'])) {
            $this->userModel->setCountryName($userInfoObject['personal_address']['country_name']);
            $finalUserResponse['response']['country_name'] = $this->userModel->getCountryName();
        }
        return $finalUserResponse;
    }

    /**
     * This function sets Session for User. Also sending Payment Token as PT0, PT1 to front-end.
     * @param array $userInfo : Personal_Information, Billing_Information, Cart.
     * @return array $userInfoResponse
     * @author gmathur
     */
    private function setUserInfoSession($userInfo)
    {
        $userHelper = new UserHelper();

        $userDetail = isset($userInfo['response']['user']) ? $userInfo['response']['user'] : [];

        $userHelper->setUserSession($userDetail);

        $userInfoResponse = [];
        $userInfoResponse['status'] = isset($userInfo['status']) ? $userInfo['status'] : 0;
        $userInfoResponse['error_code'] = isset($userInfo['error_code']) ? $userInfo['error_code'] : "";
        $userInfoResponse['error_message'] = isset($userInfo['error_message']) ? $userInfo['error_message'] : "";
        $userInfoResponse['response']['user'] = isset($userInfo['response']['user']) ? $userInfo['response']['user'] : [];

        return $userInfoResponse;
    }
}