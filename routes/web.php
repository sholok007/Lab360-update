<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EspSensorController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ESPSetupController;
use App\Http\Controllers\ReagentController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\MachineDataController;
use App\Http\Controllers\MachineCalibrateController;
use App\Http\Controllers\MachineSetupController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenualTestController;
use App\Http\Controllers\TestingViewController;
use App\Http\Controllers\DeviceController;
use App\Events\DeviceDataReceived;

// ==============================================
// 🔐 Auth Routes
// ==============================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'signup'])->name('signup.submit');

// ==============================================
// 🧠 Protected Routes
// ==============================================
Route::middleware(['auth'])->group(function () {

    // Dashboard / Home
    Route::get('/', [HomeController::class, 'index'])->name('home.home');

    // ==========================================
    // 💻 Machine Routes (for Proprietor / User)
    // ==========================================
    Route::get('/machines', [MachineController::class, 'index'])->name('machines.index');
    Route::post('/machines', [MachineController::class, 'store'])->name('machines.store');
    Route::put('/machines/{id}', [MachineController::class, 'update'])->name('machines.update');
    Route::delete('/machines/{id}', [MachineController::class, 'destroy'])->name('machines.destroy');
    Route::get('/machines/setup/{id}', [MachineController::class, 'setup'])->name('machines.setup');

   //Machine-Calibrate
    Route::get('/machines/machine-calibrate-setup/{id}', [MachineCalibrateController::class, 'index'])->name('machines.calibrate.setup');    

    //machine-Setup
    Route::get('/machines/machine-setup/{id}', [MachineSetupController::class, 'index'])->name('machines.machine-setup');
    Route::post('/machines/machine-setup/save-drain', [MachineSetupController::class, 'saveDrain'])->name('machines.saveDrain');
    Route::post('/machines/machine-setup/save-rodi', [MachineSetupController::class, 'saveRodi'])->name('machines.saveRodi');
    Route::post('/machines/machine-setup/save-others', [MachineSetupController::class, 'saveOthers'])->name('machines.saveOthers');

    //machine-Setup
    Route::get('/machines/operation/{id}', [OperationController::class, 'index'])->name('machines.operation'); 
   
   
    //menual test
    Route::get('/machines/menual-test/{id}', [MenualTestController::class, 'index'])->name('machines.menual-test'); 

    // Machine Data
    Route::get('/machine-data', [MachineDataController::class, 'index'])->name('machine_data.index');
    Route::post('/machine-data/save', [MachineDataController::class, 'store'])->name('machine_data.store');

    //Re-Agent data route
    Route::post('/machines/save-reagent-location', [ESPSetupController::class, 'saveReagentLocation'])->name('machines.saveReagentLocation');
    Route::delete('/machines/data/{id}', [ESPSetupController::class, 'deleteMachineData'])->name('machines.data.delete');
    Route::get('/machines/setup/{id}', [ESPSetupController::class, 'reagentSetup'])->name('machines.setup');
    
    Route::delete('/machines/machine-data/{id}', [ESPSetupController::class, 'deleteMachineData'])->name('machines.deleteMachineData');
    
    //Dashboad route
    Route::get('/machines/dashboard/{id}', [DashboardController::class, 'index'])->name('machines.dashboard'); 

    Route::get('/machines/{machine}/show', [MachineController::class, 'show'])->name('machines.show');
    
    Route::post('/machines/start-command', function (Request $request) {
        $machineId = $request->input('machine_id');
        $message = "start";

        // broadcast to device channel
        broadcast(new DeviceDataReceived([
            'machine_id' => $machineId,
            'command' => $message
        ]));

        return response()->json(['status' => 'sent', 'message' => 'Start command sent via WebSocket']);
    })->name('machines.startCommand');

    //Calibrate Save & Delete routes
    Route::post('/machines/reactor-calibrate/save/{id}', [MachineCalibrateController::class, 'saveReactor'])->name('machines.reactor-calibrate.save');
    Route::post('/machines/cc-calibrate/save/{id}', [MachineCalibrateController::class, 'saveCc'])->name('machines.cc-calibrate.save');
    Route::delete('/machines/reactor-calibrate/{id}', [MachineCalibrateController::class, 'deleteReactor'])->name('machines.reactor-calibrate.delete');
    Route::delete('/machines/cc-calibrate/{id}', [MachineCalibrateController::class, 'deleteCc'])->name('machines.cc-calibrate.delete');

    
    // 🔹 AJAX for Proprietor/User (Machine setup)
    // ------------------------------------------
    Route::get('/machines/brands-by-test/{testId}', [MachineController::class, 'getBrandsByTest'])
        ->name('machines.brandsByTest');

    Route::get('/machines/brands/{brand}/reagents', [MachineController::class, 'getReagentsByBrand'])
        ->name('machines.reagents');

    // ==========================================
    // 👤 User Management
    // ==========================================
    Route::resource('users', UserController::class);

    // ==========================================
    // 🧑‍💼 Admin / Proprietor / Moderator Routes
    // ==========================================
    Route::middleware(['role:Admin,Proprietor,Moderator'])->group(function () {

        // Master Data
        Route::resource('tests', TestController::class);
        Route::resource('brands', BrandController::class);
        Route::resource('reagents', ReagentController::class);

        // ESP Sensors
        Route::get('/espsensor', [EspSensorController::class, 'index'])->name('espsensor.index');
        Route::delete('/espsensor/{id}', [EspSensorController::class, 'destroy'])->name('espsensor.destroy');

        // ESP Setup (for Admin panel)
        Route::get('/esp-setups', [ESPSetupController::class, 'index'])->name('esp-setups.index');
        Route::get('/esp-setups/brands-by-test/{test}', [ESPSetupController::class, 'getBrands'])
            ->name('esp-setups.brands');
        Route::post('/brands/assign-reagents', [BrandController::class, 'assignReagents'])
            ->name('brands.assignReagents');
        Route::get('/esp-setups/brands/{brand}/reagents', [ESPSetupController::class, 'getBrandReagents'])
            ->name('esp-setups.reagents');
        
            
        Route::get('/testing-view', [TestingViewController::class, 'index'])->name('testing-view.index');    
        Route::post('/api/send-command', function (Request $request) {
                // Example: just echo back received data
                return response()->json([
                    'received' => $request->all(),
                    'status' => 'ok',
                ]);
            });
    // routes/web.php
        Route::post('/testing-command', [TestingViewController::class, 'receiveCommand'])->name('testing-command');

      

        Route::get('/device', [DeviceController::class, 'index']); // view page

        });
    
    // Device communication (available to all authenticated users)
    Route::post('/device/send', [DeviceController::class, 'sendData']); // send data via AJAX
});
