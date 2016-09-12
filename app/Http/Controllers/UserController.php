<?php
namespace App\Http\Controllers;

use App\Factory\LogFactory;
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

    private function getUserMapper()
    {
        if (is_null($this->userMapper)) {
            $this->userMapper = new UserMapper();
        }
        return $this->userMapper;
    }

    public function index()
    {
        return view('login');
    }

    /**
     * Backend action to send the reset password request
     *
     * @return array
     * @since 16.13
     * @author mvalenzuela
     */
    public function resetPassword()
    {
        $logFactory = new LogFactory();
        $params = Request::json()->all();

        if(!is_array($params)) {
            $this->logError(__METHOD__, 'Input parameters are not properly structured', $logFactory);
        } else {
            if(isset($params['email']) && strlen($params['email'])!=0) {
                $this->setParamsForResetPassword($params);
                $this->timingStart(__METHOD__);
                $userMapper = $this->getUserMapper();
                $userObject = $userMapper->forgotPassword($params);
                $this->timingEnd($logFactory);
                return $userObject;
            } else {
                $this->logError(__METHOD__, 'Email key does not exist for Forgot Password.', $logFactory);
            }
        }
        return [];
    }

    /**
     * Sets parameters for reset password
     * @param Array $params
     * @since 16.13
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
     * @since 16.13
     * @author mvalenzuela
     */
    public function newPassword()
    {
        $logFactory = new LogFactory();
        $params = Request::json()->all();

        if(!is_array($params))
        {
            $this->logError(__METHOD__, 'Input parameters are not properly structured', $logFactory);
        }
        else
        {
            $this->timingStart(__METHOD__);
            $userMapper = new UserMapper();
            $userObject = $userMapper->changePassword($params);
            $this->timingEnd($logFactory);
            return $userObject;
        }

        return [];
    }

    /**
     * Backend action to change the login a user.
     * @return array
     * @since 16.12
     * @author aprakash
     */
    public function login()
    {
        $logFactory = new LogFactory();
        $params = Request::all();
        $secureParams = $this->cleanParams($params);

        if(!is_array($params))
        {
            $this->logError(__METHOD__, 'Input parameters are not properly structured', $logFactory);
        }
        else
        {
            $this->timingStart(__METHOD__);
            $userMapper = new UserMapper();
            $userObject = $userMapper->authenticateUser($secureParams);
            $this->timingEnd($logFactory);
            return $userObject;
        }

        return [];
    }

    /**
     * Backend action to change the register a user.
     * @return array
     * @since 16.12
     * @author aprakash
     */
    public function register()
    {
        $logFactory = new LogFactory();
        $params = Request::json()->all();
        $secureParams = $this->cleanParams($params);

        if(!is_array($params))
        {
            $this->logError(__METHOD__, 'Input parameters are not properly structured', $logFactory);
        }
        else
        {
            $this->timingStart(__METHOD__);
            $userMapper = new UserMapper();
            $userObject = $userMapper->setUser($secureParams);
            $this->timingEnd($logFactory);
            return $userObject;
        }

        return [];
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
            $this->logWarning(__METHOD__ , "The input parameters did not match the clean parameters", $logFactory);

        return $secureParams;
    }
}
