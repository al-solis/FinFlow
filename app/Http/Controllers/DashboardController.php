<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\module;
use App\Models\sub_module;
class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }
}