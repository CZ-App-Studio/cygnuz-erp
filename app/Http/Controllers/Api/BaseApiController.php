<?php

namespace App\Http\Controllers\Api;

use App\ApiClasses\Success;
use App\Http\Controllers\Controller;

class BaseApiController extends Controller
{

  public function checkDemoMode()
  {
    return Success::response(env('APP_DEMO'));
  }
}
