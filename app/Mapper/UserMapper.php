<?php
namespace App\Mapper;

class UserMapper
{
    public function getUser($params)
    {
        $userFactory = new UserFactory();
        $userObject = $userFactory->retrieveUserInfo($params);
        return $userObject;
    }

    public function setUser($params)
    {
        $userFactory = new UserFactory();
        $userObject = $userFactory->storeUserInfo($params);
        return $userObject;
    }

    public function updateUserPersonalInformation($params)
    {
        $userFactory = new UserFactory();
        $userObject = $userFactory->updateUserPersonalInfo($params);
        return $userObject;
    }

    public function logoutUser()
    {
        $userFactory = new UserFactory();
        $userObject = $userFactory->logoutUser();
        return $userObject;
    }

    public function forgotPassword($params)
    {
        $userFactory = new UserFactory();
        $userObject = $userFactory->createByEmail($params);
        return $userObject;
    }

    public function changePassword($params)
    {
        $userFactory = new UserFactory();
        $userObject = $userFactory->changePassword($params);
        return $userObject;
    }
}
