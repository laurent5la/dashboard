<?php
namespace App\Factory;

use JWTAuth;
use JWTFactory;

class JwtLoginDashboardFactory
{
    private static function createPayloadFromUserInfo($userInfo)
    {
        return JWTFactory::make([ 'user_token' => $userInfo['user_token']]);
    }

    public static function createToken($userInfo)
    {
        return JWTAuth::encode(self::createPayloadFromUserInfo($userInfo));
    }
}

