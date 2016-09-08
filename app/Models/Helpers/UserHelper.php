<?php
namespace App\Models\Helpers;

use App\Mapper\LogFactory;
use Config;
use Session;
use App\Models\ShoppingCart;

/**
 * This class contains all the Helper functions required by the User Operations.
 * @return $logMessage
 * @author kparakh
 */

class UserHelper
{
    private $logFactory;
    private $logMessage = array();
    private $config;


    public function __construct()
    {
        $this->logFactory = new LogFactory();
        $this->config = app()['config'];
    }

    public function isValidPassword($password)
    {
        if(strlen($password) < 6 || !preg_match("#[0-9]+#", $password) ||  !preg_match("#[a-z]+#", $password))
        {
            $this->logMessage['UserHelper->isValidPassword']['Password'] = 'Invalid Password';
            $this->logFactory->writeErrorLog($this->logMessage);
            return false;
        }
        else
            return true;
    }

    public function validForgotPasswordParams($params)
    {
        if(!isset($params['email']) || filter_var($params['email'], FILTER_VALIDATE_EMAIL) === false)
        {
            $this->logMessage['UserHelper->validateForgotPasswordParams']['email'] = (isset($params['email'])? $params['email'] : '').' Invalid Email in the Request';
            $this->logMessage['email'] = $this->logMessage['UserHelper->validateForgotPasswordParams']['email'];
        }
        if(!isset($params['campaign_folder']) || strlen($params['campaign_folder']) == 0)
        {
            $this->logMessage['UserHelper->validateForgotPasswordParams']['campaign_folder'] = 'Invalid campaign folder in the Request';
        }
        if(!isset($params['campaign_name']) || strlen($params['campaign_name']) == 0)
        {
            $this->logMessage['UserHelper->validateForgotPasswordParams']['campaign_name'] = 'Invalid campaign name in the Request';
        }
        if(!isset($params['password_reset_url']) || strlen($params['password_reset_url']) == 0)
        {
            $this->logMessage['UserHelper->validateForgotPasswordParams']['password_reset_url'] = 'Invalid password reset url in the Request';
        }

        return $this->logMessage;
    }

    public function areValidRegisterParams($params)
    {
        if(!isset($params['email']) || is_null($params['email']) || !filter_var($params['email'], FILTER_VALIDATE_EMAIL))
            $this->logMessage['UserHelper->areValidRegisterParams']['Email'] = 'Invalid Email in the Request';
        if(!isset($params['personal-first-name']) || is_null($params['personal-first-name']))
            $this->logMessage['UserHelper->areValidRegisterParams']['First_Name'] = 'Invalid First Name in the Request';
        if(!isset($params['personal-last-name']) || is_null($params['personal-last-name']))
            $this->logMessage['UserHelper->areValidRegisterParams']['Last_Name'] = 'Invalid Last Name in the Request';
        if(!isset($params['password1']) || is_null($params['password1']) || (strlen($params['password1']) < 6 || !preg_match("#[0-9]+#", $params['password1']) || !preg_match("#[a-z]+#", $params['password1'])))
            $this->logMessage['UserHelper->areValidRegisterParams']['Password'] = 'Invalid Password in the Request';
        if(!isset($params['personal-phone']) || is_null($params['personal-phone']))
            $this->logMessage['UserHelper->areValidRegisterParams']['Phone'] = 'Invalid Phone Number in the Request';
        if(!isset($params['personal-address-line1']) || is_null($params['personal-address-line1']))
            $this->logMessage['UserHelper->areValidRegisterParams']['Address_1'] = 'Invalid Address 1 in the Request';
        if(!isset($params['personal-city']) || is_null($params['personal-city']))
            $this->logMessage['UserHelper->areValidRegisterParams']['City'] = 'Invalid City in the Request';
        if(!isset($params['personal-state']) || is_null($params['personal-state']))
            $this->logMessage['UserHelper->areValidRegisterParams']['State'] = 'Invalid State in the Request';
        if(!isset($params['personal-zip']) || is_null($params['personal-zip']))
            $this->logMessage['UserHelper->areValidRegisterParams']['Zip'] = 'Invalid Zip in the Request';
        if(!isset($params['personal-country-name']) || is_null($params['personal-country-name']))
            $this->logMessage['UserHelper->areValidRegisterParams']['Country'] = 'Invalid Country Name in the Request';
        if(!isset($params['personal-country']) || is_null($params['personal-country']))
            $this->logMessage['UserHelper->areValidRegisterParams']['Country'] = 'Invalid Country in the Request';
        return $this->logMessage;
    }

