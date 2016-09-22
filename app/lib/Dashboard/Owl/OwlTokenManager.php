<?php
namespace App\Lib\Dashboard\Owl;

use App\Factory\LogFactory;
use App\Lib\Dashboard\Owl\Exception\UnableToRefreshAccessTokenException;
use App\Traits\Logging;
use GuzzleHttp\Client as GuzzleClient;
use Cache;

class OwlTokenManager
{
    use Logging;

    private $owlConfig;
    private $cacheKeyForOWLToken;
    private $cacheKeyForOWLRefreshToken;
    private $clientID;
    private $clientSecret;
    private $guzzleClient;


    public function __construct()
    {
        $this->owlConfig = app()['config'];
        $this->clientID = $this->owlConfig->get('owl.client_id');
        $this->clientSecret = $this->owlConfig->get('owl.client_secret');
        $this->cacheKeyForOWLToken = 'OWL-TOKEN' . '-' . $this->clientID;
        $this->cacheKeyForOWLRefreshToken = 'OWL-REFRESH-TOKEN' . '-' . $this->clientID;
        $this->guzzleClient = new GuzzleClient();
    }

    public function getAccessToken()
    {
        if (Cache::has($this->cacheKeyForOWLToken)) {
            $this->info("retrieving access token from cache");
            return Cache::get($this->cacheKeyForOWLToken);
        } else {
            $this->info("refreshing access token");

            $accessToken = $this->refreshAccessToken();
            return $accessToken;
        }
    }

    public function refreshAccessToken()
    {
        $logTime = [];
        $logTime['start'] = time();

        $this->expireAccessTokenInCache();

        return $this->requestNewAccessToken();
    }

    private function expireAccessTokenInCache()
    {
        if (Cache::has($this->cacheKeyForOWLToken)) {
            Cache::forget($this->cacheKeyForOWLToken);
        }
    }

    private function requestNewAccessToken()
    {
        $tries = env('REFRESH_TRIES', 2);
        for ($i = 0; $i < $tries; $i++) {
            $params = array(
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientID,
                'client_secret' => $this->clientSecret
            );
            $accessTokenURL = $this->owlConfig->get('owl_endpoints.access_token');
            $request = $this->guzzleClient->post(env('OWL_API_URL').$accessTokenURL, array('body' => $params));
            $result = $request->getBody();
            $response = json_decode($result, true);
            if(isset($response['access_token'])) {
                Cache::put($this->cacheKeyForOWLToken, $response['access_token'], 24 * 60);
                return $response['access_token'];
            }
            $this->error('Unable to return Access Token from OWL. Attempt ' . strval($i + 1));
        }
        throw new UnableToRefreshAccessTokenException("Unable to refresh access token after $tries attempts.");
    }
}