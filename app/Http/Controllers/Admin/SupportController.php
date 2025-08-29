<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SupportController extends Controller
{
  public function index()
  {
    return view('admin.support.index');
  }
}
