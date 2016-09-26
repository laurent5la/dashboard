<?php
namespace App\Lib\Dashboard\Owl\Exception;

use App\Exceptions\ApplicationException;

class UnableToRefreshAccessTokenException extends ApplicationException
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}