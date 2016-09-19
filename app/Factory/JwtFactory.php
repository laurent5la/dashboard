<?php
namespace App\Factory;

use Tymon\JWTAuth\Facades\JWTAuth;

class JwtFactory
{
    private static function createPayloadFromUserInfo($userInfo)
    {
        return JWTAuth::make($userInfo);
    }

    public static function createToken($userInfo)
    {
        return JWTAuth::encode(self::createPayloadFromUserInfo($userInfo));
    }
}

