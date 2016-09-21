<?php
namespace App\Models;

class Alert {

    private $customer_alert_identifier;
    private $customer_alert_preference_identifier;
    private $product_category_code;
    private $user_identifier;
    private $company_identifier;
    private $company_name;
    private $duns_number;
    private $customer_event_alert_identifier;
    private $alert_profile_code;
    private $category_code;
    private $type_code;
    private $alert_reference_code;
    private $alert_reference_identifier;
    private $message_text;
    private $read_indicator;
    private $email_sent_indicator;
    private $alert_generated_date_time;
    private $alert_expiry_date;
    private $is_active_indicator;
    private $created_by;
    private $created_date;
    private $last_modified_by;
    private $last_modified_date;

    /**
     * @return mixed
     */
    public function getCustomerAlertIdentifier()
    {
        return $this->customer_alert_identifier;
    }

    /**
     * @param mixed $customer_alert_identifier
     */
    public function setCustomerAlertIdentifier($customer_alert_identifier)
    {
        $this->customer_alert_identifier = $customer_alert_identifier;
    }

    /**
     * @return mixed
     */
    public function getCustomerAlertPreferenceIdentifier()
    {
        return $this->customer_alert_preference_identifier;
    }

    /**
     * @param mixed $customer_alert_preference_identifier
     */
    public function setCustomerAlertPreferenceIdentifier($customer_alert_preference_identifier)
    {
        $this->customer_alert_preference_identifier = $customer_alert_preference_identifier;
    }

    /**
     * @return mixed
     */
    public function getProductCategoryCode()
    {
        return $this->product_category_code;
    }

    /**
     * @param mixed $product_category_code
     */
    public function setProductCategoryCode($product_category_code)
    {
        $this->product_category_code = $product_category_code;
    }

    /**
     * @return mixed
     */
    public function getUserIdentifier()
    {
        return $this->user_identifier;
    }

    /**
     * @param mixed $user_identifier
     */
    public function setUserIdentifier($user_identifier)
    {
        $this->user_identifier = $user_identifier;
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
    public function getCompanyName()
    {
        return $this->company_name;
    }

    /**
     * @param mixed $company_name
     */
    public function setCompanyName($company_name)
    {
        $this->company_name = $company_name;
    }

    /**
     * @return mixed
     */
    public function getDunsNumber()
    {
        return $this->duns_number;
    }

    /**
     * @param mixed $duns_number
     */
    public function setDunsNumber($duns_number)
    {
        $this->duns_number = $duns_number;
    }

    /**
     * @return mixed
     */
    public function getCustomerEventAlertIdentifier()
    {
        return $this->customer_event_alert_identifier;
    }

    /**
     * @param mixed $customer_event_alert_identifier
     */
    public function setCustomerEventAlertIdentifier($customer_event_alert_identifier)
    {
        $this->customer_event_alert_identifier = $customer_event_alert_identifier;
    }

    /**
     * @return mixed
     */
    public function getAlertProfileCode()
    {
        return $this->alert_profile_code;
    }

    /**
     * @param mixed $alert_profile_code
     */
    public function setAlertProfileCode($alert_profile_code)
    {
        $this->alert_profile_code = $alert_profile_code;
    }

    /**
     * @return mixed
     */
    public function getCategoryCode()
    {
        return $this->category_code;
    }

    /**
     * @param mixed $category_code
     */
    public function setCategoryCode($category_code)
    {
        $this->category_code = $category_code;
    }

    /**
     * @return mixed
     */
    public function getTypeCode()
    {
        return $this->type_code;
    }

    /**
     * @param mixed $type_code
     */
    public function setTypeCode($type_code)
    {
        $this->type_code = $type_code;
    }

    /**
     * @return mixed
     */
    public function getAlertReferenceCode()
    {
        return $this->alert_reference_code;
    }

    /**
     * @param mixed $alert_reference_code
     */
    public function setAlertReferenceCode($alert_reference_code)
    {
        $this->alert_reference_code = $alert_reference_code;
    }

    /**
     * @return mixed
     */
    public function getAlertReferenceIdentifier()
    {
        return $this->alert_reference_identifier;
    }

    /**
     * @param mixed $alert_reference_identifier
     */
    public function setAlertReferenceIdentifier($alert_reference_identifier)
    {
        $this->alert_reference_identifier = $alert_reference_identifier;
    }

    /**
     * @return mixed
     */
    public function getMessageText()
    {
        return $this->message_text;
    }

    /**
     * @param mixed $message_text
     */
    public function setMessageText($message_text)
    {
        $this->message_text = $message_text;
    }

    /**
     * @return mixed
     */
    public function getReadIndicator()
    {
        return $this->read_indicator;
    }

    /**
     * @param mixed $read_indicator
     */
    public function setReadIndicator($read_indicator)
    {
        $this->read_indicator = $read_indicator;
    }

    /**
     * @return mixed
     */
    public function getEmailSentIndicator()
    {
        return $this->email_sent_indicator;
    }

    /**
     * @param mixed $email_sent_indicator
     */
    public function setEmailSentIndicator($email_sent_indicator)
    {
        $this->email_sent_indicator = $email_sent_indicator;
    }

    /**
     * @return mixed
     */
    public function getAlertGeneratedDateTime()
    {
        return $this->alert_generated_date_time;
    }

    /**
     * @param mixed $alert_generated_date_time
     */
    public function setAlertGeneratedDateTime($alert_generated_date_time)
    {
        $this->alert_generated_date_time = $alert_generated_date_time;
    }

    /**
     * @return mixed
     */
    public function getAlertExpiryDate()
    {
        return $this->alert_expiry_date;
    }

    /**
     * @param mixed $alert_expiry_date
     */
    public function setAlertExpiryDate($alert_expiry_date)
    {
        $this->alert_expiry_date = $alert_expiry_date;
    }

    /**
     * @return mixed
     */
    public function getIsActiveIndicator()
    {
        return $this->is_active_indicator;
    }

    /**
     * @param mixed $is_active_indicator
     */
    public function setIsActiveIndicator($is_active_indicator)
    {
        $this->is_active_indicator = $is_active_indicator;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * @param mixed $created_by
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
    }

    /**
     * @return mixed
     */
    public function getCreatedDate()
    {
        return $this->created_date;
    }

    /**
     * @param mixed $created_date
     */
    public function setCreatedDate($created_date)
    {
        $this->created_date = $created_date;
    }

    /**
     * @return mixed
     */
    public function getLastModifiedBy()
    {
        return $this->last_modified_by;
    }

    /**
     * @param mixed $last_modified_by
     */
    public function setLastModifiedBy($last_modified_by)
    {
        $this->last_modified_by = $last_modified_by;
    }

    /**
     * @return mixed
     */
    public function getLastModifiedDate()
    {
        return $this->last_modified_date;
    }

    /**
     * @param mixed $last_modified_date
     */
    public function setLastModifiedDate($last_modified_date)
    {
        $this->last_modified_date = $last_modified_date;
    }

}