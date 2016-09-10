<?php
namespace App\Lib\Dashboard\Helper;

use App\Mapper\OwlFactory;
use App\Lib\Dashboard\Owl\OwlClient;

class CrossCookie extends CrossCookieAbstract
{
    public function __construct()
    {

    }

    /**
     * @param $cookieName
     * @param $cookieValue
     * Sets PHP cookie
     */
    private function setPHPCookie($cookieName, $cookieValue)
    {
        setcookie($cookieName, $cookieValue, $this->getCookieExpirationTime(), '/');
    }

    /**
     * @param $cookieName
     * Expires PHP cookie
     */
    private function unSetPHPCookie($cookieName)
    {
        setcookie($cookieName, "", $this->getCookieExpirationTime() - (3600), '/');
    }


    /**
     * Sets AUTH cookie
     * This is the master user session cookie. It holds the session expiration timestamp in plain text and signed form.
     */
    private function setAuthCookie()
    {
        $unsignedValue = $this->getExpirationTimestampInMS();
        $cookieValue = $this->generateSignedCookie($unsignedValue);
        $this->setPHPCookie($this->cookieNames['AUTH'], $cookieValue);
    }

    /**
     * Sets Token Cookie
     * This is the first of the OWL Token cookies. It holds the OWL user token in plain text and signed form.
     */
    private function setUserTokenCookie()
    {
        if (!empty($this->OWLUserToken)) {
            $cookieValue = $this->generateSignedCookie($this->OWLUserToken);
            $this->setPHPCookie($this->cookieNames['USER_TOKEN'], $cookieValue);
        }

    }

    /**
     * Sets Refresh Token cookie
     * This is the second of the OWL Token cookies. It holds the OWL (user) refresh token in plain text and signed form.
     */
    private function setUserRefreshTokenCookie()
    {
        if (!empty($this->OWLUserRefreshToken)) {
            $cookieValue = $this->generateSignedCookie($this->OWLUserRefreshToken);
            $this->setPHPCookie($this->cookieNames['USER_REFRESH_TOKEN'], $cookieValue);
        }
    }

    /**
     * Expires the OWL token
     */
    protected function unsetOWLUserToken()
    {
        $owlFactory = new OwlFactory();
        $owlFactory->userLogout(/*user token?*/);
    }

    /**
     * Sets the three cross cookies
     */
    protected function setCrossCookies()
    {
        $this->setAuthCookie();
        $this->setUserTokenCookie();
        $this->setUserRefreshTokenCookie();
    }

    /**
     * Sets the shopping cart cookie
     */
    protected function setCartCrossCookie()
    {
        $this->setPHPCookie($this->cookieNames['SHOPPING_CART'],$this->Cart);
    }

    /**
     * Expires the shopping cart cookie
     */
    protected function unSetCartCrossCookie()
    {
        $this->unSetPHPCookie($this->cookieNames['SHOPPING_CART']);
    }
}