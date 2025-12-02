<?php

namespace App\Http\Controllers;

use App\Models\Reagent;
use Illuminate\Http\Request;

class ReagentController extends Controller
{
    public function index()
    {
        $reagents = Reagent::orderBy('id','desc')->paginate(10);
        return view('reagents.index', compact('reagents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:reagents,name'
        ]);

        Reagent::create([
            'name' => $request->name
        ]);

        return redirect()->back()->with('success','Reagent created successfully!');
    }

    public function update(Request $request, Reagent $reagent)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:reagents,name,'.$reagent->id
        ]);

        $reagent->update([
            'name' => $request->name
        ]);

        return redirect()->back()->with('success','Reagent updated successfully!');
    }

    public function destroy(Reagent $reagent)
    {
        $reagent->delete();
        return redirect()->back()->with('success','Reagent deleted successfully!');
    }
}

