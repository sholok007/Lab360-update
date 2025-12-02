<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\DeviceDataReceived;

class TestingViewController extends Controller
{
    public function index(){ return view('testing-view.index'); }

    public function receiveCommand(Request $request){
        return response()->json([
            'received'=>$request->all(),
            'status'=>'ok'
        ]);
    }
}