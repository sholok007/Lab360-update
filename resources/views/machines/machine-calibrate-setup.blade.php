@extends('layout.app')
@section('title', 'Lab360::Settings-Machine Calibrate Setup')
@section('content')
<div class="container-fluid">
    <div class="content-wrapper">
        <div class="row justify-content-center">
            <div class="col-md-12"><h1>Machine Name - {{ $machine->machine_name }}</h1></div>
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center"><h4 class="card-title">Machine Calibrate</h4></div>

                        {{-- Reactor Calibrate --}}
                        <form id="reactorCalibrateForm" method="POST" action="{{ route('machines.reactor-calibrate.save', $machine->id) }}">
                            @csrf
                            <div class="form-group">
                               <div class="d-flex justify-content-between">
                                    <label>Reactor Calibrate</label>
                                    <button type="button" class="btn btn-danger btn-sm" id="startReactorBtn">Start</button>
                                </div>
                                <div class="input-group mt-2">
                                    <input type="text" name="reactor_value" class="form-control" placeholder="Enter Value" aria-label="Reactor Value">
                                    <div class="input-group-append">
                                        <button class="btn btn-sm btn-facebook" type="button">ml</button>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-2 btn-block">Save Reactor</button>
                        </form>

                        {{-- CC Calibrate --}}
                        <form id="ccCalibrateForm" method="POST" action="{{ route('machines.cc-calibrate.save', $machine->id) }}">
                            @csrf
                            <div class="form-group mt-4">
                                <div class="d-flex justify-content-between">
                                    <label>>Cleaning Chember Calibrate</label>
                                    <button type="button" class="btn btn-danger btn-sm" id="startCcBtn">Start</button>
                                </div>
                                <div class="input-group mt-2">
                                    <input type="text" name="cc_value" class="form-control" placeholder="Enter Value" aria-label="CC Value">
                                    <div class="input-group-append">
                                        <button class="btn btn-sm btn-facebook" type="button">ml</button>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-2 btn-block">Save CC</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tables --}}
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Reactor Calibrate</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Value</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($reactorData)
                                    <tr id="reactorRow-{{ $reactorData->id }}">
                                        <td id="reactorValue">{{ $reactorData->value }}</td>
                                        <td id="reactorDate">{{ $reactorData->updated_at->format('d M, Y H:i') }}</td>
                                    </tr>
                                    @else
                                    <tr>
                                        <td colspan="2" class="text-center">No data available</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">CC Calibrate</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Value</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($ccData)
                                    <tr id="ccRow-{{ $ccData->id }}">
                                        <td id="ccValue">{{ $ccData->value }}</td>
                                        <td id="ccDate">{{ $ccData->updated_at->format('d M, Y H:i') }}</td>
                                    </tr>
                                    @else
                                    <tr>
                                        <td colspan="2" class="text-center">No data available</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo/dist/echo.iife.js"></script>

<script>
window.Pusher = Pusher;

// IIFE (already includes Echo)
window.Echo = Echo;

const macId = "{{ $machine->mac_id }}";

// Listen to machine channel
window.Echo.channel(`machine.${macId}`)
    .listen('.device.data', (e) => {
        console.log("📡 WS Received:", e);
        if(e.command === 'start'){
            Swal.fire('Device Started!', `Start command received for ${macId}`, 'info');
        }
    });

// ✅ এখানে Echo/Pusher connection state check
if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
    console.log('Connection state:', window.Echo.connector.pusher.connection.state);
} else {
    console.warn('Echo or Pusher not initialized yet!');
}

console.log('✅ WebSocket initialized');
</script>


