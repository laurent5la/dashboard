<?php
namespace App\Exceptions;

abstract class ApplicationException extends \Exception
{
    /**
     * Returns json string with message, code, file, and line.
     *
     * @return string
     * @author mvalenzuela
     * @since 16.12
     */
    public function getJson()
    {
        return json_encode([
            "message" => $this->message,
            "code" => $this->code,
            "file" => $this->file,
            "line" => $this->line
        ]);
    }

    public function getArray()
    {
        return [
            "message" => $this->message,
            "code" => $this->code,
            "file" => $this->file,
            "line" => $this->line
        ];
    }
}