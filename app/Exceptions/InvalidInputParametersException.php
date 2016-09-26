<?php
namespace App\Exceptions;


class InvalidInputParametersException extends ApplicationException
{
    public function __construct($message)
    {
        parent::__construct($message, 403);
    }
}