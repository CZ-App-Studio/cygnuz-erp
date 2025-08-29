<?php

namespace Modules\AICore\Exceptions;

use Exception;

class AIRateLimitException extends Exception
{
    protected $code = 429;
}
