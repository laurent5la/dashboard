<?php
namespace App\Mapper;

use App\Lib\ECart\Helper\CrossCookie;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use App\Models\Helpers\UserHelper;

class UserFactory
{
    private $owlFactory;
    private $avalaraFactory;
    private $crossCookie;
    private $userModel;
    private $logMessage = array();
    private $userHelper;

    private function getUserHelper()
    {
        if (is_null($this->userHelper)) {
            $this->userHelper = new UserHelper();
        }
        return $this->userHelper;
    }

    public function __construct($crossCookie = null)
    {
        $this->owlFactory = new OwlFactory();
        $this->avalaraFactory = new AvalaraFactory();
        if(empty($crossCookie)) {
            $this->crossCookie = new CrossCookie();
        } else {
            $this->crossCookie = $crossCookie;
        }
        $this->userModel = new User();
        $this->config = app()['config'];
        $this->logFactory = new LogFactory();
    }

    /**
     * Returns the User Information
     *
     * @param array $params {
     *     @var string $email email of the user
     *     @var string $password password of the user
     * }
     * @return JSON UserInfoObject $finalLoginResponse Final User Response Object consisting of personal, billing and tax information
     * @use App\Mapper\OwlFactory::__construct()
     * @use App\Models\User::__construct()
     * @author Kunal
     */
    public function retrieveUserInfo($params)
    {
        $retrieveUserToken = $this->owlFactory->userLogin($params['email'], $params['password']);

        if ($retrieveUserToken && isset($retrieveUserToken['meta'])) {
            switch ($retrieveUserToken['meta']['code']) {
                case '200':
                    $userToken = $retrieveUserToken['response']['user_token'];
                    $userRefreshToken = $retrieveUserToken['response']['refresh_token'];
                    $this->crossCookie->login($userToken, $userRefreshToken);

                    $userInfo = $this->owlFactory->getUserInfo($userToken);

                    if(isset($userInfo['user']['Personal_Information']['user_status_code']) &&
                        $userInfo['user']['Personal_Information']['user_status_code'] === "RESET") {
                        Session::put("temporary_password", $params['password']);
                    }

                    if(isset($userInfo['user']['Billing_Information'])) {
                        $userBillingInfo = $userInfo['user']['Billing_Information'];
                    } else {
                        $this->logMessage['UserObjectFactory->retrieveUserInfo']['Billing_Information'] = "Missing Billing Information";
                    }

                    if(isset($userInfo['user']['Personal_Information'])) {
                        $userPersonalInfo = $userInfo['user']['Personal_Information'];
                    } else {
                        $this->logMessage['UserObjectFactory->retrieveUserInfo']['Personal_Information'] = "Missing Personal Information";
                    }
                    if (isset($userPersonalInfo)) {
                        $this->prepareAvalaraAddressValidate($userPersonalInfo);
                    }

                    if(isset($userPersonalInfo['user_type_code']) && $userPersonalInfo['user_type_code'] != 'EXT') {
                        $errorMsg = 'You\'re not authorized to access this portal.';
                        $errorArray = array(
                            'meta_code' => 408,
                            'response' => array(
                                'message' => $errorMsg,
                            ),
                        );
                        $this->logMessage['UserObjectFactory->retrieveUserInfo']['User_Type'] = $errorArray;
                        $this->logFactory->writeErrorLog($this->logMessage);
                        return json_encode($errorArray);
                    } elseif(!isset($userPersonalInfo['user_type_code'])) {
                        $this->logMessage['UserObjectFactory->retrieveUserInfo']['User_Type'] = "Missing User Type Code";
                        $this->logFactory->writeErrorLog($this->logMessage);
                    } else {
                        $userPersonalInfo = $this->formatUserObjectResponse($userPersonalInfo);
                        $errorFlag = false;
                        if($userPersonalInfo == '' || count($userPersonalInfo) == 0) {
                            $finalLoginResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                            $finalLoginResponse['error_code'] = 'update_billing_tax';
                            $finalLoginResponse['error_message'] = '';
                            $errorFlag = true;
                            $this->logMessage['UserFactory->retrieveUserInfo']['Personal_Information'] = "Missing Personal Information";
                        }
                        if($errorFlag == false) {
                            $finalLoginResponse['status'] = $this->config->get('Enums.Status.SUCCESS');
                            $finalLoginResponse['error_code'] = '';
                            $finalLoginResponse['error_message'] = '';
                            $this->logFactory->writeInfoLog("Login Success");
                        }
                        $finalLoginResponse['response']['user']['Personal_Information'] = $userPersonalInfo;
                        $finalLoginResponse['response']['user']['Billing_Information'] = $userBillingInfo;
                        $finalLoginResponse['response']['user']['Personal_Information']['response']['user_token'] = $userToken;

                        if($this->logMessage != '') {
                            $this->logFactory->writeErrorLog($this->logMessage);
                        }
                        $this->logMessage['UserFactory->retrieveUserInfo']['Response'] = $finalLoginResponse;
                        $this->logFactory->writeInfoLog($finalLoginResponse);

                        $loginResponse = $this->setUserInfoSession($finalLoginResponse);
                        return json_encode($loginResponse);
                    }
                    break;
                case '400':
                case '401':
                case '402':
                    $activityLog = Array();
                    $activityLog["activity_log"] = Session::get('user_activity');
                    $this->logFactory->writeActivityLog($activityLog);
                    $finalLoginResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                    $finalLoginResponse['error_code'] = 'display_message';
                    $finalLoginResponse['error_message'] = $this->config->get('Enums.Status.MESSAGE');
                    return json_encode($finalLoginResponse);
                    break;
                case '403':
                    $errorMsg = is_string($retrieveUserToken['error']) ? $retrieveUserToken['error'] : $retrieveUserToken['error']['0'];
                    $errorArray = array(
                        'meta_code' => $retrieveUserToken['meta']['code'],
                        'response' => array(
                            'message' => $errorMsg,
                        ),
                    );
                    $this->logMessage['UserObjectFactory->retrieveUserInfo']['Errors'] = $errorArray;
                    $this->logFactory->writeInfoLog($errorArray);

                    return json_encode($errorArray);
                    break;

                case '404':
                    $this->logMessage['UserObjectFactory->retrieveUserInfo']['Errors'] = 'Page Not Found';
                    $this->logFactory->writeErrorLog($this->logMessage);
                    break;

                default:
                    $errorMsg = $this->config->get('Enums.Status.MESSAGE');
                    $errorArray = array(
                        'meta_code' => 500,
                        'response' => array(
                            'message' => $errorMsg,
                        ),
                    );
                    $this->logMessage['UserObjectFactory->retrieveUserInfo']['Errors'] = $errorArray;
                    $this->logFactory->writeErrorLog($errorArray);
                    return $errorArray;
                    break;
            }
        }else{
            $this->logFactory->writeErrorLog("Login Failure");
        }
    }

