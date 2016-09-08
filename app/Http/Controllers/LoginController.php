<?php
namespace App\Http\Controllers;

use App\Mapper\LogFactory;
use App\Mapper\UserMapper;
use Request;
use Config;

/**
 * Class LoginController
 * @package App\Http\Controllers
 */
class LoginController extends Controller {

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
     * Backend action to reset the user's password
     *
     * @return Array
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
     */
    private function setParamsForResetPassword(&$params)
    {
        $params['campaign_folder']      = Config::get('reset_password.campaign_folder');
        $params['campaign_name']        = Config::get('reset_password.campaign_name');
        $params['password_reset_url']   = Config::get('reset_password.password_reset_url');
    }
}
