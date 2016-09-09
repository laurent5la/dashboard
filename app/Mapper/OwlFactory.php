<?php

namespace App\Mapper;

use App\Lib\Dashboard\Owl\OwlClient;
use App\Models\Helpers\UserHelper;
use GuzzleHttp\Client;
use Session;
use Config;


/**
 * Class OwlFactory
 *
 * This class is used to create request for the Owl Endpoints
 *
 * @package App\Mapper
 */
class OwlFactory extends OwlClient
{
    private $config;
    private $userToken = '';
    private $client;
    private $logFactory;
    private $logMessage = Array();
    private $helper;
    private $userTokenInHeader = false;
    private $userActivities = Array();

    public function __construct()
    {
        $this->config = app()['config'];
        $this->client = new client();
        $this->client->setBaseUrl(env('OWL_BASE_URL'));
        $this->logFactory = new LogFactory();
        $this->helper = new UserHelper();
    }

    public function retrieveUserToken($email, $password)
    {
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? array() : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 1 - User is logging in with Email - $email");
        $userTokenURL = $this->config->get('owl_endpoints.user_token');
        if(parent::isValidEndpoint($userTokenURL))
        {
            if (filter_var($email, FILTER_VALIDATE_EMAIL))
            {
                $userLoginParams = array(
                    'email' => $email,
                    'password' => $password
                );

                $owlInstance = OwlClient::getInstance();
                $owlPostClient = $owlInstance->owlPostRequest($userTokenURL, $userLoginParams, false);
                Session::set('user_activity', $this->userActivities);
                return $owlPostClient;
            }
            else
            {
                $this->logMessage['OwlFactory->userLogin']['Email'] = 'Invalid Email';
                $this->logFactory->writeErrorLog($this->logMessage);
            }

        }
        else
        {
            $this->logMessage['OwlFactory->userLogin']['Login_Endpoint'] = 'Invalid Login Endpoint';
            $this->logFactory->writeErrorLog($this->logMessage);
            return null;
        }

    }

	public function userLogout($userToken)
	{
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? array() : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 2 - User is logging out with User Token - $userToken");
		$userLogoutURL = $this->config->get('owl_endpoints.user_logout');

		if ($this->isValidEndpoint($userLogoutURL))
		{
            $userTokenAsParam = $this->config->get('module.user_token_as_parameter');
            if($userTokenAsParam == 1)
            {
                $params['user_token'] = $userToken;
                $jsonFormatParams = json_encode($params);
            }
            else
            {
                $jsonFormatParams = '';
            }

            $owlInstance = OwlClient::getInstance();
            $owlPostClient = $owlInstance->owlPostRequest($userLogoutURL, $jsonFormatParams, true);
            Session::set('user_activity', $this->userActivities);
            return $owlPostClient;
        }
		else
		{
			$this->logMessage['OwlFactory->userLogout']['Logout_Endpoint'] = 'Invalid Logout Endpoint';
			$this->logFactory->writeErrorLog($this->logMessage);
            return null;
		}
	}