    /**
     * This function call OWL's Logout Endpoint passing user token which is stored in Session.
     * @return array finalLogoutResponse
     */
    public function logoutUser($crossCookieMock = null)
    {
        $userHelper = $this->getUserHelper();
        $userToken = $userHelper->getUserTokenFromSession();

        if(strlen($userToken)!=0)
        {
            $logoutOwlResponse = $this->owlFactory->userLogout($userToken);
            if(isset($logoutOwlResponse['meta']['code']))
            {
                switch($logoutOwlResponse['meta']['code'])
                {
                    case '200':
                    {
                        if($logoutOwlResponse['response']['logout']==1)
                        {
                            $finalLogoutResponse['status'] = $this->config->get('Enums.Status.SUCCESS');
                            $finalLogoutResponse['error_code'] = '';
                            $finalLogoutResponse['error_message'] = '';
                        }

                        else
                        {
                            $this->logMessage['UserObjectFactory->logoutUser']['Errors'] = isset($logoutOwlResponse['error'][0]) ? $logoutOwlResponse['error'][0] : "OWL returned ".$logoutOwlResponse['meta']['code'];
                            $this->logFactory->writeErrorLog($this->logMessage);
                        }
                        break;
                    }
                    case '400':
                    {
                        $activityLog = Array();
                        $activityLog["activity_log"] = Session::get('user_activity');
                        $this->logFactory->writeActivityLog($activityLog);
                        break;
                    }

                    case '403':
                    {
                        $this->logMessage['UserObjectFactory->logoutUser']['meta'] = $logoutOwlResponse['meta']['code'];
                        $this->logMessage['UserObjectFactory->logoutUser']['Errors'] = "Invalid User Token";
                        $this->logFactory->writeErrorLog($this->logMessage);
                        break;
                    }
                    default:
                    {
                        $this->logMessage['UserObjectFactory->logoutUser']['meta'] = $logoutOwlResponse['meta']['code'];
                        $this->logMessage['UserObjectFactory->logoutUser']['Errors'] = isset($logoutOwlResponse['error'][0]) ? $logoutOwlResponse['error'][0] : "OWL returned ".$logoutOwlResponse['meta']['code'];
                        $this->logFactory->writeErrorLog($this->logMessage);
                        break;
                    }
                }
            }

            else
            {
                $this->logMessage['UserObjectFactory->logoutUser']['Errors'] = "Meta code missing from the logout response.";
                $this->logFactory->writeErrorLog($this->logMessage);
            }

        }

        else
        {
            $this->logMessage['UserObjectFactory->logoutUser']['Errors'] = "User Token not found in session. User might already have logged out";
            $this->logFactory->writeInfoLog($this->logMessage);
        }
        $userHelper->unsetUserSession();
        $shoppingCart = new ShoppingCart();
        $shoppingCart->clearCartSessionAndCookie($crossCookieMock);

        $finalLogoutResponse['status'] = $this->config->get('Enums.Status.SUCCESS');

        return json_encode($finalLogoutResponse);
    }

