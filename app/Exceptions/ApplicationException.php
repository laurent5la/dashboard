<?php
namespace App\Exceptions;

class ApplicationException extends \Exception
{
    public function __construct($message, $code = null)
    {
        if (is_null($code)) {
            parent::__construct($message);
        } else {
            parent::__construct($message, $code);
        }
    }

    public function getJson()
    {
        return json_encode([
            "message" => $this->message,
            "code" => $this->code,
            "file" => $this->file,
            "line" => $this->line
        ]);
    }
}