<?php

namespace App\Exceptions\Settings;

use Exception;

class SettingNotFoundException extends Exception
{
    public function __construct(string $key, int $code = 0, ?Exception $previous = null)
    {
        $message = "Setting with key '{$key}' not found.";
        parent::__construct($message, $code, $previous);
    }
}