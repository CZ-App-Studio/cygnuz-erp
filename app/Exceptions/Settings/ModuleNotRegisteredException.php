<?php

namespace App\Exceptions\Settings;

use Exception;

class ModuleNotRegisteredException extends Exception
{
    public function __construct(string $module, int $code = 0, ?Exception $previous = null)
    {
        $message = "Module '{$module}' is not registered in the settings system.";
        parent::__construct($message, $code, $previous);
    }
}
