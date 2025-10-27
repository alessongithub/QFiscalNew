<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();
        
        return view('landing', compact('plans'));
    }
}