    /**
     * Stores the User Information
     * @param array $params {
     *     @var string $email email of the user
     *     @var string $first_name first name of the user
     *     @var string $last_name last name of the user
     *     @var string $password password of the user
     *     @var string $address_line_1 address line 1 of the user
     *     @var string $address_line_2 address line 2 of the user
     *     @var string $city city of the user
     *     @var string $state_code state code of the user
     *     @var string $country_code country code of the user
     *     @var string $postal_code postal code of the user
     *     @var string $phone_number phone number of the user
     * }
     *
     * @return JSON UserInfoObject $finalRegisterResponse Final User Response Object consisting of personal, billing and tax information
     * @use App\Mapper\OwlFactory::__construct()
     * @use App\Models\User::__construct()
     * @author Kunal
     */

    public function storeUserInfo($params)
    {
        $retrieveUserToken = $this->owlFactory->userRegister($params);

        if (isset($retrieveUserToken['meta'])) {
            switch ($retrieveUserToken['meta']['code']) {
                case '200':
                    $userToken = $retrieveUserToken['response']['user_token'];
                    $userRefreshToken = $retrieveUserToken['response']['refresh_token'];
                    $this->crossCookie->login($userToken, $userRefreshToken);

                    $userInfoObject = $this->owlFactory->getUserInfo($userToken);

                    if(isset($userInfoObject['user']['Personal_Information']))
                        $userPersonalInfoObject =  $userInfoObject['user']['Personal_Information'];
                    else
                        $this->logMessage['UserObjectFactory->storeUserInfo']['Personal_Information'] = "Missing Personal Information";

                    if(isset($userPersonalInfoObject['user_type_code']) && $userPersonalInfoObject['user_type_code'] != 'EXT')
                    {
                        $errorMsg = 'You\'re not authorized to access this portal.';
                        $errorArray = array(
                            'meta_code' => 408,
                            'response' => array(
                                'message' => $errorMsg,
                            ),
                        );
                        $this->logMessage['UserObjectFactory->retrieveUserInfo']['User_Type'] = $errorArray;
                        $this->logFactory->writeErrorLog($this->logMessage);
                        return json_encode($errorArray);
                    }
                    elseif(!isset($userPersonalInfoObject['user_type_code']))
                    {
                        $this->logMessage['UserObjectFactory->retrieveUserInfo']['User_Type'] = "Missing User Type Code";
                        $this->logFactory->writeErrorLog($this->logMessage);
                    }
                    else
                    {
                        $calculatedTaxResponse = $this->prepareTaxRequestResponse($userPersonalInfoObject);
                        $updatedCartResponse = $this->updateCartContentsWithTax($calculatedTaxResponse);
                        $userPersonalInfoObject = $this->formatUserObjectResponse($userPersonalInfoObject);

                        $errorFlag = false;
                        if($userPersonalInfoObject == '' || count($userPersonalInfoObject) == 0)
                        {
                            $finalRegisterResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                            $finalRegisterResponse['error_code'] = 'update_billing_tax';
                            $finalRegisterResponse['error_message'] = '';
                            $errorFlag = true;
                            $this->logMessage['UserObjectFactory->storeUserInfo']['Personal_Information'] = "Missing Personal Information";
                        }

                        if(strlen($calculatedTaxResponse['response']['TaxRate']) == 0)
                        {
                            $finalRegisterResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                            $finalRegisterResponse['error_code'] = 'update_user_billing';
                            $finalRegisterResponse['error_message'] = '';
                            $errorFlag = true;
                            $this->logMessage['UserObjectFactory->storeUserInfo']['Tax_Information'] = "Missing Avalara Tax Information";
                        }
                        if($errorFlag == false)
                        {
                            $finalRegisterResponse['status'] = $this->config->get('Enums.Status.SUCCESS');
                            $finalRegisterResponse['error_code'] = '';
                            $finalRegisterResponse['error_message'] = '';
                            $this->logFactory->writeInfoLog("Register Success");
                        }
                        $finalRegisterResponse['response']['user']['Personal_Information'] = $userPersonalInfoObject;
                        $finalRegisterResponse['response']['cart'] = $updatedCartResponse;
                        $finalRegisterResponse['response']['user']['Personal_Information']['response']['user_token'] = $userToken;

                        if($this->logMessage != '')
                            $this->logFactory->writeErrorLog($this->logMessage);

                        $this->logMessage['UserObjectFactory->storeUserInfo']['Response'] = $finalRegisterResponse;
                        $this->logFactory->writeInfoLog($finalRegisterResponse);

                        $registerResponse = $this->setUserInfoSession($finalRegisterResponse);

                        return json_encode($registerResponse);
                    }
                    break;

                case '400':
                case '401':
                case '402':
                    $activityLog = Array();
                    $activityLog["activity_log"] = Session::get('user_activity');
                    $this->logFactory->writeActivityLog($activityLog);
                    $finalRegisterResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                    $finalRegisterResponse['error_code'] = 'display_message';
                    $finalRegisterResponse['error_message'] = $this->config->get('Enums.Status.MESSAGE');
                    return json_encode($finalRegisterResponse);
                    break;
                case '403':
                    $errorMsg = is_string($retrieveUserToken['error']) ? $retrieveUserToken['error'] : $retrieveUserToken['error']['0'];
                    $errorArray = array(
                        'meta_code' => $retrieveUserToken['meta']['code'],
                        'response' => array(
                            'message' => $errorMsg,
                        ),
                    );
                    $this->logMessage['UserObjectFactory->storeUserInfo']['Errors'] = $errorArray;
                    $this->logFactory->writeInfoLog($errorArray);

                    return json_encode($errorArray);
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
                    $this->logMessage['UserObjectFactory->storeUserInfo']['Errors'] = $errorArray;
                    $this->logFactory->writeErrorLog($errorArray);
                    return $errorArray;
                    break;
            }
        }else{
            $this->logFactory->writeErrorLog("Register Failure");
        }
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
        $userHelper = $this->getUserHelper();
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
                        $this->logFactory->writeInfoLog("Personal Update Success");
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
                        $this->logFactory->writeErrorLog($this->logMessage);

                    $this->logMessage['UserObjectFactory->updateUserPersonalInfo']['Response'] = $finalUpdatedUserResponse;
                    $this->logFactory->writeInfoLog($finalUpdatedUserResponse);

                    $updateResponse = $this->setUserInfoSession($finalUpdatedUserResponse);

                    return json_encode($updateResponse);

                    break;

                case '400':
                case '401':
                case '402':
                    $activityLog = Array();
                    $activityLog["activity_log"] = Session::get('user_activity');
                    $this->logFactory->writeActivityLog($activityLog);
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
                    $this->logFactory->writeErrorLog($errorArray);

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
                    $this->logFactory->writeErrorLog($errorArray);
                    return $errorArray;
                    break;
            }
        }
        else{
            $this->logFactory->writeErrorLog("Personal Update Failure");
        }
    }

    /**This function will send request for Forgot Password.
     * @param $params : $email, $campaign_folder, $campaign_name, $password_reset_url
     * @return array
     * @author gmathur
     */
    public function createByEmail($params)
    {
        $userHelper = $this->getUserHelper();
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
                            $this->logFactory->writeInfoLog("Reset Password Sent Successfully");
                        } else {
                            $finalForgotPasswordResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                            $finalForgotPasswordResponse['error_code'] = '';
                            $finalForgotPasswordResponse['error_message'] = '';
                            $this->logFactory->writeInfoLog("Reset Password Failed");
                        }
                        break;
                    case '400':
                        $activityLog = Array();
                        $activityLog["activity_log"] = Session::get('user_activity');
                        $this->logFactory->writeActivityLog($activityLog);
                        break;

                    case '403':
                        $finalForgotPasswordResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                        $finalForgotPasswordResponse['error_code'] = 'display_message';
                        $finalForgotPasswordResponse['error_message'] = $forgotPasswordResponse["error"][0];
                        $this->logFactory->writeInfoLog("Reset Password Failed. No meta code key");
                        break;

                    default:
                        $finalForgotPasswordResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                        $finalForgotPasswordResponse['error_code'] = '';
                        $finalForgotPasswordResponse['error_message'] = '';
                        $this->logFactory->writeInfoLog("Reset Password Failed. No meta code key");
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
            $this->logFactory->writeErrorLog($forgotPasswordErrorArray);
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
                        $this->logFactory->writeInfoLog("Change Password Success");
                    } else {
                        $finalChangePasswordResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                        $finalChangePasswordResponse['error_code'] = '';
                        $finalChangePasswordResponse['error_message'] = '';
                        $this->logFactory->writeInfoLog("Change Password Failed");
                    }
                    break;
                case '400':
                    $activityLog = Array();
                    $activityLog["activity_log"] = Session::get('user_activity');
                    $this->logFactory->writeActivityLog($activityLog);
                    break;

                default:
                    $finalChangePasswordResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                    $finalChangePasswordResponse['error_code'] = '';
                    $finalChangePasswordResponse['error_message'] = '';
                    $this->logFactory->writeInfoLog("Change Password Failed as ". $changePasswordResponse['error'][0]);
                    break;
            }
        }
        return $finalChangePasswordResponse;
    }

    private function prepareAvalaraAddressValidate($userPersonalInfo)
    {
        $userHelper = $this->getUserHelper();
        $userLoginAddressValidation = $userHelper->areValidLoginAddressParams($userPersonalInfo['personal_address']);
        if(!empty($userLoginAddressValidation))
        {
            $this->logFactory->writeInfoLog($userLoginAddressValidation);
        }
        else
        {
            $avalaraParams = Array(
                'Line1'       => $userPersonalInfo['personal_address']['address_line_1'],
                'Line2'       => $userPersonalInfo['personal_address']['address_line_2'],
                'City'        => $userPersonalInfo['personal_address']['city_name'],
                'Region'      => $userPersonalInfo['personal_address']['state_code'],
                'PostalCode'  => $userPersonalInfo['personal_address']['zip_code'],
                'Country'     => $userPersonalInfo['personal_address']['country_code']
            );
            $validatedAddress = $this->avalaraFactory->getValidAddress($avalaraParams);
            if(isset($validatedAddress['ResultCode']) && $validatedAddress['ResultCode'] == 'Success')
                Session::put('addressValidated', 'true');
            else
                Session::put('addressValidated', 'false');
        }
    }

    private function formatUserObjectResponse($userInfo)
    {
        $finalUserResponse = Array();
        if(isset($userInfo['user_identifier']) || is_null($userInfo['user_identifier'])){
            $this->userModel->setUserId($userInfo['user_identifier']);
            $finalUserResponse['response']['user_identifier'] = $this->userModel->getUserId();
        }
        if(isset($userInfo['email']) || is_null($userInfo['email'])){
            $this->userModel->setEmail($userInfo['email']);
            $finalUserResponse['response']['email'] = $this->userModel->getEmail();
        }
        if(isset($userInfo['first_name']) || is_null($userInfo['first_name'])){
            $this->userModel->setFirstName($userInfo['first_name']);
            $finalUserResponse['response']['first_name'] = $this->userModel->getFirstName();
        }
        if(isset($userInfo['last_name']) || is_null($userInfo['last_name'])){
            $this->userModel->setLastName($userInfo['last_name']);
            $finalUserResponse['response']['last_name'] = $this->userModel->getLastName();
        }
        if(isset($userInfo['personal_address']['phone_number']) || is_null($userInfo['personal_address']['phone_number'])){
            $this->userModel->setPhoneNumber($userInfo['personal_address']['phone_number']);
            $finalUserResponse['response']['phone_number'] = $this->userModel->getPhoneNumber();
        }
        if(isset($userInfo['user_status_code']) || is_null($userInfo['user_status_code'])){
            $this->userModel->setStatusCode($userInfo['user_status_code']);
            $finalUserResponse['response']['status_code'] = $this->userModel->getStatusCode();
            /** This session is created to keep track of the user's reset status,
             * during their interaction with the cart. **/
            if ($this->userModel->getStatusCode() == "RESET")
                Session::put("user_status_reset", true);
        }
        if(isset($userInfo['personal_address']['address_line_1']) || is_null($userInfo['personal_address']['address_line_1'])){
            $this->userModel->setAddressLine1($userInfo['personal_address']['address_line_1']);
            $finalUserResponse['response']['address_line_1'] = $this->userModel->getAddressLine1();
        }
        if(isset($userInfo['personal_address']['address_line_2']) || is_null($userInfo['personal_address']['address_line_2'])){
            $this->userModel->setAddressLine2($userInfo['personal_address']['address_line_2']);
            $finalUserResponse['response']['address_line_2'] = $this->userModel->getAddressLine2();
        }
        if(isset($userInfo['personal_address']['city_name']) || is_null($userInfo['personal_address']['city_name'])){
            $this->userModel->setCityName($userInfo['personal_address']['city_name']);
            $finalUserResponse['response']['city'] = $this->userModel->getCityName();
        }
        if(isset($userInfo['personal_address']['zip_code']) || is_null($userInfo['personal_address']['zip_code'])){
            $this->userModel->setZipCode($userInfo['personal_address']['zip_code']);
            $finalUserResponse['response']['zip_code'] = $this->userModel->getZipCode();
        }
        if(isset($userInfo['personal_address']['state_code']) || is_null($userInfo['personal_address']['state_code'])){
            $this->userModel->setStateCode($userInfo['personal_address']['state_code']);
            $finalUserResponse['response']['state_code'] = $this->userModel->getStateCode();
        }
        if(isset($userInfo['personal_address']['state_name']) || is_null($userInfo['personal_address']['state_name'])){
            $this->userModel->setStateName($userInfo['personal_address']['state_name']);
            $finalUserResponse['response']['state_name'] = $this->userModel->getStateName();
        }
        if(isset($userInfo['personal_address']['country_code']) || is_null($userInfo['personal_address']['country_code'])){
            $this->userModel->setCountryCode($userInfo['personal_address']['country_code']);
            $finalUserResponse['response']['country_code'] = $this->userModel->getCountryCode();
        }
        if(isset($userInfo['personal_address']['country_name']) || is_null($userInfo['personal_address']['country_name'])){
            $this->userModel->setCountryName($userInfo['personal_address']['country_name']);
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
        $userHelper = $this->getUserHelper();

        $billingInfo = isset($userInfo['response']['user']['Billing_Information']) ? $userInfo['response']['user']['Billing_Information'] : array();

        $cartInfo = isset($userInfo['response']['cart']) ? $userInfo['response']['cart'] : array();

        if(isset($billingInfo) && count($billingInfo)!=0)
        {
            $creditCardInfo = isset($billingInfo['response']['credit_card_details']) ? $billingInfo['response']['credit_card_details'] : null;
            $formattedCCInfoSlug = $userHelper->prefixPaymentToken($creditCardInfo);
            $userInfo['response']['user']['Billing_Information']['response']['credit_card_details'] = $formattedCCInfoSlug;
        }

        if(isset($cartInfo) && count($cartInfo)!=0)
        {
            $userInfo['response']['cart'] = $cartInfo;
        }

        $userHelper->setUserSession($userInfo);

        $formattedCCInfo = array();

        if(isset($formattedCCInfoSlug) && count($formattedCCInfoSlug)!=0)
        {
            $formattedCCInfo = $userHelper->setCreditCardinfo($formattedCCInfoSlug);
        }

        $userInfoResponse = array();
        $userInfoResponse['status'] = isset($userInfo['status']) ? $userInfo['status'] : 0;
        $userInfoResponse['error_code'] = isset($userInfo['error_code']) ? $userInfo['error_code'] : "";
        $userInfoResponse['error_message'] = isset($userInfo['error_message']) ? $userInfo['error_message'] : "";
        $userInfoResponse['response']['user']['Personal_Information'] = isset($userInfo['response']['user']['Personal_Information']) ? $userInfo['response']['user']['Personal_Information'] : null;
        $userInfoResponse['response']['user']['Billing_Information']['response']['credit_card_details'] = isset($formattedCCInfo) ? $formattedCCInfo : "";
        $userInfoResponse['response']['cart'] = $cartInfo;

        /**
         * Not sending user_identifier and user_token to the front-end.
         */

        if(isset($userInfoResponse['response']['user']['Personal_Information']['response']['user_identifier']))
            unset( $userInfoResponse['response']['user']['Personal_Information']['response']['user_identifier']);

        if(isset($userInfoResponse['response']['user']['Personal_Information']['response']['user_token']))
            unset( $userInfoResponse['response']['user']['Personal_Information']['response']['user_token']);

        return $userInfoResponse;
    }

}