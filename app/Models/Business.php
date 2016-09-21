<?php
namespace App\Models;

class Business {

    private $duns;
    private $name;
    private $address;
    private $city;
    private $state;
    private $zip;
    private $country;
    private $verified;
    private $claimed;
    private $score;
    private $inquiry;
    private $event;
    private $phone_number;
    private $address_extended;
    private $business_id;
    private $enterprise_business_id;
    private $credibility_review_link;
    private $trade_reference;


    public function _construct()
    {

    }

    /**
     * @return mixed
     */
    public function getDuns()
    {
        return $this->duns;
    }

    /**
     * @param mixed $duns
     */
    public function setDuns($duns)
    {
        $this->duns = $duns;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param mixed $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getVerified()
    {
        return $this->verified;
    }

    /**
     * @param mixed $verified
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;
    }

    /**
     * @return mixed
     */
    public function getClaimed()
    {
        return $this->claimed;
    }

    /**
     * @param mixed $claimed
     */
    public function setClaimed($claimed)
    {
        $this->claimed = $claimed;
    }

    /**
     * @return mixed
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param mixed $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * @return mixed
     */
    public function getInquiry()
    {
        return $this->inquiry;
    }

    /**
     * @param mixed $inquiry
     */
    public function setInquiry($inquiry)
    {
        $this->inquiry = $inquiry;
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent($event)
    {
        $this->event = $event;
    }

    /**
     * @return mixed
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * @param mixed $phone_number
     */
    public function setPhoneNumber($phone_number)
    {
        $this->phone_number = $phone_number;
    }

    /**
     * @return mixed
     */
    public function getAddressExtended()
    {
        return $this->address_extended;
    }

    /**
     * @param mixed $address_extended
     */
    public function setAddressExtended($address_extended)
    {
        $this->address_extended = $address_extended;
    }

    /**
     * @return mixed
     */
    public function getBusinessId()
    {
        return $this->business_id;
    }

    /**
     * @param mixed $business_id
     */
    public function setBusinessId($business_id)
    {
        $this->business_id = $business_id;
    }

    /**
     * @return mixed
     */
    public function getEnterpriseBusinessId()
    {
        return $this->enterprise_business_id;
    }

    /**
     * @param mixed $enterprise_business_id
     */
    public function setEnterpriseBusinessId($enterprise_business_id)
    {
        $this->enterprise_business_id = $enterprise_business_id;
    }

    /**
     * @return mixed
     */
    public function getCredibilityReviewLink()
    {
        return $this->credibility_review_link;
    }

    /**
     * @param mixed $credibility_review_link
     */
    public function setCredibilityReviewLink($credibility_review_link)
    {
        $this->credibility_review_link = $credibility_review_link;
    }

    /**
     * @return mixed
     */
    public function getTradeReference()
    {
        return $this->trade_reference;
    }

    /**
     * @param mixed $trade_reference
     */
    public function setTradeReference($trade_reference)
    {
        $this->trade_reference = $trade_reference;
    }

}