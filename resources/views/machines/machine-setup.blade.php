@extends('layout.app')
@section('title', 'Lab360::Settings-Machine Setup')

@section('content')

<style>
.machineRadio {
    width: 16px;
    height: 16px;
    accent-color: #007bff;
    cursor: pointer;
}
.form-group label {
    font-weight: 500;
}
</style>

<div class="container-fluid">
    <div class="content-wrapper">
        <div class="row justify-content-center">
            {{-- Machine Add Form --}}
            <div class="col-md-12">
                <h1>Machine Name - {{ $machine->machine_name }}</h1>
            </div>

            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center">Machine Setup</h3>

                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        {{-- ===================== DRAIN SECTION ===================== --}}
                        <button type="button" class="btn btn-warning btn-sm mb-2" id="testButton">🧪 Test WebSocket</button>
                        
                        <form id="drainForm">
                            @csrf
                            <div class="col-md-12">
                                <h4>Drain</h4>
                            </div>

                            <div class="row mt-3 align-items-center">
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <input type="radio" name="drainOption" id="drainPipe" class="machineRadio toggleable" value="pipe">
                                        <label for="drainPipe" class="ml-1">Pipe</label>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <div class="form-group mb-0">
                                                <input type="radio" name="drainOption" id="drainContainer" class="machineRadio toggleable" value="container">
                                                <label for="drainContainer" class="ml-1">Container</label>
                                            </div>
                                        </div>

                                        <div class="col-md-8" id="drainMlGroup" style="display: none;">
                                            <div class="input-group mt-2">
                                                <input type="text" class="form-control" name="drain_ml" placeholder="Enter Value">
                                                <div class="input-group-append">
                                                    <button class="btn btn-sm btn-facebook" type="button">ml</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 mt-3 text-right">
                                    <button type="button" class="btn btn-primary btn-sm" id="saveDrainBtn">Save Drain</button>
                                </div>
                            </div>
                        </form>

                        <hr>

                        {{-- ===================== RODI SECTION ===================== --}}
                        <form id="rodiForm">
                            @csrf
                            <div class="col-md-12">
                                <h4>RODI</h4>
                            </div>

                            <div class="row mt-3 align-items-center">
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <input type="radio" name="rodiOption" id="rodiSupply" class="machineRadio toggleable" value="pipe">
                                        <label for="rodiSupply" class="ml-1">Supply</label>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <div class="form-group mb-0">
                                                <input type="radio" name="rodiOption" id="rodiContainer" class="machineRadio toggleable" value="container">
                                                <label for="rodiContainer" class="ml-1">Container</label>
                                            </div>
                                        </div>

                                        <div class="col-md-8" id="rodiMlGroup" style="display: none;">
                                            <div class="input-group mt-2">
                                                <input type="text" class="form-control" name="rodi_ml" placeholder="Enter Value">
                                                <div class="input-group-append">
                                                    <button class="btn btn-sm btn-facebook" type="button">ml</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 mt-3 text-right">
                                    <button type="button" class="btn btn-primary btn-sm" id="saveRodiBtn">Save RODI</button>
                                </div>
                            </div>
                        </form>



                         {{-- ===================== others ===================== --}}
                        <form id="othersForm">
                            @csrf
                          
                            <div class="row mt-3 align-items-center">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Clarity Test</label>
                                        <div class="input-group">
                                            <div class="switch-container text-center">
                                                <label class="switch">
                                                    <input type="checkbox" class="toggleSwitch" data-target="claritytest">
                                                    <span class="slider"></span>
                                                </label>
                                                <p id="claritytest" class="statusText mt-1 mb-0">OFF</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                     <div class="form-group">
                                        <label>Tem. Test </label>
                                        <div class="input-group">
                                            <div class="switch-container text-center">
                                                <label class="switch">
                                                    <input type="checkbox" class="toggleSwitch" data-target="temtest">
                                                    <span class="slider"></span>
                                                </label>
                                                <p id="temtest" class="statusText mt-1 mb-0">OFF</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="col-md-4">
                                      <div class="form-group">
                                        <label>Alarm</label>
                                        <div class="input-group">
                                            <div class="switch-container text-center">
                                                <label class="switch">
                                                    <input type="checkbox" class="toggleSwitch" data-target="alarm">
                                                    <span class="slider"></span>
                                                </label>
                                                <p id="alarm" class="statusText mt-1 mb-0">OFF</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 mt-3 text-right">
                                    <button type="button" class="btn btn-primary btn-sm" id="saveOthersBtn">Save Others</button>
                                </div>

                                <div class="col-md-12 mt-3 text-center">
                                    <button type="submit" class="btn btn-success btn-lg">Submit</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection


