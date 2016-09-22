<?php
namespace App\Mapper;

use App\Factory\UserObjectFactory;
use App\Factory\OwlFactory;
use App\Factory\JwtLoginDashboardFactory;
use App\Traits\Logging;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class UserMapper
{
    use Logging;

    public function __construct()
    {
        $this->config = app()['config'];
    }

    public function getUser($params)
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->retrieveUserDashboardInfo($params);
        return $userObject;
    }

    public function authenticateUser($params)
    {
        $owlFactory = new OwlFactory();
        $logMessage = [];
        $retrieveUserToken = $owlFactory->retrieveUserToken($params['email'], $params['password']);
        $finalLoginResponse = [];
        $jwt = "";

        if ($retrieveUserToken && isset($retrieveUserToken['meta'])) {
            switch ($retrieveUserToken['meta']['code']) {
                case '200':
                    $userToken = $retrieveUserToken['response']['user_token'];
                    $basicUserInfoArray = $owlFactory->isUserTokenValid($userToken);


                    if((!is_null($basicUserInfoArray))
                        && (isset($basicUserInfoArray["meta"]))
                        && (isset($basicUserInfoArray["meta"]["code"]))
                        && ($basicUserInfoArray["meta"]["code"] == 200)) {
                        //appending user token to response retrieved from OWL
                        $basicUserInfoArray["response"]["user_token"] = $userToken;
                        $userDetail = $owlFactory->getUserDetail($userToken);
                        //@TODO format userdetail information with models and objectfactories
                        $this->info("Login Success");
                        $finalLoginResponse['status'] = $this->config->get('Enums.Status.SUCCESS');
                        $finalLoginResponse['error_code'] = '';
                        $finalLoginResponse['error_message'] = '';
                        $finalLoginResponse['response'] = $userDetail['response'];
                        $jwt = JwtLoginDashboardFactory::createToken($basicUserInfoArray["response"])->__toString();

                    } else {
                        $finalLoginResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                        $finalLoginResponse['error_code'] = 'invalid_user_token_status';
                        $finalLoginResponse['error_message'] = '';
                    }

                    break;
                case '400':
                case '401':
                case '402':
                    $activityLog = Array();
                    $activityLog["activity_log"] = Session::get('user_activity');
                    $this->activity($activityLog);
                    $finalLoginResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                    $finalLoginResponse['error_code'] = 'display_message';
                    $finalLoginResponse['error_message'] = $this->config->get('Enums.Status.MESSAGE');
                    break;
                case '403':
//                    @TODO Enable it for failed login attempts
//                    if(Session::get('failed_attempts'))
//                        $failedLoginAttempts = Session::get('failed_attempts');
//                    else
//                        $failedLoginAttempts = 0;
//                    $failedLoginAttempts += 1;
//                    Session::set('failed_attempts', $failedLoginAttempts);
//                    if($failedLoginAttempts >= 3)
//                    {
//                        $errorMsg = 'You\'ve exceeded maximum failed attempts! Please contact the customer support.';
//                        $errorArray = array(
//                            'meta_code' => 500,
//                            'response' => array(
//                                'message' => $errorMsg,
//                            ),
//                        );
//                    }
//                    else
//                    {
                    $errorMsg = is_string($retrieveUserToken['error']) ? $retrieveUserToken['error'] : $retrieveUserToken['error']['0'];
                    $finalLoginResponse = array(
                        'error_code' => $retrieveUserToken['meta']['code'],
                        'response' => array(
                            'message' => $errorMsg,
                        ),
                    );
                    $logMessage['UserObjectFactory->retrieveUserInfo']['Errors'] = $finalLoginResponse;
                    $this->info($finalLoginResponse);

//                    }

                    break;

                case '404':
                    $logMessage['UserObjectFactory->retrieveUserInfo']['Errors'] = 'Page Not Found';
                    $this->error($logMessage);
                    break;

                default:
                    $errorMsg = $this->config->get('Enums.Status.MESSAGE');
                    $finalLoginResponse = array(
                        'error_code' => 500,
                        'response' => array(
                            'message' => $errorMsg,
                        ),
                    );
                    $logMessage['UserObjectFactory->retrieveUserInfo']['Errors'] = $finalLoginResponse;
                    $this->error($finalLoginResponse);
                    break;
            }
        }else{
            $this->error("Login Failure");
        }
        return response($finalLoginResponse)->header('jwt', $jwt);
    }

    /**
     * Stores the User Information
     * @param array $params {
     *     @var string $email email of the user
     *     @var string $first_name first name of the user
     *     @var string $last_name last name of the user
     *     @var string $password password of the user
     * }
     *
     * @return JSON UserInfoObject $finalRegisterResponse Final User Response Object consisting of first_name, last_name, email, and password
     * @use App\Factory\OwlFactory::__construct()
     * @author aprakash
     */
    public function setUser($params)
    {
        $owlFactory = new OwlFactory();
        $logMessage = [];
        $retrieveUserToken = $owlFactory->userRegister($params);
        $finalRegisterResponse = [];
        $jwt = "";

        if (isset($retrieveUserToken['meta'])) {
            switch ($retrieveUserToken['meta']['code']) {
                case '200':
                    $userToken = $retrieveUserToken['response']['user_token'];
                    $basicUserInfoArray = $owlFactory->isUserTokenValid($userToken);

                    if((!is_null($basicUserInfoArray))
                        && (isset($basicUserInfoArray["meta"]))
                        && (isset($basicUserInfoArray["meta"]["code"]))
                        && ($basicUserInfoArray["meta"]["code"] == 200)) {

                        //appending user token to response retrieved from OWL
                        $basicUserInfoArray["response"]["user_token"] = $userToken;
                        $userDetail = $owlFactory->getUserDetail($userToken);
                        //@TODO format userdetail information with models and objectfactories

                        $this->info("Register Success");
                        $finalRegisterResponse['status'] = $this->config->get('Enums.Status.SUCCESS');
                        $finalRegisterResponse['error_code'] = '';
                        $finalRegisterResponse['error_message'] = '';
                        $finalRegisterResponse['response'] = $userDetail['response'];
                        $jwt = JwtLoginDashboardFactory::createToken($basicUserInfoArray["response"])->__toString();


                    } else {
                        $finalRegisterResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                        $finalRegisterResponse['error_code'] = 'invalid_user_token_status';
                        $finalRegisterResponse['error_message'] = '';
                    }
                    break;

                case '400':
                case '401':
                case '402':
                    $activityLog = Array();
                    $activityLog["activity_log"] = Session::get('user_activity');
                    $this->activity($activityLog);
                    $finalRegisterResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                    $finalRegisterResponse['error_code'] = 'display_message';
                    $finalRegisterResponse['error_message'] = $this->config->get('Enums.Status.MESSAGE');
                    break;
                case '403':
                    $errorMsg = is_string($retrieveUserToken['error']) ? $retrieveUserToken['error'] : $retrieveUserToken['error']['0'];
                    $finalRegisterResponse = array(
                        'error_code' => $retrieveUserToken['meta']['code'],
                        'response' => array(
                            'message' => $errorMsg,
                        ),
                    );
                    $logMessage['UserObjectFactory->storeUserInfo']['Errors'] = $finalRegisterResponse;
                    $this->info($finalRegisterResponse);
                    break;

                case '404':
                    break;

                default:
                    $errorMsg = $this->config->get('Enums.Status.MESSAGE');
                    $finalRegisterResponse = array(
                        'error_code' => 500,
                        'response' => array(
                            'message' => $errorMsg,
                        ),
                    );
                    $logMessage['UserObjectFactory->storeUserInfo']['Errors'] = $finalRegisterResponse;
                    $this->error($finalRegisterResponse);
                    break;
            }
        }else{
            $this->error("Register Failure");
        }
        return response($finalRegisterResponse)->header('jwt', $jwt);
    }

    public function updateUserPersonalInformation($params)
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->updateUserPersonalInfo($params);
        return $userObject;
    }

    public function logoutUser()
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->logoutUser();
        return $userObject;
    }

    public function forgotPassword($params)
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->createByEmail($params);
        return $userObject;
    }

    public function changePassword($params)
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->changePassword($params);
        return $userObject;
    }
}
