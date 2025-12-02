<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EspSensor;

class EspSensorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'test_name' => 'nullable|string',
            'brand' => 'nullable|string',
            'color_r' => 'nullable|string',
            'color_g' => 'nullable|string',
            'color_b' => 'nullable|string',
            'tem' => 'nullable|string',
            'sen_1' => 'nullable|string',
            'sen_2' => 'nullable|string',
            'sen_3' => 'nullable|string',
            'sen_4' => 'nullable|string',
            'sen_5' => 'nullable|string',
            'sen_6' => 'nullable|string',
        ]);

        $sensor = EspSensor::create($data);

        return response()->json(['success' => true, 'id' => $sensor->id]);
    }

    public function index(){
        $sensors = EspSensor::orderBy('id','desc')->paginate(10);
        return view('espsensor.index', compact('sensors'));
    }

     public function destroy($id)
    {
        $sensor = EspSensor::findOrFail($id);
        $sensor->delete();

        return redirect()->route('espsensor.index')->with('success','Sensor data deleted successfully.');
    }
}
