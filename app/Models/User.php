<?php
namespace App\Models;
use App\Mapper\UserObjectFactory;
use Illuminate\Auth\UserInterface;

class User {

    private $user_id;
    private $salutation;
    private $first_name;
    private $last_name;
    private $email;
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

    public function _construct()
    {

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
     * @return mixed
     */

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param mixed $first_name
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param mixed $last_name
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
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
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
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
}