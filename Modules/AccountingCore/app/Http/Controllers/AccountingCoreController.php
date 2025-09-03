<?php

namespace Modules\AccountingCore\app\Http\Controllers;

use App\Http\Controllers\Controller;

class AccountingCoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:accountingcore.access')->only(['index']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return redirect()->route('accountingcore.dashboard');
    }
}
