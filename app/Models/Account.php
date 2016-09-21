<?php
namespace App\Models;

class Account {

    private $account_id;
    private $entitled_user_identifier;
    private $account_name;
    private $subscriber_id;
    private $user_profile;
    private $company_identifier;
    private $user_role_code;
    private $is_active;

    /**
     * @return mixed
     */
    public function getAccountId()
    {
        return $this->account_id;
    }

    /**
     * @param mixed $account_id
     */
    public function setAccountId($account_id)
    {
        $this->account_id = $account_id;
    }

    /**
     * @return mixed
     */
    public function getEntitledUserIdentifier()
    {
        return $this->entitled_user_identifier;
    }

    /**
     * @param mixed $entitled_user_identifier
     */
    public function setEntitledUserIdentifier($entitled_user_identifier)
    {
        $this->entitled_user_identifier = $entitled_user_identifier;
    }

    /**
     * @return mixed
     */
    public function getAccountName()
    {
        return $this->account_name;
    }

    /**
     * @param mixed $account_name
     */
    public function setAccountName($account_name)
    {
        $this->account_name = $account_name;
    }

    /**
     * @return mixed
     */
    public function getSubscriberId()
    {
        return $this->subscriber_id;
    }

    /**
     * @param mixed $subscriber_id
     */
    public function setSubscriberId($subscriber_id)
    {
        $this->subscriber_id = $subscriber_id;
    }

    /**
     * @return mixed
     */
    public function getUserProfile()
    {
        return $this->user_profile;
    }

    /**
     * @param mixed $user_profile
     */
    public function setUserProfile($user_profile)
    {
        $this->user_profile = $user_profile;
    }

    /**
     * @return mixed
     */
    public function getCompanyIdentifier()
    {
        return $this->company_identifier;
    }

    /**
     * @param mixed $company_identifier
     */
    public function setCompanyIdentifier($company_identifier)
    {
        $this->company_identifier = $company_identifier;
    }

    /**
     * @return mixed
     */
    public function getUserRoleCode()
    {
        return $this->user_role_code;
    }

    /**
     * @param mixed $user_role_code
     */
    public function setUserRoleCode($user_role_code)
    {
        $this->user_role_code = $user_role_code;
    }

    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * @param mixed $is_active
     */
    public function setIsActive($is_active)
    {
        $this->is_active = $is_active;
    }

}