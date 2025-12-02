<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
     public function index($id){
        $machine = Machine::findOrFail($id);
        return view('machines.dashboard', compact('machine'));
    } 
}