    public function areValidUpdateParams($params)
    {
        if(!isset($params['new-address-line1']) || (isset($params['new-address-line1']) && strlen($params['new-address-line1']) == 0))
            $this->logMessage['UserHelper->areValidUpdateParams']['Address_1'] = 'Invalid Address 1 in the Request';
        if(!isset($params['new-city']) || (isset($params['new-city']) && strlen($params['new-city']) == 0))
            $this->logMessage['UserHelper->areValidUpdateParams']['City'] = 'Invalid City in the Request';
        if(!isset($params['new-state']) || (isset($params['new-state']) && strlen($params['new-state']) == 0))
            $this->logMessage['UserHelper->areValidUpdateParams']['State'] = 'Invalid State in the Request';
        if(!isset($params['new-zip']) || (isset($params['new-zip']) && strlen($params['new-zip']) == 0))
            $this->logMessage['UserHelper->areValidUpdateParams']['Zip'] = 'Invalid Zip in the Request';
        if(!isset($params['new-country']) || (isset($params['new-country']) && strlen($params['new-country']) == 0))
            $this->logMessage['UserHelper->areValidUpdateParams']['country'] = 'Invalid Country in the Request';
        if(!isset($params['new-phone']) || (isset($params['new-phone']) && strlen($params['new-phone']) == 0))
            $this->logMessage['UserHelper->areValidUpdateParams']['phone'] = 'Invalid Phone in the Request';
        return $this->logMessage;
    }

    public function areValidLoginAddressParams($params)
    {
        $message = 'This may have happened when user registered from PHX without updating Personal Info.';
        if(!isset($params['address_line_1']) || (isset($params['address_line_1']) && strlen($params['address_line_1']) == 0))
            $this->logMessage['UserHelper->areValidLoginAddressParams']['Address_1'] = 'Missing Address 1 in the Request. '.$message;
        if(!isset($params['address_line_2']))
            $this->logMessage['UserHelper->areValidLoginAddressParams']['Address_2'] = 'Missing Address 2 in the Request. '.$message;
        if(!isset($params['city_name']) || (isset($params['city_name']) && strlen($params['city_name']) == 0))
            $this->logMessage['UserHelper->areValidLoginAddressParams']['City'] = 'Missing City in the Request. '.$message;
        if(!isset($params['state_code']) || (isset($params['state_code']) && strlen($params['state_code']) == 0))
            $this->logMessage['UserHelper->areValidLoginAddressParams']['State'] = 'Missing State in the Request. '.$message;
        if(!isset($params['zip_code']) || (isset($params['zip_code']) && strlen($params['zip_code']) == 0))
            $this->logMessage['UserHelper->areValidLoginAddressParams']['Zip'] = 'Missing Zip in the Request. '.$message;
        if(!isset($params['country_code']) || (isset($params['country_code']) && strlen($params['country_code']) == 0))
            $this->logMessage['UserHelper->areValidLoginAddressParams']['country'] = 'Missing Country in the Request. '.$message;
        return $this->logMessage;
    }

    /**
     * This function sets User Session.
     * @param $userInfo
     */

    public function setUserSession($userInfo)
    {
        Session::put('user', $userInfo);
    }

    /**
     * This function destroys User Session.
     */

    public function unsetUserSession()
    {
        Session::forget('user');
    }

    /** Prefixing Payment Token as PT0, PT1 to front-end.
     * @param $creditCardInfo
     * @return $creditCardInfo
     * @author gmathur
     */