    public function userRegister($params)
    {
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? array() : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 3 - User is registering with email - ".$params['email']);
        $userRegistrationURL = $this->config->get('owl_endpoints.user_register');
        $userRegistrationValidation = $this->helper->areValidRegisterParams($params);
        if(!empty($userRegistrationValidation))
        {
            $this->logFactory->writeErrorLog($userRegistrationValidation);
        }
        else
        {
            if(parent::isValidEndpoint($userRegistrationURL))
            {
                $userRegisterParams = array(
                    'email' => $params['email'],
                    'first_name' => $params['personal-first-name'],
                    'last_name' => $params['personal-last-name'],
                    'password' => $params['password1'],
                    'phone_number' => $params['personal-phone'],
                    'accepted_tos' => 1,
                    'address_line_1' => $params['personal-address-line1'],
                    'address_line_2' => isset($params['personal-address-line2']) ? $params['personal-address-line2'] : null,
                    'city' => $params['personal-city'],
                    'state_code' => $params['personal-state'],
                    'country_code' => $params['personal-country'],
					'country_name' => $params['personal-country-name'],
                    'postal_code' => $params['personal-zip'],
                    'source' => 'WAC',
                );

                $owlInstance = OwlClient::getInstance();
                $registrationPostRequest = $owlInstance->owlPostRequest($userRegistrationURL, $userRegisterParams);
                Session::set('user_activity', $this->userActivities);
                return $registrationPostRequest;
            }
            else
            {
                $this->logMessage['OwlFactory->userRegister']['Registration_Endpoint'] = 'Invalid Registration Endpoint';
                $this->logFactory->writeErrorLog($this->logMessage);
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
        if(!empty($userPersonalUpdateValidation))
        {
            $this->logFactory->writeErrorLog($userPersonalUpdateValidation);
        }
        else
        {
            if (parent::isValidEndpoint($userPersonalUpdateURL)) {
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

        if($this->isValidEndpoint($validUserTokenURL))
        {
            $params = array(
                'query' => array(
                    'user_token' => $userToken
                )
            );

            $owlInstance = OwlClient::getInstance();
            $response = $owlInstance->owlGetRequest($validUserTokenURL, $params);
            $successCode = $response['meta']['code'] == 200;
            return $successCode;
        }
        else
        {
            $this->logMessage['OwlFactory->isUserTokenValid']['Valid_Token'] = 'Invalid User Token Endpoint';
            $this->logFactory->writeErrorLog($this->logMessage);
            return null;
        }

    }

    /**
     * This function will return both Personal and Billing info of the user.
     * @param  string $userToken
     * @return Array userInfo including Personal and Billing Info.
     */

    public function getUserInfo($userToken = null)
    {
        $userToken = ($userToken) ? $userToken : $this->userToken;

        $userPersonalAndBillingInfoURL = $this->config->get('owl_endpoints.user_billing_info');

        if ($this->isUserTokenValid($userToken)) {
            $params = array(
                'query' => array(
                    'user_token' => $userToken
                )
            );

            if (parent::isValidEndpoint($userPersonalAndBillingInfoURL))
            {
                $owlInstance = OwlClient::getInstance();
                $userPersonalAndBillingResponse = $owlInstance->owlGetRequest($userPersonalAndBillingInfoURL, $params, $userToken);
                $userResponse = array();

                switch ($userPersonalAndBillingResponse['meta']['code']) {
                    case '200':

                        if (!empty($userPersonalAndBillingResponse['response']['user']['user_identifier'])) {
                            $userResponse['user']['Personal_Information'] = $userPersonalAndBillingResponse['response']['user'];
                            $userResponse['user']['Billing_Information']['response']['credit_card_details'] = $userPersonalAndBillingResponse['response']['credit_card_details'];
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
                        if (isset($userBillingResponse['error'][0]))
                        {
                            //try one more time
                            $userPersonalAndBillingResponse = $owlInstance->owlGetRequest($userPersonalAndBillingInfoURL, $params, $userToken);
                            if($userPersonalAndBillingResponse['meta']['code'] != '200') {
                                $userResponse['user']['Personal_Information'] = '';
                                $userResponse['user']['Billing_Information'] = '';
                            }
                            else {
                                $userResponse['user']['Billing_Information']['response']['credit_card_details'] = $userPersonalAndBillingResponse['response']['credit_card_details'];
                            }
                        }
                        break;
                    default:
                        $userResponse['user']['Personal_Information'] = '';
                        $userResponse['user']['Billing_Information'] = '';
                        break;
                }
                return $userResponse;

            }
            else
            {
                $this->logMessage['OwlFactory->getUserInfo']['User_Information'] = 'Invalid User Information Endpoint';
                $this->logFactory->writeErrorLog($this->logMessage);
                return null;
            }
        }
        return null;
    }

    /**
     * This function will return both Personal and Billing info of the user.
     * @param  Array $resetPasswordInput email, campaign_folder, campaign_name, password_reset_url
     * @return Array $resetPasswordResponse returns response from OWL call for Reset Password
     * @author gmathur
     */
    public function sendResetPasswordEmail($resetPasswordInput)
    {
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? array() : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 5 - User is trying to reset the password with email - ".$resetPasswordInput['email']);
        $resetPasswordURL = $this->config->get('owl_endpoints.user_password_reset');
        if(parent::isValidEndpoint($resetPasswordURL))
        {
            $params = array(
                'email'             => $resetPasswordInput['email'],
                'campaign_folder'   => $resetPasswordInput['campaign_folder'],
                'campaign_name'     => $resetPasswordInput['campaign_name'],
                'password_reset_url'=> $resetPasswordInput['password_reset_url']
            );

            $this->logMessage["OwlFactory->sendResetPasswordEmail"] = $params;
            $this->logFactory->writeInfoLog($this->logMessage);

            $owlInstance = OwlClient::getInstance();
            $resetPasswordResponse = $owlInstance->owlPostRequest($resetPasswordURL, $params, false);
            Session::set('user_activity', $this->userActivities);
            return $resetPasswordResponse;
        }

        else
        {
            $this->logMessage['OwlFactory->sendResetPasswordEmail']['Reset_Password_Endpoint'] = 'Invalid Reset Password Endpoint';
            $this->logFactory->writeErrorLog($this->logMessage);
            return null;
        }

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
        $resetPasswordURL = $this->config->get('owl_endpoints.user_password_change');
        if(parent::isValidEndpoint($resetPasswordURL))
        {
			$userHelper = new UserHelper();
			$userToken = $userHelper->getUserTokenFromSession();
            array_push($this->userActivities, "User is trying to update the password with User Token - ".$userToken);
            $params = array(
                'old_password' => $passwords['old_password'],
                'new_password' => $passwords['new_password'],
				'user_token' => $userToken
            );

            $owlInstance = OwlClient::getInstance();
            $changePasswordResponse = $owlInstance->owlPostRequest($resetPasswordURL, $params, true);
            Session::set('user_activity', $this->userActivities);
            return $changePasswordResponse;
        }

        else
        {
            $this->logMessage['OwlFactory->userLogin']['Change_Password_Endpoint'] = 'Invalid Change Password Endpoint';
            $this->logFactory->writeErrorLog($this->logMessage);
            return null;
        }

    }

    public function getProductDetails($productIds)
    {
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? array() : $userSession;

        //This might need to be added to owl endpoints config
        $productDetailsURL = $this->config->get('cart_endpoints.product_details');
        $productIdsString = implode(",", $productIds);
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 7 - User is adding a product with product_id - ".$productIdsString." to the cart");
        if(parent::isValidEndpoint($productDetailsURL))
        {
            $params = array(
                'query' => array(
                    'productId' => $productIdsString
                )
            );

            $owlInstance = OwlClient::getInstance();
            $response = $owlInstance->owlGetRequest($productDetailsURL,$params);
            Session::set('user_activity', $this->userActivities);
            return $response;
        }
        else
        {
            $this->logMessage['OwlFactory->getProductDetails']['Product_Details'] = 'Invalid Product Details Endpoint';
            $this->logFactory->writeErrorLog($this->logMessage);
            return null;
        }

    }

    public function getCompanyName($dunsNumber)
    {
        $companyNameURL = $this->config->get('owl_endpoints.company_details');

        if(parent::isValidEndpoint($companyNameURL))
        {
            $params = array(
                'query' => array(
                    'duns' => $dunsNumber
                )
            );

            $owlInstance = OwlClient::getInstance();
            $response = $owlInstance->owlGetRequest($companyNameURL,$params);

            return $response;
        }
        else
        {
            $this->logMessage['OwlFactory->getCompanyName']['Company_Details'] = 'Invalid Company Details Endpoint';
            $this->logFactory->writeErrorLog($this->logMessage);
            return null;
        }

    }
    public function getCouponDetails($couponCodeParams)
    {
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? array() : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 8 - User is trying to apply a coupon for product/s with product_id/s - ".$couponCodeParams['productId']. " and coupon code - ".$couponCodeParams["promoCode"]);
        $couponDetailsURL = $this->config->get('cart_endpoints.coupon_details');

        if(parent::isValidEndpoint($couponDetailsURL))
        {
            $params = array(
                'query' => array(
                    'productId' => $couponCodeParams['productId'],
                    'promoCode' => $couponCodeParams['promoCode'],
                )
            );

            $owlInstance = OwlClient::getInstance();
            $response = $owlInstance->owlGetRequest($couponDetailsURL, $params);
            Session::set('user_activity', $this->userActivities);
            return $response;
        }
        else
        {
            $this->logMessage['OwlFactory->getCouponDetails']['Coupon_Details'] = 'Invalid Coupon Details Endpoint';
            $this->logFactory->writeErrorLog($this->logMessage);
            return null;
        }

    }

    public function postPFS($cart)
    {
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? array() : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 9 - User is trying to place an order - PFS call");
        $pfsURL = $this->config->get('cart_endpoints.pfs');

        if(parent::isValidEndpoint($pfsURL))
        {
            $owlInstance = OwlClient::getInstance();
            $response = $owlInstance->owlPostRequest($pfsURL, $cart, true);
            Session::set('user_activity', $this->userActivities);
            return $response;
        }
        else
        {
            $this->logMessage['OwlFactory->postPFS']['PFS'] = 'Invalid PFS Endpoint';
            $this->logFactory->writeErrorLog($this->logMessage);
            return null;
        }

    }

    /**
     * This function will return the user entitlement response from phoenix.
     * @param $freeProductparams
     * @return array $response returns response from OWL call for getting user entitlement
     * @author kparakh
     */
    public function postEntitlement($freeProductparams)
    {
        if(is_null(Session::get('user_activity')))
            Session::set('user_activity', []);
        $userSession = Session::get('user_activity');
        $this->userActivities = empty($userSession) ? array() : $userSession;
        array_push($this->userActivities, date("Y-m-d H:i:s")." Action 9 - User is trying to place an order for a free product");
        $userEntitlementURL = $this->config->get('cart_endpoints.user_entitlements');

        if(parent::isValidEndpoint($userEntitlementURL))
        {
            $owlInstance = OwlClient::getInstance();
            $response = $owlInstance->owlPostRequest($userEntitlementURL, $freeProductparams, true);
            Session::set('user_activity', $this->userActivities);
            return $response;
        }
        else
        {
            $this->logMessage['OwlFactory->postEntitlement']['User Entitlement'] = 'Invalid User Entitlement Endpoint';
            $this->logFactory->writeErrorLog($this->logMessage);
            return null;
        }
    }

    /**
     * This function will return the product price key from phoenix.
     * @param $productPriceID
     * @return array $response returns response from OWL call for getting product price key for contentful
     * @author Jbabu
     */
    public function getProductPriceKeyByProductSlug($productPriceID)
    {
        $params = array(
            'query' => array(
                'productSlug' => $productPriceID
            )
        );
        //@TODO enable this once the endpoint is ready
//        $owlInstance = OwlClient::getInstance();
//        $response = $owlInstance->owlGetRequest($productSlugURL,$params);

        $response = Config::get('product_slug');
        return $response;
    }
    /**
     * This function will return the product details from phoenix.
     * @param $productPriceKey
     * @return array $response returns response from OWL call for getting product price details
     * @author Jbabu
     */
    public function getProductDetailsByProductPriceKey($productPriceKey)
    {
        $params = array(
            'query' => array(
                'productSlug' => $productPriceKey
            )
        );
        //@TODO enable this once the endpoint is ready
//        $owlInstance = OwlClient::getInstance();
//        $response = $owlInstance->owlGetRequest($productDetailsURL,$params);
        $response = Config::get('getProductDetailsBySlug');
        return $response;
    }
}