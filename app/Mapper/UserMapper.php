<?php
namespace App\Mapper;

use App\Exceptions\InvalidUserTokenStatusException;
use App\Exceptions\NotFoundErrorException;
use App\Exceptions\ServerErrorException;
use App\Factory\UserObjectFactory;
use App\Factory\OwlFactory;
use App\Factory\JwtLoginDashboardFactory;
use App\Traits\Logging;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class UserMapper
{
    use Logging;

    public function __construct()
    {
        $this->config = app()['config'];
    }

    public function getUser($params)
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->retrieveUserDashboardInfo($params);
        return $userObject;
    }



    public function updateUserPersonalInformation($params)
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->updateUserPersonalInfo($params);
        return $userObject;
    }

    public function logoutUser()
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->logoutUser();
        return $userObject;
    }

    public function forgotPassword($params)
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->createByEmail($params);
        return $userObject;
    }

    public function changePassword($params)
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->changePassword($params);
        return $userObject;
    }

    private function formatStandardResponse(&$array, $status, $payload, $messages)
    {
        $array['status'] = $status;
        $array['payload'] = $payload;
        $array['messages'] = $messages;
    }
}
