<?php
namespace App\Models;
use Illuminate\Auth\UserInterface;

class User {

    private $user_id;
    private $salutation;
    private $user_first_name;
    private $user_last_name;
    private $user_email_identifier;
    private $accepted_tos;
    private $phone_number = '';
    private $source;
    private $status_code;
    private $alerts = array();
    private $error = null;
    private $full_name;
    private $address_line_1;
    private $address_line_2;
    private $city_name;
    private $zip_code;
    private $state_code;
    private $state_name;
    private $country_code;
    private $country_name;
    private $impersonated;
    private $user_identifier;
    private $user_type_code;
    private $address_identifier;
    private $job_title;
    private $contact_phone_number;
    private $fax_number;
    private $customer_group_identifier;
    private $last_login_date;
    private $last_password_changed_date;
    private $failed_login_attempt_count;
    private $user_middle_name;
    private $is_deleted_indicator;
    private $account;
    private $entitlement;
    private $alert;

    public function _construct(Entitlement $entitlementObj, Alert $alertObj, Account $accountObj)
    {
        $this->entitlement = $entitlementObj;
        $this->alert = $alertObj;
        $this->account = $accountObj;
    }

    /**
     * @return mixed
     */
    public function getLastLoginDate()
    {
        return $this->last_login_date;
    }

    /**
     * @param mixed $last_login_date
     */
    public function setLastLoginDate($last_login_date)
    {
        $this->last_login_date = $last_login_date;
    }

    /**
     * @return mixed
     */
    public function getLastPasswordChangedDate()
    {
        return $this->last_password_changed_date;
    }

    /**
     * @param mixed $last_password_changed_date
     */
    public function setLastPasswordChangedDate($last_password_changed_date)
    {
        $this->last_password_changed_date = $last_password_changed_date;
    }

    /**
     * @return mixed
     */
    public function getFailedLoginAttemptCount()
    {
        return $this->failed_login_attempt_count;
    }

    /**
     * @param mixed $failed_login_attempt_count
     */
    public function setFailedLoginAttemptCount($failed_login_attempt_count)
    {
        $this->failed_login_attempt_count = $failed_login_attempt_count;
    }

    /**
     * @return mixed
     */
    public function getUserMiddleName()
    {
        return $this->user_middle_name;
    }

    /**
     * @param mixed $user_middle_name
     */
    public function setUserMiddleName($user_middle_name)
    {
        $this->user_middle_name = $user_middle_name;
    }

    /**
     * @return mixed
     */
    public function getIsDeletedIndicator()
    {
        return $this->is_deleted_indicator;
    }

    /**
     * @param mixed $is_deleted_indicator
     */
    public function setIsDeletedIndicator($is_deleted_indicator)
    {
        $this->is_deleted_indicator = $is_deleted_indicator;
    }


    /**
     * @return mixed
     */
    public function getCustomerGroupIdentifier()
    {
        return $this->customer_group_identifier;
    }

    /**
     * @param mixed $customer_group_identifier
     */
    public function setCustomerGroupIdentifier($customer_group_identifier)
    {
        $this->customer_group_identifier = $customer_group_identifier;
    }

    /**
     * @return mixed
     */
    public function getStateName()
    {
        return $this->state_name;
    }

    /**
     * @param mixed $state_name
     */
    public function setStateName($state_name)
    {
        $this->state_name = $state_name;
    }

    /**
     * @return mixed
     */
    public function getCountryName()
    {
        return $this->country_name;
    }

    /**
     * @param mixed $country_name
     */
    public function setCountryName($country_name)
    {
        $this->country_name = $country_name;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }


    /**
     * @return string
     */
    public function getUserFirstName()
    {
        return $this->user_first_name;
    }

    /**
     * @param string $user_first_name
     */
    public function setUserFirstName($user_first_name)
    {
        $this->user_first_name = $user_first_name;
    }

    /**
     * @return string
     */
    public function getUserLastName()
    {
        return $this->user_last_name;
    }

    /**
     * @param string $user_last_name
     */
    public function setUserLastName($user_last_name)
    {
        $this->user_last_name = $user_last_name;
    }

    /**
     * @return mixed
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * @param mixed $salutation
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;
    }

    /**
     * @return string
     */
    public function getUserEmailIdentifier()
    {
        return $this->user_email_identifier;
    }

    /**
     * @param string $user_email_identifier
     */
    public function setUserEmailIdentifier($user_email_identifier)
    {
        $this->user_email_identifier = $user_email_identifier;
    }