    public function prefixPaymentToken($creditCardInfo)
    {
        for ($count = 0; $count < count($creditCardInfo); $count++)
        {
            if(isset($creditCardInfo[$count]['payment_token_identifier']) && strlen($creditCardInfo[$count]['payment_token_identifier'])!=0)
            {
                $creditCardInfo[$count]['payment_token_identifier'] = "PT".$count."_".$creditCardInfo[$count]['payment_token_identifier'];
            }
        }

        return $creditCardInfo;
    }

    /** Sending Payment Token Identifier as PT0, PT1 to the front-end.
     * @param $creditCardInfo
     * @return $creditCardInfo
     * @author gmathur
     */

    public function setCreditCardInfo($creditCardInfo)
    {
        for ($count = 0; $count < count($creditCardInfo); $count++)
        {
            if(isset($creditCardInfo[$count]['payment_token_identifier']) && strlen($creditCardInfo[$count]['payment_token_identifier'])!=0)
            {
                $creditCardInfo[$count]['payment_token_identifier'] = explode("_",$creditCardInfo[$count]['payment_token_identifier']);
                $creditCardInfo[$count]['payment_token_identifier'] = $creditCardInfo[$count]['payment_token_identifier'][0];
            }
        }

        return $creditCardInfo;
    }

    /** Getting Payment Token from the slug.
     * @param $creditCardInfo
     * @return $creditCardInfo
     * @author gmathur
     */

    public function getCreditCardInfo($creditCardInfo)
    {
        for ($count = 0; $count < count($creditCardInfo); $count++)
        {
            if(isset($creditCardInfo[$count]['payment_token_identifier']) && strlen($creditCardInfo[$count]['payment_token_identifier'])!=0)
            {
                $paymentTokenParts = array();
                $paymentTokenParts = explode("_",$creditCardInfo[$count]['payment_token_identifier']);
                $creditCardInfo[$count]['payment_token_identifier'] = $paymentTokenParts[count($paymentTokenParts)-1];
            }
        }

        return $creditCardInfo;
    }

    /** Getting User Token from the session.
     * @return string $userToken
     * @author gmathur
     */


    public function getUserTokenFromSession()
    {
        $userToken = '';
        if(Session::has('user'))
        {
            if(Session::has('user.response.user.Personal_Information.response.user_token'))
            {
                $userToken = Session::get('user.response.user.Personal_Information.response.user_token');
            }
            else
            {
                $this->logFactory->writeErrorLog("Missing user_token field from Session.");
            }
        }
        else
        {
            $this->logFactory->writeErrorLog("Missing User Session.");
        }

        return $userToken;
    }

    public function getUserInfoForDisplay()
    {
        $userInfoArray = array();

        if(Session::has('user'))
        {
            $userInfoArray =  Session::get('user');
            $userInfoArray['response']['status'] = 1;
            $userInfoArray['isLoggedIn'] = 1;

            $userObject = new UserHelper();
            if(isset($userInfoArray['response']['user']['Billing_Information']['response']['credit_card_details']))
            {
                $formattedBillingInfo = $userObject->setCreditCardInfo($userInfoArray['response']['user']['Billing_Information']['response']['credit_card_details']);

                $userInfoArray['response']['user']['Billing_Information']['response']['credit_card_details'] = $formattedBillingInfo;
            }
            else
            {
                $formattedBillingInfo = array();
                $userInfoArray['response']['user']['Billing_Information']['response']['credit_card_details'] = $formattedBillingInfo;
            }

            if(isset($userInfoArray['response']['user']['Personal_Information']['response']['user_identifier']))
                unset( $userInfoArray['response']['user']['Personal_Information']['response']['user_identifier']);

            if(isset($userInfoArray['response']['user']['Personal_Information']['response']['user_token']))
                unset( $userInfoArray['response']['user']['Personal_Information']['response']['user_token']);
        }

        else
        {
            $shoppingCart = new ShoppingCart();
            $shoppingCart->taxCondition();

            $userInfoArray['isLoggedIn'] = 0;
            $userInfoArray['response']['user']['Personal_Information'] = "";
            $userInfoArray['response']['user']['Billing_Information'] = "";
        }
        return $userInfoArray;
    }
}