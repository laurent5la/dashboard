<?php
namespace App\Lib\Dashboard\Helper;

/**
 * Class CrossCookieAbstract
 *
 * An abstract class that lays the foundation for implementing CrossCookie functionality
 */
abstract class CrossCookieAbstract
{
    private $microSecConversionFactor = 1000000; //1 second represented in microseconds
    private $milliSecConversionFactor = 1000;    //1 second represented in milliseconds
    private $secConversionFactor      = 60;      //1 minute represented in seconds
    /**
     * @var string
     * Default hashing algorithm to be used for signing cookie values
     */
    protected $hashingAlgorithm = 'sha256';
    /**
     * @var int
     * Time to live in minutes for Auth Cookie
     */
    protected $ttlInMinutes = 20;
    /**
     * @var string
     * Cookie value separator, it separates the unsigned and signed values
     */
    protected $cookieValueSeparator = ',';
    /**
     * @var array
     * Names of the three cookies used in the CrossCookie system
     */
    protected $cookieNames = [
        'AUTH' => 'dandb_auth',
        'USER_TOKEN' => 'dandb_token',
        'USER_REFRESH_TOKEN' => 'dandb_refresh_token',
        'SHOPPING_CART' =>"shopping_cart",
        'PROMO_CODE' => "promoCode"
    ];
    protected $OWLUserToken;
    protected $OWLUserRefreshToken;
    protected $Cart;
    /**
     * @var string
     * Name of the ENV variable set in $_SERVER for getting the secret key, which is used to sign the cookie values
     */
    private $secretKeyEnvVar = 'DBCC_SECRET_AUTH_KEY';

    /**
     * Sets the three cookies required for cross cookie logic to work
     */
    protected abstract function setCrossCookies();

    /**
     * Sets cookie for cart.
     */
    protected abstract function setCartCrossCookie();

    /**
     * Unsets cookie for cart.
     */
    protected abstract function unSetCartCrossCookie();

    /**
     * Makes an OWL call and expires the OWL token
     */
    protected abstract function unsetOWLUserToken();

    /**
     * @return int
     * Gets the Time to live in microseconds
     */
    private function getTTLInMS()
    {
        return ($this->ttlInMinutes * $this->secConversionFactor * $this->microSecConversionFactor);
    }

    /**
     * @param $cookieName
     * @return array
     * Helper function that reads the cookie value into an Array
     */
    private function readCookieComponentsIntoArray($cookieName)
    {
        if (isset($_COOKIE[$cookieName])) {
            $cookieValue = $_COOKIE[$cookieName];
            $arrValues = explode($this->cookieValueSeparator, $cookieValue);
            if (count($arrValues) == 2) {
                return $arrValues;
            }
        }
        return [];
    }

    /**
     * @param $stringOne
     * @param $stringTwo
     * @return bool
     * Helper function that checks if two string match in value
     */
    private function doStringsMatch($stringOne, $stringTwo)
    {
        return (strcmp(trim($stringOne), trim($stringTwo)) == 0);
    }

    /**
     * @return null|string
     * Helper function that reads the secret key value from $_SERVER
     */
    protected function getSecretKey()
    {
        return isset($_SERVER[$this->secretKeyEnvVar]) ? $_SERVER[$this->secretKeyEnvVar] : '';
    }

    /**
     * @return mixed
     * Helper function that returns the current timestamp in milliseconds
     */
    protected function getCurrentTimestampInMS()
    {
        $timeInSeconds = microtime(true);
        return round($timeInSeconds * $this->milliSecConversionFactor);
    }

    /**
     * @return mixed
     * Helper function that returns the cookie expiration time in milliseconds
     */
    protected function getExpirationTimestampInMS()
    {
        return ($this->getCurrentTimestampInMS() + $this->getTTLInMS());
    }

    /**
     * @return mixed
     * Helper function that returns the cookie expiration time in seconds
     */
    protected function getCookieExpirationTime()
    {
        return time() + ($this->ttlInMinutes * $this->secConversionFactor);
    }

    /**
     * @param $unsignedString
     * @return string
     * Helper function that computes the cookie value using the unsigned and signed values
     */
    protected function generateSignedCookie($unsignedString)
    {
        return $unsignedString . $this->cookieValueSeparator . $this->createSignedString($unsignedString);
    }

    /**
     * @param $unsignedString
     * @return signed string
     * Gets the signed value of a string using hashing and secret key
     */
    protected function createSignedString($unsignedString)
    {
        return hash($this->hashingAlgorithm, $unsignedString . $this->getSecretKey());
    }

    /**
     * @param $cookieName
     * @return string|null
     * Reads the unsigned component of a cookie value
     */
    protected function readUnsignedStringFromCookie($cookieName)
    {
        if (count($arrValue = $this->readCookieComponentsIntoArray($cookieName)) != 0) {
            return $arrValue[0];
        }
        return null;
    }

