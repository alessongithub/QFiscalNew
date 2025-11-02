<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        // Buscar apenas planos ativos
        $plans = Plan::where('active', true)
            ->whereNull('deleted_at') // Excluir soft deletes
            ->orderBy('price')
            ->get();
        
        return view('landing', compact('plans'));
    }
}
