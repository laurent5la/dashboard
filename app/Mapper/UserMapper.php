<?php
namespace App\Mapper;

class UserMapper
{
    public function getUser($params)
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->retrieveUserInfo($params);
        return $userObject;
    }

    public function setUser($params)
    {
        $userFactory = new UserObjectFactory();
        $userObject = $userFactory->storeUserInfo($params);
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
}