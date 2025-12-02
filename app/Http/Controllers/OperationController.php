<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class OperationController extends Controller{
    public function index($id){
        $machine = Machine::findOrFail($id);
         $tests = \App\Models\Test::all();
         return view('machines.operation', compact('machine', 'tests'));
    }    
}
