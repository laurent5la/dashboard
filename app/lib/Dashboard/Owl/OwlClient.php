<?php

namespace App\Lib\Owl;

use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use App\Mapper\LogFactory;
use Config;

class OwlClient
{
    private static $owlClient = NULL;
    private $owlConfig;
    private $guzzleClient = NULL;
    private $cacheKeyForOWLToken;
    private $cacheKeyForOWLRefreshToken;
    private $xSSLOptions = array('X-SSL' => 'yes');
    private $clientID;
    private $clientSecret;
    private $logFactory;
    private $attempts = 0;
    private $getRequestAttempts = 0;
    private $postRequestAttempts = 0;

    public static function getInstance() {
        if (null === static::$owlClient) {
            static::$owlClient = new OwlClient();
        }
        return static::$owlClient;
    }

    public function init()
    {
        $this->owlConfig = app()['config'];
        if (is_null($this->guzzleClient)) {
            $this->guzzleClient = new client();
        }

        $this->clientID = $this->owlConfig->get('owl.client_id');
        $this->clientSecret = $this->owlConfig->get('owl.client_secret');
        $this->cacheKeyForOWLToken = 'OWL-TOKEN' . '-' . $this->clientID;
        $this->cacheKeyForOWLRefreshToken = 'OWL-REFRESH-TOKEN' . '-' . $this->clientID;

        if (is_null($this->guzzleClient))
        {
            $this->logFactory = new LogFactory();
        }
    }

    /**
     * @param $accessToken
     * @param array $additions
     */
    public function setHeaders($accessToken, $additions=[])
    {
        $headerArray = array();
        $headerArray = array_merge(
            array(
                'access-token' => $accessToken,
            ),
            $this->xSSLOptions,
            $additions
        );

        $this->guzzleClient->setDefaultOption('headers', $headerArray);
    }

    /**
     * function to get an access token from cache or refresh the access token
     * @return null
     */
    public function getAccessToken()
    {
        $logFactory = new LogFactory();

        if (Cache::has($this->cacheKeyForOWLToken))
        {
            $logFactory->writeInfoLog("access token cached");
            return Cache::get($this->cacheKeyForOWLToken);
        }
        else
        {
            $logFactory->writeInfoLog("access token refreshed");
            $accessToken = $this->refreshAccessToken();
            return $accessToken;
        }
    }

    /**
     * function to refresh an expired access token
     */
    private function refreshAccessToken()
    {
        $this->init();
        $logFactory = new LogFactory();
        $logTime = array();
        $logTime['start'] = time();

        // removing expired token from the cache
        if (Cache::has($this->cacheKeyForOWLToken))
        {
            Cache::forget($this->cacheKeyForOWLToken);
        }
        error_log("refresh token 1");
        $params = array(
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientID,
            'client_secret' => $this->clientSecret
        );
        $accessTokenURL = $this->owlConfig->get('owl_endpoints.access_token');
        $request = $this->guzzleClient->post(env('OWL_API_URL').$accessTokenURL, array('body' => $params));
        $result = $request->getBody();
        $response = json_decode($result,true);

        error_log("refresh token 2");
        if(isset($response['access_token']))
        {
            Cache::put($this->cacheKeyForOWLToken, $response['access_token'], 24 * 60);
            return $response['access_token'];
        }
        else
        {
            if ($this->attempts >= 2)
            {
                $logFactory->writeErrorLog("Unable to return Access Token from OWL at least two times. Redirecting to 500");
                abort(500);
            }
            else
            {
                $logFactory->writeErrorLog("Unable to return Access Token from OWL.");
                $this->attempts++;
            }
        }
        return null;
    }

    public function owlGetRequest($url, $params, $userToken="")
    {

        $this->init();
        $accessToken = $this->getAccessToken();
        $logFactory = new LogFactory();
        error_log("url:".$url." - access token:". $accessToken);
        try
        {
            if($userToken){
                $this->setHeaders($accessToken, ['user-token'=>$userToken]);
            }
            else
                $this->setHeaders($accessToken, []);

            $response = $this->owlGetRequestHelper(env('OWL_API_URL').$url, $params);
        }
        catch (ClientException $e)
        {
            $logFactory->writeErrorLog("Error in OwlClient.php - OwlGetRequest - ".$e->getMessage());
        }

        return $response;
    }

