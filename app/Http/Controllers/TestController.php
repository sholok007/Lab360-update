<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;

class TestController extends Controller{

     public function index()
    {
        $tests = Test::orderBy('id','desc')->paginate(10);
        return view('tests.index', compact('tests'));
    }
    
    public function create(){
        return view('tests.create');
    }

  
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        Test::create([
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'Test created successfully!');
    }

      public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $test = Test::findOrFail($id);
        $test->update(['name' => $request->name]);

        return redirect()->back()->with('success','Test updated successfully!');
    }

     public function destroy($id)
    {
        $test = Test::findOrFail($id);
        $test->delete();

        return redirect()->back()->with('success','Test deleted successfully!');
    }
}
