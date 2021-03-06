<?php
namespace App\Http\Controllers;

use App\Exceptions\EmailParamMissingException;
use App\Exceptions\InvalidInputParametersException;
use App\Factory\UserObjectFactory;
use App\Mapper\UserMapper;
use Request;
use Config;

/**
 * Class UserController
 * @package App\Http\Controllers
 */
class UserController extends Controller {

    /** @var  UserMapper $userMapper */
    private $userMapper;

    private $userFactory;

    private function getUserMapper()
    {
        if (is_null($this->userMapper)) {
            $this->userMapper = new UserMapper();
        }
        return $this->userMapper;
    }

    private function getUserFactory()
    {
        if (is_null($this->userFactory)) {
            $this->userFactory = new UserObjectFactory();
        }

        return $this->userFactory;
    }

    /**
     * Backend action to change the login a user.
     * @return array
     * @throws InvalidInputParametersException
     * @since 16.12
     * @author aprakash
     */
    public function login()
    {
        $params = Request::all();
        $secureParams = $this->cleanParams($params);
        $jwt = null;

        if(!is_array($params)) {
            throw new InvalidInputParametersException('Input parameters are not properly structured');
        } else {
            $this->timingStart(__METHOD__);
            $userFactory = $this->getUserFactory();

            $userObject = $userFactory->authenticateUser($secureParams, $jwt);
            $this->timingEnd();
            $standardResponse = [];
            $this->formatStandardResponse(
                $standardResponse,
                Config::get('Enums.Status.SUCCESS'),
                $userObject,
                []
            );
            return response($standardResponse, 200, ["Authorization" => "Bearer $jwt JWT"]) ;
        }
    }

    /**
     * Backend action to change the register a user.
     * @return array
     * @throws InvalidInputParametersException
     * @since 16.12
     * @author aprakash
     */
    public function register()
    {
        $params = Request::all();
        $secureParams = $this->cleanParams($params);
        $jwt = null;

        if(!is_array($params))
        {
            throw new InvalidInputParametersException('Input parameters are not properly structured', 403);
        } else {
            $this->timingStart(__METHOD__);
            $userFactory = $this->getUserFactory();
            $userObject = $userFactory->setUser($secureParams, $jwt);
            $this->timingEnd();
            $standardResponse = [];
            $this->formatStandardResponse(
                $standardResponse,
                Config::get('Enums.Status.SUCCESS'),
                $userObject,
                []
            );
            return response($standardResponse, 200, ["Authorization" => "Bearer $jwt JWT"]);
        }
    }


    public function logout()
    {
        $this->timingStart(__METHOD__);
        $userMapper = new UserMapper();
        $userObject = $userMapper->logoutUser();
        $this->timingEnd();
        return $userObject;
    }

    /**
     * Backend action to send the reset password request
     *
     * @return array
     * @throws InvalidInputParametersException
     * @throws EmailParamMissingException
     * @since 16.12
     * @author mvalenzuela
     */
    public function resetPassword()
    {
        $params = Request::json()->all();
        $secureParams = $this->cleanParams($params);

        if(!is_array($params)) {
            throw new InvalidInputParametersException('Input parameters are not properly structured', 400);
        } elseif(isset($params['email']) && strlen($params['email'])!=0) {
            $this->setParamsForResetPassword($params);
            $this->timingStart(__METHOD__);
            $userMapper = $this->getUserMapper();
            $userObject = $userMapper->forgotPassword($secureParams);
            $this->timingEnd();
            return $userObject;
        } else {
            throw new EmailParamMissingException('Email key does not exist in '. __METHOD__, 400);
        }
    }

    /**
     * Sets parameters for reset password
     * @param Array $params
     * @since 16.12
     * @author mvalenzuela
     */
    private function setParamsForResetPassword(&$params)
    {
        $params['campaign_folder']      = Config::get('reset_password.campaign_folder');
        $params['campaign_name']        = Config::get('reset_password.campaign_name');
        $params['password_reset_url']   = Config::get('reset_password.password_reset_url');
    }

    /**
     * Backend action to change the password after reset request.
     * @return array
     * @throws InvalidInputParametersException
     * @since 16.12
     * @author mvalenzuela
     */
    public function newPassword()
    {
        $params = Request::json()->all();
        $secureParams = $this->cleanParams($params);

        if(!is_array($params))
        {
            throw new InvalidInputParametersException('Input parameters are not properly structured', 400);
        }
        else
        {
            $this->timingStart(__METHOD__);
            $userMapper = new UserMapper();
            $userObject = $userMapper->changePassword($secureParams);
            $this->timingEnd();
            return $userObject;
        }
    }

    /**
     * This function takes input parameters, cleans it and returns it.
     * @return array
     * @since 16.12
     * @author aprakash
     */
    private function cleanParams($params)
    {
        $paramsWithNoTags = array_map("strip_tags", $params);
        $secureParams = array_map("trim", $paramsWithNoTags);

        if($params !== $secureParams)
            $this->logWarning(__METHOD__ , "The input parameters did not match the clean parameters");

        return $secureParams;
    }
}
