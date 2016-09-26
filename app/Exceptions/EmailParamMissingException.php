<?php
namespace App\Exceptions;


class EmailParamMissingException extends ApplicationException
{
    public function __construct($message)
    {
        parent::__construct($message, 403);
    }
}