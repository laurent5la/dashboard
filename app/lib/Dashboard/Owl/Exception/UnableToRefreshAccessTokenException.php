<?php
namespace App\Lib\Dashboard\Owl\Exception;

class UnableToRefreshAccessTokenException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}