@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const macId = "{{ $machine->mac_id }}";
const machineId = "{{ $machine->id }}";

console.log('🔍 Machine MAC ID:', macId);
console.log('🔍 Machine ID:', machineId);

// Wait for Echo to be ready
setTimeout(function() {
    if (window.Echo) {
        const safeMac = macId.replace(/:/g, '');
        const channelName = `machine.${safeMac}`;
        console.log('🔍 Listening on channel:', channelName);
        
        // Listen to machine channel for WebSocket responses
        window.Echo.channel(channelName)
            .listen('.device.data', (e) => {
                console.log("📡 WS Received:", e);
            });
        console.log('✅ WebSocket listening for machine:', macId);
    } else {
        console.warn('Echo not initialized');
    }
}, 200);

// 🔹 Make radio buttons toggleable (can deselect)
document.querySelectorAll('.toggleable').forEach(radio => {
    radio.addEventListener('mousedown', function (e) {
        if (this.checked) {
            this.dataset.wasChecked = "true";
        } else {
            this.dataset.wasChecked = "false";
        }
    });

    radio.addEventListener('click', function (e) {
        if (this.dataset.wasChecked === "true") {
            this.checked = false;
            this.dataset.wasChecked = "false";
            this.dispatchEvent(new Event('change')); // trigger change
        }
    });
});

// 🔹 Show/hide ml input based on selection
document.querySelectorAll('input[name="drainOption"]').forEach(radio => {
    radio.addEventListener('change', function () {
        const group = document.getElementById('drainMlGroup');
        group.style.display = (this.value === 'container' && this.checked) ? 'flex' : 'none';
    });
});

document.querySelectorAll('input[name="rodiOption"]').forEach(radio => {
    radio.addEventListener('change', function () {
        const group = document.getElementById('rodiMlGroup');
        group.style.display = (this.value === 'container' && this.checked) ? 'flex' : 'none';
    });
});