    /**
     * @param $cookieName
     * @return null
     * Reads the signed component of a cookie value
     */
    protected function readSignedStringFromCookie($cookieName)
    {
        if ($arrValue = $this->readCookieComponentsIntoArray($cookieName)) {
            return $arrValue[1];
        }
        return null;
    }

    /**
     * @return boolean
     * This function runs the first check (isLoggedIn) to ensure Auth cookie is valid
     */
    protected function isAuthCookieValid()
    {
        $cookieName = $this->cookieNames['AUTH'];
        $unsigned = $this->readUnsignedStringFromCookie($cookieName);
        $signed = $this->readSignedStringFromCookie($cookieName);
        if (isset($unsigned) && (is_numeric($unsigned))) {
            if ($unsigned > $this->getCurrentTimestampInMS()) {
                if ($this->doStringsMatch($this->createSignedString($unsigned), $signed)) {
                    return true;
                }
            }
        }

        $this->unsetCookie($cookieName);
        return false;
    }

    /**
     * @return boolean
     * This function checks if user token cookie is valid
     */
    protected function isOWLUserTokenValid()
    {
        $cookieName = $this->cookieNames['USER_TOKEN'];
        $unsigned = $this->readUnsignedStringFromCookie($cookieName);
        $signed = $this->readSignedStringFromCookie($cookieName);
        if (isset($unsigned) && is_string($unsigned)) {
            if ($this->doStringsMatch($this->createSignedString($unsigned), $signed)) {
                return true;
            }
        }

        $this->unsetCookie($cookieName);
        return false;
    }

    /**
     * @return boolean
     * * This function checks if user refresh token cookie is valid
     */
    protected function isOWLRefreshUserTokenValid()
    {
        $cookieName = $this->cookieNames['USER_REFRESH_TOKEN'];
        $unsigned = $this->readUnsignedStringFromCookie($cookieName);
        $signed = $this->readSignedStringFromCookie($cookieName);
        if (isset($unsigned) && is_string($unsigned)) {
            if ($this->doStringsMatch($this->createSignedString($unsigned), $signed)) {
                return true;
            }
        }

        $this->unsetCookie($cookieName);
        return false;
    }

    /**
     * Unsets the three cross cookies
     */
    protected function unsetCrossCookies()
    {
        foreach ($this->cookieNames as $key => $cookieName) {
            $this->unsetCookie($cookieName);
        }
    }

    /**
     * @param $cookieName
     * Unsets a cookie by expiring it
     */
    private function unsetCookie($cookieName)
    {
        if (isset($_COOKIE[$cookieName])) {
            setcookie($cookieName, '', time() - 3600, '/');
        }
    }

    /**
     * @return bool
     * Determines if a user is logged in
     */
    public function isLoggedIn()
    {
        return $this->isAuthCookieValid() && $this->isOWLUserTokenValid() && $this->isOWLRefreshUserTokenValid();
    }

    /**
     * Logs the user in
     */
    public function login($userToken = "", $userRefreshToken = "")
    {
        if ($userToken && $userRefreshToken) {
            $this->OWLUserToken = $userToken;
            $this->OWLUserRefreshToken = $userRefreshToken;
        }
        else {
            $this->OWLUserToken = $this->getUserToken();
            $this->OWLUserRefreshToken = $this->getUserRefreshToken();
        }

        $this->setCrossCookies();
    }

    /*
     * Set the shopping cart cookie
     */
    public function shoppingCartCookie($cart)
    {
        if($cart)
        {
            $this->Cart = $cart;
            $this->setCartCrossCookie();
        }
        else
        {
            $this->unSetCartCrossCookie();
        }
    }

    /**
     * This function removes Shopping Cart Cookie.
     */

    public function removeShoppingCartCookie()
    {
        $this->unSetCartCrossCookie();
    }


    /**
     * Logs the user out
     */
    public function logout()
    {
        $this->unsetOWLUserToken();
        $this->unsetCrossCookies();
    }

    /**
     * @return null|string
     */
    public function getUserToken() {
        $cookieName = $this->cookieNames['USER_TOKEN'];
        return $this->readUnsignedStringFromCookie($cookieName);
    }

    public function getPromotionCode()
    {
        $cookieName = $this->cookieNames['PROMO_CODE'];
        if(isset($_COOKIE[$cookieName]))
            return $_COOKIE[$cookieName];

        return null;
    }

    /**
     * @return null|string
     */
    public function getUserRefreshToken() {
        $cookieName = $this->cookieNames['USER_REFRESH_TOKEN'];
        return $this->readUnsignedStringFromCookie($cookieName);
    }
}