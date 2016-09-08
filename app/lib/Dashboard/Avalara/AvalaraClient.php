<?php

namespace App\Lib\Dashboard\Avalara;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\BadResponseException;
use App\Mapper\LogFactory;
use Config;

/**
 * Class AvalaraClient
 *
 * This class is used to create request for the Avalara Endpoints
 *
 * @package App\Lib\ECart\Avalara
 */

class AvalaraClient
{
    private $avalaraConfig;
    private $guzzleClient;

    /**
     * Class Constructor
     * Get avalara configurations , Guzzle Client
     * @use App\Lib\ECart\Avalara\AvalaraClient;
     * @author Kunal
     */

    public function __construct()
    {
        $this->avalaraConfig = app()['config'];
        $this->guzzleClient = new client();
        $this->guzzleClient->setBaseUrl($this->avalaraConfig->get('avalara.base_url'));
        $this->logFactory = new LogFactory();
        $this->setHeaders();
    }

    private function setHeaders($additions=[])
    {
        $avalaraAccountNumber = env("AVALARA_ACCOUNT_NUMBER");
        $avalaraLicenseKey = env("AVALARA_LICENSE_KEY");
        $authorizationHeader = $avalaraAccountNumber.':'.$avalaraLicenseKey;
        $headerArray = array_merge(
            array(
                'Authorization' => 'Basic '.base64_encode($authorizationHeader),
            ),
            $additions
        );

        $this->guzzleClient->setDefaultOption('headers', $headerArray);
    }

    /**
     * Form Guzzle Get Request for Avalara Address Validation
     * @param array $vars {
     *     @var string Line1 address line 1 of the user
     *     @var string Line2 address line 2 of the user
     *     @var string City city of the user
     *     @var string Region state of the user
     *     @var string PostalCode postal code of the user
     *     @var string Country country of the user
     * }
     *
     * @return JSON AvalaraResponse $response Validated Address Response from Avalara
     * @use Guzzle\Http\Client
     * @author Kunal
     */

    public function avalaraGetRequest($url, $vars)
    {
        try {
            $response = $this->avalaraGetRequestHelper($url, $vars);
            $this->logMessage['AvalaraClient->avalaraGetRequest']['Address_Validation_Response'] = $response;
            $this->logFactory->writeAvalaraLog($this->logMessage);

        } catch (BadResponseException $exception) {
            $response = $exception->getResponse()->getBody(true);
            $this->logMessage['AvalaraClient->avalaraGetRequest']['Address_Validation_Exception'] = $response;
            $this->logFactory->writeAvalaraLog($this->logMessage);
        }
        return $response;
    }

    /**
     * Get Validated Address from Avalara
     * @param array $vars {
     *     @var string Line1 address line 1 of the user
     *     @var string Line2 address line 2 of the user
     *     @var string City city of the user
     *     @var string Region state of the user
     *     @var string PostalCode postal code of the user
     *     @var string Country country of the user
     * }
     *
     * @return JSON AvalaraResponse $response Validated Address Response from Avalara
     * @use Guzzle\Http\Client
     * @author Kunal
     */

    public function avalaraGetRequestHelper($url, $vars)
    {
        try {
            $url = $url."Line1=".$vars['Line1']."&Line2=".$vars['Line2']."&City=".$vars['City']."&Region=".$vars['Region']."&PostalCode=".$vars['PostalCode']."&Country=".$vars['Country'];
            $this->guzzleClient->getConfig()->set('curl.options', array('body_as_string' => true));
            $request = $this->guzzleClient->get($url, array(), $vars);
            $this->logMessage['AvalaraClient->avalaraGetRequestHelper']['Address_Validation_Request'] = $url;
            $this->logFactory->writeAvalaraLog($this->logMessage);
            $response = $request->send()->json();
        } catch (BadResponseException $exception) {
            $response = $exception->getResponse()->getBody(true);
            $this->logMessage['AvalaraClient->avalaraGetRequestHelper']['Address_Validation_Exception'] = $response;
            $this->logFactory->writeAvalaraLog($this->logMessage);
        }
        return $response;
    }

    public function avalaraPostRequest($url, $params)
    {
        try {
            $response = $this->avalaraPostRequestHelper($url, $params);
        } catch (RequestException $e) {
            $response = $this->avalaraPostRequestHelper($url, $params);
            $this->logMessage['AvalaraClient->avalaraPostRequest']['Tax_Exception'] = $response;
            $this->logFactory->writeAvalaraLog($this->logMessage);
        }

        return $response;
    }

    public function avalaraPostRequestHelper($url, $params)
    {
        $fields_string = $params;
        $avalaraAccountNumber = env("AVALARA_ACCOUNT_NUMBER");
        $avalaraLicenseKey = env("AVALARA_LICENSE_KEY");
        $authorizationHeader = $avalaraAccountNumber.':'.$avalaraLicenseKey;
        $headerArray['Content-Type'] = 'application/json';
        $headerArray['Authorization'] = 'Basic '.base64_encode($authorizationHeader);

        $curlPost = curl_init();
        curl_setopt($curlPost,CURLOPT_URL, $url);
        curl_setopt($curlPost, CURLOPT_HTTPHEADER, array(
            'Authorization:'.$headerArray['Authorization'],
            'Content-Type:'.$headerArray['Content-Type']
        ));
        curl_setopt($curlPost,CURLOPT_POST, 1);
        curl_setopt($curlPost,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($curlPost, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curlPost);
        $result = json_decode($result, true);
        curl_close($curlPost);

        return $result;
    }
}