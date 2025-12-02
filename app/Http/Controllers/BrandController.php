<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;
use App\Models\Brand;
use App\Models\Reagent;



class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::with('test')->paginate(10);
        $tests = Test::all();
        $reagents = Reagent::all();
        return view('brand.index', compact('brands','tests', 'reagents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'test_id' => 'required|exists:tests,id',
        ]);

        Brand::create([
            'name' => $request->name,
            'test_id' => $request->test_id
        ]);

        return redirect()->back()->with('success','Brand created successfully!');
    }

    public function destroy($id)
    {
        Brand::findOrFail($id)->delete();
        return redirect()->back()->with('success','Brand deleted successfully!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'test_id' => 'required|exists:tests,id',
        ]);

        $brand = Brand::findOrFail($id);
        $brand->update([
            'name' => $request->name,
            'test_id' => $request->test_id
        ]);

        return redirect()->back()->with('success','Brand updated successfully!');
    }


    public function assignReagents(Request $request)
    {
        $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'reagent_ids' => 'array'
        ]);

        $brand = Brand::findOrFail($request->brand_id);

        // পুরনো গুলো replace করে নতুন assign হবে
        $brand->reagents()->sync($request->reagent_ids);

        return redirect()->back()->with('success', 'Reagents assigned successfully!');
    }
}