// 🔹 Handle Drain Form Submission
$(document).ready(function(){
    console.log('🟢 Drain form ready. Machine:', macId);
    console.log('🟢 jQuery loaded:', typeof $ !== 'undefined');
    console.log('🟢 Test button exists:', $('#testButton').length);
    console.log('🟢 Save Drain button exists:', $('#saveDrainBtn').length);
    
    // Test button
    $('#testButton').click(function(){
        console.log('🧪 Test button clicked');
        
        const testData = new FormData();
        testData.append('mac_id', macId);
        testData.append('command', 'TEST-DRAIN-PAGE');
        
        fetch("/device/send", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: testData
        })
        .then(response => {
            console.log("🧪 Test response status:", response.status);
            return response.json();
        })
        .then(data => {
            console.log("✅ Test command sent:", data);
            alert('Test sent! Status: ' + JSON.stringify(data) + '. Check ESP32 serial monitor');
        })
        .catch(error => {
            console.error("❌ Test failed:", error);
            alert('Test failed! Error: ' + error.message);
        });
    });
    
    // Save Drain Button
    $('#saveDrainBtn').click(function(){
        console.log('💧 Save Drain button clicked');
        
        const drainOption = $('input[name="drainOption"]:checked').val();
        const drainMl = $('input[name="drain_ml"]').val();
        
        console.log('Drain option:', drainOption, 'ML:', drainMl);
        
        if (!drainOption) {
            Swal.fire({icon:'warning', title:'Warning!', text:'Please select a drain option.'});
            return;
        }
        
        if (drainOption === 'container' && !drainMl) {
            Swal.fire({icon:'warning', title:'Warning!', text:'Please enter container value.'});
            return;
        }
        
        // Build command for ESP32
        let command = drainOption === 'pipe' ? 'drain=pipe' : `drain=container ${drainMl}`;
        
        console.log('💧 Sending command to ESP32:', command);
        console.log('💧 MAC ID:', macId);
        console.log('💧 URL:', "/device/send");
        
        // Send to ESP32 via WebSocket using fetch with FormData
        const formData = new FormData();
        formData.append('mac_id', macId);
        formData.append('command', command);
        
        fetch("/device/send", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: formData
        })
        .then(response => {
            console.log("💧 Response status:", response.status);
            return response.json();
        })
        .then(data => {
            console.log("✅ Drain command sent successfully:", data);
            
            // Save to database
            const dbData = new FormData();
            dbData.append('machine_id', machineId);
            dbData.append('drain_type', drainOption);
            dbData.append('ml_value', drainMl || '');
            
            return fetch("/machines/machine-setup/save-drain", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: dbData
            });
        })
        .then(response => response.json())
        .then(dbResult => {
            console.log("✅ Saved to database:", dbResult);
            Swal.fire({icon:'success', title:'Saved!', text:'Drain setup sent to ESP32 and saved to database successfully.'});
        })
        .catch(error => {
            console.error("❌ Error sending command:", error);
            Swal.fire({icon:'error', title:'Error!', text:'Failed to save drain setup.'});
        });
    });
    
    // Save RODI Button
    $('#saveRodiBtn').click(function(){
        console.log('💧 Save RODI button clicked');
        
        const rodiOption = $('input[name="rodiOption"]:checked').val();
        const rodiMl = $('input[name="rodi_ml"]').val();
        
        console.log('RODI option:', rodiOption, 'ML:', rodiMl);
        
        if (!rodiOption) {
            Swal.fire({icon:'warning', title:'Warning!', text:'Please select a RODI option.'});
            return;
        }
        
        if (rodiOption === 'container' && !rodiMl) {
            Swal.fire({icon:'warning', title:'Warning!', text:'Please enter container value.'});
            return;
        }
        
        // Build command for ESP32
        let command = rodiOption === 'pipe' ? 'rodi=supply' : `rodi=container ${rodiMl}`;
        
        console.log('💧 Sending RODI command to ESP32:', command);
        console.log('💧 MAC ID:', macId);
        
        // Send to ESP32 via WebSocket using fetch with FormData
        const formData = new FormData();
        formData.append('mac_id', macId);
        formData.append('command', command);
        
        fetch("/device/send", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: formData
        })
        .then(response => {
            console.log("💧 RODI Response status:", response.status);
            return response.json();
        })
        .then(data => {
            console.log("✅ RODI command sent successfully:", data);
            
            // Save to database
            const dbData = new FormData();
            dbData.append('machine_id', machineId);
            dbData.append('rodi_type', rodiOption);
            dbData.append('ml_value', rodiMl || '');
            
            return fetch("/machines/machine-setup/save-rodi", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: dbData
            });
        })
        .then(response => response.json())
        .then(dbResult => {
            console.log("✅ Saved to database:", dbResult);
            Swal.fire({icon:'success', title:'Saved!', text:'RODI setup sent to ESP32 and saved to database successfully.'});
        })
        .catch(error => {
            console.error("❌ Error sending RODI command:", error);
            Swal.fire({icon:'error', title:'Error!', text:'Failed to save RODI setup.'});
        });
    });
    
    // Save Others Button (Toggle Switches)
    $('#saveOthersBtn').click(function(){
        console.log('🔘 Save Others button clicked');
        
        // Get toggle switch states
        const clarityTest = $('input[data-target="claritytest"]').is(':checked') ? 'ON' : 'OFF';
        const temTest = $('input[data-target="temtest"]').is(':checked') ? 'ON' : 'OFF';
        const alarm = $('input[data-target="alarm"]').is(':checked') ? 'ON' : 'OFF';
        
        console.log('Clarity Test:', clarityTest);
        console.log('Tem. Test:', temTest);
        console.log('Alarm:', alarm);
        
        // Build command for ESP32
        const command = `clarity=${clarityTest},temp=${temTest},alarm=${alarm}`;
        
        console.log('🔘 Sending Others command to ESP32:', command);
        console.log('🔘 MAC ID:', macId);
        
        // Send to ESP32 via WebSocket
        const formData = new FormData();
        formData.append('mac_id', macId);
        formData.append('command', command);
        
        fetch("/device/send", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: formData
        })
        .then(response => {
            console.log("🔘 Others Response status:", response.status);
            return response.json();
        })
        .then(data => {
            console.log("✅ Others command sent successfully:", data);
            
            // Save to database
            const dbData = new FormData();
            dbData.append('machine_id', machineId);
            dbData.append('clarity_test', clarityTest);
            dbData.append('tem_test', temTest);
            dbData.append('alarm', alarm);
            
            return fetch("/machines/machine-setup/save-others", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: dbData
            });
        })
        .then(response => response.json())
        .then(dbResult => {
            console.log("✅ Saved to database:", dbResult);
            Swal.fire({icon:'success', title:'Saved!', text:'Settings sent to ESP32 and saved to database successfully.'});
        })
        .catch(error => {
            console.error("❌ Error sending Others command:", error);
            Swal.fire({icon:'error', title:'Error!', text:'Failed to save settings.'});
        });
    });
});
</script>
@endsection
