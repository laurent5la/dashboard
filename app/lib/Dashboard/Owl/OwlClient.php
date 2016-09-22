<?php

namespace App\Lib\Dashboard\Owl;

use App\Traits\Logging;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Cache;
use Config;

class OwlClient
{
    use Logging;

    private static $owlClient;
    /** @var  OwlTokenManager */
    private $owlTokenManager;
    private $owlConfig;
    /** @var  GuzzleClient */
    private $guzzleClient;
    private $xSSLOptions = array('X-SSL' => 'yes');
    private $clientID;
    private $clientSecret;
    private $getRequestAttempts = 0;
    private $postRequestAttempts = 0;

    /**
     * @return OwlClient
     */
    public static function getInstance() {
        if (is_null(self::$owlClient)) {
            self::$owlClient = new OwlClient();
        }
        return self::$owlClient;
    }

    public function init()
    {
        $this->owlConfig = app()['config'];
        if (is_null($this->guzzleClient)) {
            $this->guzzleClient = new GuzzleClient();
        }

        $this->clientID = $this->owlConfig->get('owl.client_id');
        $this->clientSecret = $this->owlConfig->get('owl.client_secret');
        $this->owlTokenManager = new OwlTokenManager();
    }

    /**
     * @param $accessToken
     * @param array $additions
     */
    public function setHeaders($accessToken, $additions=[])
    {
        $headerArray = array_merge(
            [
                'access-token' => $accessToken,
            ],
            $this->xSSLOptions,
            $additions
        );

        $this->guzzleClient->setDefaultOption('headers', $headerArray);
    }


    public function owlGetRequest($url, $params, $userToken="")
    {

        $this->init();
        $accessToken = $this->owlTokenManager->getAccessToken();
        error_log("url:".$url." - access token:". $accessToken);
        try {
            if($userToken) {
                $this->setHeaders($accessToken, ['user-token'=>$userToken]);
            } else
                $this->setHeaders($accessToken, []);

            $response = $this->owlGetRequestHelper(env('OWL_API_URL').$url, $params);
        }
        catch (ClientException $e) {
            $$this->error("Error in OwlClient.php - OwlGetRequest - ".$e->getMessage());
            $response = [];
        }
        return $response;
    }

    public function owlGetRequestHelper($url, $params, $userTokenInHeader=false)
    {
        $this->init();
        error_log("initializing ".$url);
        $logTime = array();
        $logTime['start'] = time();

        try {
            $request = $this->guzzleClient->get($url, $params);
            $result = $request->getBody();
            $response = json_decode($result, true);

            if(isset($response['meta']['code'])) {
                if($response['meta']['code'] > 400 && $response['meta']['code'] <= 500) {
                    if($response['meta']['code'] == 500 || $this->getRequestAttempts >= 2) {
                        $this->error($response);
                        abort(500);
                    }

                    $this->getRequestAttempts = $this->getRequestAttempts + 1;

                    $this->owlTokenManager->refreshAccessToken();
                    $response = $this->owlGetRequest($url, $params, $userTokenInHeader);

                    $logMessage = array();
                    $logMessage['OwlClient->owlGetRequestHelper'] = "Invalid Access Token. Deleted from cache and got a new one.";
                    $this->info($logMessage);
                } else if ($response['meta']['code'] == 400) {
                    $logMessage = array();
                    $logMessage['OwlClient->owlGetRequestHelper'] = $response;
                    $this->info($logMessage);
                }
            }
        }
        catch (\Exception $e) {
            if ($e->getCode() === 401) {
                $this->owlTokenManager->refreshAccessToken();
                $this->error("getting a 401");
                $response = $this->owlGetRequest($url, $params, $userTokenInHeader);
            }
        }

        $logTime['stop'] = time();
        $logTime['elapsed'] = $logTime['stop'] - $logTime['start'];

        $this->api("GET", $logTime, $url, $params, $response);

        return $response;
    }

    public function owlPostRequest($url, $params)
    {
        $this->init();
        $accessToken = $this->owlTokenManager->getAccessToken();
        error_log("url:".$url." - access token:". $accessToken);
        try {
            if($accessToken) {
                $this->setHeaders($accessToken, []);
            } else {
                $this->error("Error in OwlClient.php - OwlPostRequest - Missing Access Token");
            }
            $response = $this->owlPostRequestHelper(env('OWL_API_URL').$url, $params);
        } catch (ClientException $e) {
            $this->error("Error in OwlClient.php - OwlGetRequest - ".$e->getMessage());
        }
        if (isset($response)) {
            return $response;
        }
        return [];
    }

    public function owlPostRequestHelper($url, $params)
    {
        $this->init();

        $logTime = array();
        $logTime['start'] = time();

        try {
            $request = $this->guzzleClient->post($url, array('body' => $params));
            $result = $request->getBody();
            $response = json_decode($result,true);

            if(isset($response['meta']['code'])) {
                if($response['meta']['code'] == 500) {
                    if($this->postRequestAttempts>=2) {
                        $this->error($response);
                        abort(500);
                    }
                    $this->postRequestAttempts = $this->postRequestAttempts + 1;
                    $this->owlTokenManager->refreshAccessToken();
                    $response = $this->owlPostRequest($url, $params);

                    $logMessage = array();
                    $logMessage['OwlClient->owlPostRequestHelper'] = "Invalid Access Token. Deleted from cache and got a new one.";
                    $this->info($logMessage);
                } else if ($response['meta']['code'] == 400) {
                    $logMessage = array();
                    $logMessage['OwlClient->owlPostRequestHelper'] = $response;
                    $this->info($logMessage);
                }
            }
        } catch (\Exception $e) {
            if ($e->getCode() === 401) {
                $this->owlTokenManager->refreshAccessToken();
                $this->error("getting a 401");
                $response = $this->owlPostRequest($url, $params);
            }
        }
        $logTime['stop'] = time();
        $logTime['elapsed'] = $logTime['stop'] - $logTime['start'];
        $this->api("POST", $logTime, $url, $params, $response);

        return $response;
    }

    public function isValidEndpoint($url)
    {
        $getOWLEndpoints = Config::get("owl_endpoints");
        if(in_array($url,$getOWLEndpoints))
            return true;
        return false;
    }
}