    /**
     * @return mixed
     */
    public function getAcceptedTos()
    {
        return $this->accepted_tos;
    }

    /**
     * @param mixed $accepted_tos
     */
    public function setAcceptedTos($accepted_tos)
    {
        $this->accepted_tos = $accepted_tos;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /**
     * @param mixed $status_code
     */
    public function setStatusCode($status_code)
    {
        $this->status_code = $status_code;
    }

    /**
     * @return array
     */
    public function getAlerts()
    {
        return $this->alerts;
    }

    /**
     * @param array $alerts
     */
    public function setAlerts($alerts)
    {
        $this->alerts = $alerts;
    }

    /**
     * @return null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param null $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return mixed
     */
    public function getAddressLine1()
    {
        return $this->address_line_1;
    }

    /**
     * @param mixed $address_line_1
     */
    public function setAddressLine1($address_line_1)
    {
        $this->address_line_1 = $address_line_1;
    }

    /**
     * @return mixed
     */
    public function getAddressLine2()
    {
        return $this->address_line_2;
    }

    /**
     * @param mixed $address_line_2
     */
    public function setAddressLine2($address_line_2)
    {
        $this->address_line_2 = $address_line_2;
    }

    /**
     * @return mixed
     */
    public function getCityName()
    {
        return $this->city_name;
    }

    /**
     * @param mixed $city_name
     */
    public function setCityName($city_name)
    {
        $this->city_name = $city_name;
    }

    /**
     * @return mixed
     */
    public function getZipCode()
    {
        return $this->zip_code;
    }

    /**
     * @param mixed $zip_code
     */
    public function setZipCode($zip_code)
    {
        if($zip_code != 'false')
            $this->zip_code = $zip_code;
        else
            $this->zip_code = '';
    }

    /**
     * @return mixed
     */
    public function getStateCode()
    {
        return $this->state_code;
    }

    /**
     * @param mixed $state_code
     */
    public function setStateCode($state_code)
    {
        $this->state_code = $state_code;
    }

    /**
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * @param mixed $country_code
     */
    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * @param string $phone_number
     */
    public function setPhoneNumber($phone_number)
    {
        $this->phone_number = $phone_number;
    }

    /**
     * @return mixed
     */
    public function getFullName()
    {
        if(!isset($this->full_name) && strlen($this->full_name)==0)
        {
            if((isset($this->first_name) && strlen($this->full_name)!=0 )&& (isset($this->first_name) && strlen($this->full_name)!=0))
            {
                $this->full_name = $this->first_name." ".$this->last_name;
            }
        }
        return $this->full_name;
    }

    /**
     * @param mixed $full_name
     */
    public function setFullName($full_name)
    {
        $this->full_name = $full_name;
    }

    /**
     * @return bool
     */
    public function getImpersonated()
    {
        return $this->phone_number;
    }

    /**
     * @param bool $impersonated
     */
    public function setImpersonated($impersonated)
    {
        $this->impersonated = $impersonated;
    }

    /**
     * @return string
     */
    public function getUserIdentifier()
    {
        return $this->user_identifier;
    }

    /**
     * @param string $user_identifier
     */
    public function setUserIdentifier($user_identifier)
    {
        $this->user_identifier = $user_identifier;
    }

    /**
     * @return string
     */
    public function getUserTypeCode()
    {
        return $this->user_type_code;
    }

    /**
     * @param string $user_type_code
     */
    public function setUserTypeCode($user_type_code)
    {
        $this->user_type_code = $user_type_code;
    }

    /**
     * @return string
     */
    public function getAddressIdentifier()
    {
        return $this->address_identifier;
    }

    /**
     * @param string $address_identifier
     */
    public function setAddressIdentifier($address_identifier)
    {
        $this->address_identifier = $address_identifier;
    }

    /**
     * @return string
     */
    public function getJobTitle()
    {
        return $this->job_title;
    }

    /**
     * @param string $job_title
     */
    public function setJobTitle($job_title)
    {
        $this->job_title = $job_title;
    }

    /**
     * @return string
     */
    public function getContactPhoneNumber()
    {
        return $this->contact_phone_number;
    }

    /**
     * @param string $contact_phone_number
     */
    public function setContactPhoneNumber($contact_phone_number)
    {
        $this->contact_phone_number = $contact_phone_number;
    }

    /**
     * @return string
     */
    public function getFaxNumber()
    {
        return $this->fax_number;
    }

    /**
     * @param string $fax_number
     */
    public function setFaxNumber($fax_number)
    {
        $this->fax_number = $fax_number;
    }

}