<?php

namespace App\Exceptions\Settings;

use Exception;

class InvalidSettingTypeException extends Exception
{
    public function __construct(string $key, string $expectedType, string $actualType, int $code = 0, ?Exception $previous = null)
    {
        $message = "Invalid type for setting '{$key}'. Expected {$expectedType}, got {$actualType}.";
        parent::__construct($message, $code, $previous);
    }
}
