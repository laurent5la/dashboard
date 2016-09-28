<?php
namespace App\Mapper;

use App\Exceptions\InvalidUserTokenStatusException;
use App\Exceptions\NotFoundErrorException;
use App\Exceptions\ServerErrorException;
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

    /**
     * @param array $params {
     *      @var string $email,
     *      @var string $password
     * }
     *
     * @return Illuminate\Http\Response
     * @throws InvalidUserTokenStatusException
     * @throws NotFoundErrorException
     * @throws ServerErrorException
     */
    public function authenticateUser($params)
    {
        $owlFactory = new OwlFactory();
        $retrieveUserToken = $owlFactory->retrieveUserToken($params['email'], $params['password']);
        $finalLoginResponse = [];

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
                        $this->formatStandardResponse(
                            $finalLoginResponse,
                            $this->config->get('Enums.Status.SUCCESS'),
                            $userDetail['response'],
                            []);
                        $jwt = JwtLoginDashboardFactory::createToken($basicUserInfoArray["response"])->__toString();

                    } else {
                        throw new InvalidUserTokenStatusException("Invalid User Token status", 500);
                    }

                    break;
                case '400':
                case '401':
                case '402':
                    $activityLog = Array();
                    $activityLog["activity_log"] = Session::get('user_activity');
                    $this->activity($activityLog);
                    throw new ServerErrorException($this->config->get('Enums.Status.MESSAGE'), 500);
                    break;
                case '403':
                    $errorMsg = is_string($retrieveUserToken['error']) ? $retrieveUserToken['error'] : $retrieveUserToken['error']['0'];
                    throw new ServerErrorException($errorMsg, intval($retrieveUserToken['meta']['code']));
                    break;
                case '404':
                    throw new NotFoundErrorException('Resource not found', 404);
                    break;
                default:
                    throw new ServerErrorException($this->config->get('Enums.Status.MESSAGE'), 500);
                    break;
            }
        }else{
            throw new ServerErrorException("Login Failure", 500);
        }
        return response($finalLoginResponse)->header('Authorization', "Bearer $jwt JWT");
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
     * @throws InvalidUserTokenStatusException
     * @throws NotFoundErrorException
     * @throws ServerErrorException
     * @use App\Factory\OwlFactory::__construct()
     * @author aprakash
     */
    public function setUser($params)
    {
        $owlFactory = new OwlFactory();
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
                        $finalRegisterResponse = [];
                        $this->formatStandardResponse(
                            $finalRegisterResponse,
                            $this->config->get('Enums.Status.SUCCESS'),
                            $userDetail['response'],
                            []);
                        $jwt = JwtLoginDashboardFactory::createToken($basicUserInfoArray["response"])->__toString();
                    } else {
                        throw new InvalidUserTokenStatusException("Invalid User Token status", 500);
                    }
                    break;

                case '400':
                case '401':
                case '402':
                    throw new ServerErrorException($this->config->get('Enums.Status.MESSAGE'), 500);
                    break;
                case '403':
                    $errorMsg = is_string($retrieveUserToken['error']) ?
                        $retrieveUserToken['error'] :
                        $retrieveUserToken['error']['0'];
                    throw new ServerErrorException($errorMsg, intval($retrieveUserToken['meta']['code']));
                    break;

                case '404':
                    break;

                default:
                    throw new ServerErrorException($this->config->get('Enums.Status.MESSAGE'), 500);
                    break;
            }
        }else{
            $this->error("Register Failure");
        }
        return response($finalRegisterResponse)->header('Authorization', "Bearer $jwt JWT");
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

    private function formatStandardResponse(&$array, $status, $payload, $messages)
    {
        $array['status'] = $status;
        $array['payload'] = $payload;
        $array['messages'] = $messages;
    }
}