<script>
$(document).ready(function(){
    const macId = "{{ $machine->mac_id }}";

    // Listen again safely (optional, but ensures jQuery ready)
    if(window.Echo && window.Echo.channel){
        window.Echo.channel(`machine.${macId}`)
            .listen('.device.data', (e) => {
                console.log("📡 Received command:", e);
                
                // Handle acknowledgment events
                if (e.status === 'acknowledged') {
                    console.log('✅ ESP32 acknowledged:', e.command);
                    
                    if (e.command === 'save_reactor_calibrate' && window.pendingReactorSave) {
                        clearTimeout(window.reactorTimeout);
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved Successfully!',
                            text: 'ESP32 confirmed - Data saved to database',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        window.pendingReactorSave = false;
                        // Reload to show updated data
                        setTimeout(() => location.reload(), 2000);
                        
                    } else if (e.command === 'save_cc_calibrate' && window.pendingCcSave) {
                        clearTimeout(window.ccTimeout);
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved Successfully!',
                            text: 'ESP32 confirmed - Data saved to database',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        window.pendingCcSave = false;
                        // Reload to show updated data
                        setTimeout(() => location.reload(), 2000);
                        
                    } else if (e.command === 'delete_reactor_calibrate') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted Successfully!',
                            text: 'ESP32 confirmed - Data deleted from database',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        setTimeout(() => location.reload(), 2000);
                        
                    } else if (e.command === 'delete_cc_calibrate') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted Successfully!',
                            text: 'ESP32 confirmed - Data deleted from database',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        setTimeout(() => location.reload(), 2000);
                    }
                    
                } else if (e.status === 'failed') {
                    console.error('❌ ESP32 reported error:', e.message);
                    
                    clearTimeout(window.reactorTimeout);
                    clearTimeout(window.ccTimeout);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Device Error',
                        text: e.message || 'ESP32 failed to process the command',
                    });
                    
                    window.pendingReactorSave = false;
                    window.pendingCcSave = false;
                    
                } else if (e.command === 'start') {
                    Swal.fire('Device Started!', `Start command received for ${macId}`, 'info');
                }
            });
            
        // Set timeout for pending operations (30 seconds)
        setInterval(() => {
            if (window.pendingReactorSave || window.pendingCcSave) {
                console.warn('⏱️ Still waiting for ESP32 acknowledgment...');
            }
        }, 5000);
    }

    // Submit forms (Reactor + CC)
    $('#reactorCalibrateForm').submit(function(e){
        e.preventDefault();
        let formData = $(this).serialize();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            success: function(res){
                if (res.status === 'pending') {
                    // ECHO system: waiting for ESP32 confirmation
                    Swal.fire({
                        icon: 'info',
                        title: 'Waiting for Device...',
                        text: 'Command sent to ESP32. Waiting for confirmation...',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    
                    // Listen for acknowledgment via WebSocket
                    window.pendingReactorSave = true;
                    
                    // Timeout after 30 seconds if no response
                    window.reactorTimeout = setTimeout(() => {
                        if (window.pendingReactorSave) {
                            window.pendingReactorSave = false;
                            Swal.fire({
                                icon: 'error',
                                title: 'Device Not Responding',
                                html: 'ESP32 did not confirm within 30 seconds.<br><br>Possible reasons:<br>• ESP32 is offline<br>• WebSocket connection lost<br>• Device is not responding<br><br>Please check the device and try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    }, 30000);
                    
                } else {
                    // Legacy mode or direct success
                    Swal.fire({icon:'success', title:'Updated!', text:res.message, timer:1500, showConfirmButton:false});
                    $('#reactorValue').text(res.data.value);
                    $('#reactorDate').text(new Date().toLocaleString('en-GB', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'}));
                    $('input[name="reactor_value"]').val('');
                }
            },
            error: function(err){
                Swal.fire('Error!', err.responseJSON?.message || 'Failed to save value.', 'error');
            }
        });
    });

    $('#ccCalibrateForm').submit(function(e){
        e.preventDefault();
        let formData = $(this).serialize();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            success: function(res){
                if (res.status === 'pending') {
                    // ECHO system: waiting for ESP32 confirmation
                    Swal.fire({
                        icon: 'info',
                        title: 'Waiting for Device...',
                        text: 'Command sent to ESP32. Waiting for confirmation...',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    
                    // Listen for acknowledgment via WebSocket
                    window.pendingCcSave = true;
                    
                    // Timeout after 30 seconds if no response
                    window.ccTimeout = setTimeout(() => {
                        if (window.pendingCcSave) {
                            window.pendingCcSave = false;
                            Swal.fire({
                                icon: 'error',
                                title: 'Device Not Responding',
                                html: 'ESP32 did not confirm within 30 seconds.<br><br>Possible reasons:<br>• ESP32 is offline<br>• WebSocket connection lost<br>• Device is not responding<br><br>Please check the device and try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    }, 30000);
                    
                } else {
                    // Legacy mode or direct success
                    Swal.fire({icon:'success', title:'Updated!', text:res.message, timer:1500, showConfirmButton:false});
                    $('#ccValue').text(res.data ? res.data.value : $('input[name="cc_value"]').val());
                    $('#ccDate').text(new Date().toLocaleString('en-GB', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'}));
                    $('input[name="cc_value"]').val('');
                }
            },
            error: function(err){
                Swal.fire('Error!', err.responseJSON?.message || 'Failed to save value.', 'error');
            }
        });
    });


    // Start Reactor via WebSocket
    $('#startReactorBtn').click(function(){
        console.log('🚀 Sending reactor calibrate command');
        console.log('MAC ID:', macId);
        $.ajax({
            url: "/device/send",
            method: "POST",
            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
            data: { 
                mac_id: macId, 
                command: "start_reactor_calibrate"
            },
            success: function(res){
                console.log("✅ Response:", res);
                Swal.fire({icon:'success', title:'Command Sent!', text:'Reactor calibration started.'});
            },
            error: function(xhr, status, error){
                console.error("❌ Error:", xhr.responseText);
                console.error("Status:", status);
                console.error("Error:", error);
                Swal.fire({icon:'error', title:'Error!', text:'Failed to send command: ' + (xhr.responseJSON?.message || error)});
            }
        });
    });

    // Start CC via WebSocket
    $('#startCcBtn').click(function(){
        console.log('🚀 Sending CC calibrate command');
        console.log('MAC ID:', macId);
        $.ajax({
            url: "/device/send",
            method: "POST",
            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
            data: { 
                mac_id: macId, 
                command: "start_cc_calibrate"
            },
            success: function(res){
                console.log("✅ Response:", res);
                Swal.fire({icon:'success', title:'Command Sent!', text:'CC calibration started.'});
            },
            error: function(xhr, status, error){
                console.error("❌ Error:", xhr.responseText);
                console.error("Status:", status);
                console.error("Error:", error);
                Swal.fire({icon:'error', title:'Error!', text:'Failed to send command: ' + (xhr.responseJSON?.message || error)});
            }
        });
    });
});
</script>
@endsection