    public function owlGetRequestHelper($url, $params, $userTokenInHeader=false)
    {
        $this->init();
        error_log("initializing ".$url);
        $logFactory = new LogFactory();
        $logTime = array();
        $logTime['start'] = time();

        try
        {
            $request = $this->guzzleClient->get($url, $params);
            $result = $request->getBody();
            $response = json_decode($result,true);

            if(isset($response['meta']['code']))
            {
                if($response['meta']['code']>400 && $response['meta']['code']<=500)
                {
                    if($response['meta']['code']==500 || $this->getRequestAttempts>=2)
                    {
                        $logFactory->writeErrorLog($response);

                        abort(500);
                    }

                    $this->getRequestAttempts = $this->getRequestAttempts + 1;

                    Cache::forget($this->cacheKeyForOWLToken);
                    $response = $this->owlGetRequest($url, $params, $userTokenInHeader);

                    $logMessage = array();
                    $logMessage['OwlClient->owlGetRequestHelper'] = "Invalid Access Token. Deleted from cache and got a new one.";
                    $logFactory->writeInfoLog($logMessage);
                }

                else if ($response['meta']['code'] == 400)
                {
                    $logMessage = array();
                    $logMessage['OwlClient->owlGetRequestHelper'] = $response;
                    $logFactory->writeInfoLog($logMessage);
                }


            }
        }
        catch (Exception $e)
        {
            if ($e->getCode() === 401) {
                Cache::forget($this->cacheKeyForOWLToken);
                $logFactory->writeErrorLog("getting a 401");
                $response = $this->owlGetRequest($url, $params, $userTokenInHeader);
            }
        }

        $logTime['stop'] = time();
        $logTime['elapsed'] = $logTime['stop'] - $logTime['start'];

        $logFactory->writeAPILog("GET", $logTime, $url, $params, $response);

        return $response;
    }

    public function owlPostRequest($url, $params)
    {
        $this->init();
        $accessToken = $this->getAccessToken();
        error_log("url:".$url." - access token:". $accessToken);
        try
        {
            if($accessToken){
                $this->setHeaders($accessToken, []);
            }
            else
                $this->logFactory->writeErrorLog("Error in OwlClient.php - OwlPostRequest - Missing Access Token");

            $response = $this->owlPostRequestHelper(env('OWL_API_URL').$url, $params);
        }
        catch (ClientException $e)
        {
            $this->logFactory->writeErrorLog("Error in OwlClient.php - OwlGetRequest - ".$e->getMessage());
        }

        return $response;
    }

    public function owlPostRequestHelper($url, $params)
    {
        $this->init();
        $logFactory = new LogFactory();

        $logTime = array();
        $logTime['start'] = time();

        try {
            $request = $this->guzzleClient->post($url, array('body' => $params));
            $result = $request->getBody();
            $response = json_decode($result,true);

            if(isset($response['meta']['code'])) {
                if($response['meta']['code'] == 500) {
                    if($this->postRequestAttempts>=2) {
                        $logFactory->writeErrorLog($response);
                        abort(500);
                    }
                    $this->postRequestAttempts = $this->postRequestAttempts + 1;
                    Cache::forget($this->cacheKeyForOWLToken);
                    $response = $this->owlPostRequest($url, $params);

                    $logMessage = array();
                    $logMessage['OwlClient->owlPostRequestHelper'] = "Invalid Access Token. Deleted from cache and got a new one.";
                    $logFactory->writeInfoLog($logMessage);
                } else if ($response['meta']['code'] == 400) {
                    $logMessage = array();
                    $logMessage['OwlClient->owlPostRequestHelper'] = $response;
                    $logFactory->writeInfoLog($logMessage);
                }
            }
        } catch (Exception $e) {
            if ($e->getCode() === 401) {
                Cache::forget($this->cacheKeyForOWLToken);
                $logFactory->writeErrorLog("getting a 401");
                $response = $this->owlPostRequest($url, $params);
            }
        }
        $logTime['stop'] = time();
        $logTime['elapsed'] = $logTime['stop'] - $logTime['start'];
        $logFactory->writeAPILog("POST", $logTime, $url, $params, $response);

        return $response;
    }
}