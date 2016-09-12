<?php

namespace App\Factory;

use App\Lib\Dashboard\Owl\OwlClient;
use App\Models\Helpers\UserHelper;
use App\Mapper\LogFactory;
use App\Lib\Decrypt;
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
        }
        return $owlPostClient;

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
     * This function will return the search results for company.
     * @param  Array $params input parameters company name, state, country, is_your_business
     * @return Array  $response returns response from OWL call for getting company details
     * @author kparakh
     */

    public function getSearchResults($params)
    {
        if($params['country']=='US' || $params['country']=='')
            $searchURL = $this->config->get('owl_endpoints.business_search');
        else
            $searchURL = $this->config->get('owl_endpoints.international_search');

        $query = array(
            'query' => $params
        );
        $owlInstance = OwlClient::getInstance();
        $response = $owlInstance->owlGetRequest($searchURL,$query);
        return $response;
    }




    /**
     * This function will return both Personal and Billing info of the user.
     * @param  string $userToken
     * @return Array userInfo including Personal and Billing Info.
     */

    public function getUserDetail($userToken = null)
    {
        $userToken = ($userToken) ? $userToken : $this->userToken;

        $userDetailURL = $this->config->get('owl_endpoints.user_detail');

        if ($this->isUserTokenValid($userToken)) {
            $params = array(
                'query' => array(
                    'user_token' => $userToken
                )
            );

            if (parent::isValidEndpoint($userDetailURL))
            {
                $owlInstance = OwlClient::getInstance();
                $userDetailResponse = $owlInstance->owlGetRequest($userDetailURL, $params, $userToken);

                $userResponse = array();

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
                        if (isset($userDetailResponse['error'][0]))
                        {
                            //try one more time
                            $userDetailResponse = $owlInstance->owlGetRequest($userDetailURL, $params, $userToken);
                            if($userDetailResponse['meta']['code'] != '200') {
                                $userResponse['user']['user_detail'] = '';
                            }
                            else {
                                $userResponse['user']['user_detail'] = $userDetailResponse['response']['user'];
                            }
                        }
                        break;
                    default:
                        $userResponse['user']['user_detail'] = '';
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
     * This function will return the product details from phoenix.
     * @param  Array $productIds ids of the products for which details are requested
     * @return Array  $response returns response from OWL call for getting product price details
     * @author kparakh
     */

    public function getProductDetails($productIds)
    {
        $productDetailsURL = $this->config->get('owl_endpoints.product_details');
        $productIdsString = '';
        $productIdsArray = array();
        foreach($productIds as $productId) {
            if(isset($productId["corelated_product_id_1"]) && strlen($productId["corelated_product_id_1"]) != 0)
                array_push($productIdsArray, $productId["corelated_product_id_1"]);
            if(isset($productId["corelated_product_id_2"]) && strlen($productId["corelated_product_id_2"]) != 0)
                array_push($productIdsArray, $productId["corelated_product_id_2"]);
            array_push($productIdsArray, $productId["product_id"]);

        }
        $productIdsString = implode(",", $productIdsArray);
        $params = array(
            'query' => array(
                'productId' => $productIdsString
            )
        );

        $owlInstance = OwlClient::getInstance();
        $response = $owlInstance->owlGetRequest($productDetailsURL,$params);

        return $response;
    }

    /**
     * This function will return the product details from phoenix.
     *
     * APP_VERSION is a flag to distinguish between where to get product data. As of now we have 4 versions -
     * APP_VERSION = 0 :- No call to contentful or phoenix. Everything comes from a config.
     * APP_VERSION = 1 :- Call Contetnful for the product details and call config to get the product prices.
     *                    Config files -
     *                      - product_key_coo_16.10 => Mock getProductDetailsBySlug response from owl/phx for COO products
     *                      - product_key_cos_16.10 => Mock getProductDetailsBySlug response from owl/phx for COS products
     * APP_VERSION = 2 :- Call Contetnful for the product details and call config to get the product prices. Here, COO products don't have packs.
     *                    Config files used -
     *                      - product_key_coo_one_pack => Mock getProductDetailsBySlug response from owl/phx for COO products having no packs
     *                      - product_key_cos_16.10 => Mock getProductDetailsBySlug response from owl/phx for COS products
     * APP_VERSION = 3 :- Call Contetnful for the product details and call owl/phx to get the product prices. No Config files required.
     *
     * @param array $productSlugs
     * @param boolean $isCOOPage
     * @return array $response returns response from OWL call for getting product price details
     * @author kparakh
     */

    public function getProductDetailsBySlug($productSlugs, $isCOOPage)
    {
        $productSlugsArray = array();
        $response = array();
        $logMessage = array();
        foreach($productSlugs as $productSlug) {
            array_push($productSlugsArray, $productSlug["main_product"]);
            if(isset($productSlug["co-related_product_1"]) && strlen($productSlug["co-related_product_1"]) != 0)
                array_push($productSlugsArray, $productSlug["co-related_product_1"]);
            if(isset($productSlug["co-related_product_2"]) && strlen($productSlug["co-related_product_2"]) != 0)
                array_push($productSlugsArray, $productSlug["co-related_product_2"]);
        }
        $productDetailsURL = $this->config->get('owl_endpoints.product_details');
        $productSlugsString = implode(",", $productSlugsArray);

        $params = array(
            'query' => array(
                'productSlug' => $productSlugsString
            )
        );
        if(env("APP_VERSION") == 3) {
            $owlInstance = OwlClient::getInstance();
            $response = $owlInstance->owlGetRequest($productDetailsURL,$params);
        } elseif (env("APP_VERSION") == 1 || env("APP_VERSION") == 2) {
            if($isCOOPage) {
                if(file_exists(config_path(). '/product_key_coo_16.10.php'))
                    $response = Config::get('product_key_coo_16.10');
                else {
                    $logMessage["OwlFactory->getProductDetailsBySlug"]["product_key_coo_16.10"] = "File Not Found!";
                    $this->logFactory->writeErrorLog($logMessage);
                }
            } else {
                if(file_exists(config_path(). '/product_key_cos_16.10.php'))
                    $response = Config::get('product_key_cos_16.10');
                else {
                    $logMessage["OwlFactory->getProductDetailsBySlug"]["product_key_cos_16.10"] = "File Not Found!";
                    $this->logFactory->writeErrorLog($logMessage);
                }
            }
        }
        return $response;
    }


    public function postCreditSignalSignUp($inputArr)
    {
        $decryptObj = new Decrypt();
        $dunsLength = 9;
        $params = array();

        if ((isset($inputArr['email']) && strlen($inputArr['email']) != 0) &&
            (isset($inputArr['firstName']) && strlen($inputArr['firstName']) != 0) &&
            (isset($inputArr['lastName']) && strlen($inputArr['lastName']) != 0) &&
            (isset($inputArr['encryptedDuns']) && strlen($inputArr['encryptedDuns']) != 0))
        {
            $params["email"] = filter_var($inputArr['email'], FILTER_SANITIZE_EMAIL);
            $params["first_name"] = preg_replace('/[^A-Za-z]/', '', $inputArr['firstName']);
            $params["last_name"] = preg_replace('/[^A-Za-z]/', '', $inputArr['lastName']);
            $params["duns"] = $decryptObj->decryptData($inputArr['encryptedDuns']);
            $params["accepted_tos"] = "1";

            if (preg_match('/^[0-9]{9}$/', $params["duns"]))
            {
                $CreditSignalSignUpURL = $this->config->get('owl_endpoints.creditsignal_signup');

                $owlInstance = OwlClient::getInstance();
                $response = $owlInstance->owlPostRequest($CreditSignalSignUpURL, $params);
                return $response;
            }
            else
            {
                $errorLog = "Encrypted DUNS did not decrypt into a length of ".$dunsLength;
            }
        }
        else
        {
            $errorLog = "Required parameters are not in the request or are empty";
        }

        if (strlen($errorLog) != 0)
        {
            $logMessage['OwlFactory->creditSignalSignUp']['error'] = $errorLog;
            $logFactoryObject = new LogFactory();
            $logFactoryObject->writeErrorLog($logMessage);
        }
    }
}