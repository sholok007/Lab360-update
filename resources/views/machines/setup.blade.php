
@extends('layout.app')
@section('title','Lab360::Settings-Reagent Setup')


@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div id="esp32-echo-window" style="background:#181818;color:#fff;padding:12px 18px;margin-bottom:18px;border-radius:8px;min-height:40px;">
                <b>ESP32 Acknowledgment (ECHO) Log:</b>
                <button type="button" onclick="clearEchoLog()" style="margin-left:16px;background:#ff5555;color:#fff;border:none;padding:4px 12px;border-radius:5px;cursor:pointer;font-size:14px;">Clear Log</button>
                <div id="esp32EchoLog" style="margin-top:8px;"></div>
            </div>
        </div>
    </div>

<style>
/* New Dark Theme UI */
.setup-container {
    background: #1a1a1a;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 0 12px #000;
}

.setup-title {
    margin-bottom: 20px;
    font-size: 22px;
    color: #fff;
}

.reagent-list {
    margin-bottom: 20px;
    padding: 15px;
    background: #0f0f0f;
    border-radius: 8px;
}

.reagent-list label {
    margin-right: 25px;
    font-size: 18px;
    color: #fff;
    cursor: pointer;
}

.reagent-list input[type="checkbox"] {
    margin-right: 8px;
    cursor: pointer;
    width: 18px;
    height: 18px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(5, 80px);
    gap: 8px;
    margin-bottom: 25px;
    padding: 20px;
    background: #0f0f0f;
    border-radius: 8px;
    justify-content: center;
}

.cell {
    width: 80px;
    height: 80px;
    background: #222;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    border: 2px solid #333;
    cursor: pointer;
    user-select: none;
    transition: 0.2s;
    font-size: 18px;
    color: #bbb;
}

.cell:hover:not(.booked):not(.disabled) {
    background: #333;
}

.cell.active {
    background: #db3b3b;
    color: white;
    border-color: #ff5e5e;
}

.cell.booked {
    background: #520202ff;
    color: #fff;
    font-size: 11px;
    cursor: not-allowed;
}

.cell.disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.save-btn {
    width: 120px;
    padding: 10px 18px;
    background: #0077ff;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    cursor: pointer;
    transition: 0.2s;
}

.save-btn:hover {
    background: #0060cc;
}

.custom-modal-width {
    max-width: 800px; 
    width: 100%; 
}

.form-check-label {
    margin-left: 8px; 
    font-weight: 500;
    font-size: 16px;
    cursor: pointer;
}

/* Modal locations styling */
#modalLocationsBody .location-checkbox:checked + .form-check-label,
#modalLocationsBody .location-checkbox:disabled + .form-check-label {
    background-color: #610808ff;
    color: #fff;
    border-radius: 5px;
    padding: 5px 10px;
    display: inline-block;
}

#modalLocationsBody .location-checkbox:disabled + .form-check-label {
    opacity: 0.7;
}
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div id="esp32-echo-window" style="background:#181818;color:#fff;padding:12px 18px;margin-bottom:18px;border-radius:8px;min-height:40px;">
                <b>ESP32 Acknowledgment (ECHO) Log:</b>
                <button type="button" onclick="clearEchoLog()" style="margin-left:16px;background:#ff5555;color:#fff;border:none;padding:4px 12px;border-radius:5px;cursor:pointer;font-size:14px;">Clear Log</button>
                <div id="esp32EchoLog" style="margin-top:8px;"></div>
            </div>
        </div>
    </div>
     <div class="content-wrapper">
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card setup-container">
                    <div class="card-body">
                    <h2 class="setup-title">Machine Name - {{ $machine->machine_name }}</h2>
                    
                    <h3 class="setup-title">Assign Reagents & Locations</h3>
                    
                    <div class="d-flex justify-content-center bd-highlight mb-3">
                           <div class="p-2 bd-highlight col-md-3">
                                <select class="form-control" id="chooseTest" style="background: #222; color: #fff; border: 2px solid #333;">
                                    <option value="">Select Test</option>
                                        @foreach($tests as $test)
                                            <option value="{{ $test->id }}">{{ $test->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="p-2 bd-highlight col-md-3">
                                    <select class="form-control" id="selectBrand" style="background: #222; color: #fff; border: 2px solid #333;">
                                        <option value="">Select Brand</option>
                                    </select>
                                </div>
                        </div>

                  <!-- Reagent Selection -->
                  <div style="text-align: center;">
                      <div class="reagent-list" id="reagentList" style="display: none; max-width: 400px; margin: 0 auto;">
                          <!-- Reagents will be dynamically loaded here -->
                      </div>
                  </div>

                  <!-- Location Grid -->

                  <div class="grid" id="locationGrid">
                        @foreach($locations as $location)
                            @php
                                // check if location is assigned in DB
                                $mapping = $machineData->first(function($data) use ($location) {
                                    return in_array($location->id, [
                                        optional($data->reagentALocation)->id,
                                        optional($data->reagentBLocation)->id,
                                        optional($data->reagentCLocation)->id,
                                        optional($data->reagentDLocation)->id,
                                        optional($data->reagentELocation)->id
                                    ]);
                                });
                            @endphp

                            <div class="cell {{ $mapping ? 'booked' : 'disabled' }}" 
                                 id="cell{{ $location->id }}" 
                                 data-location-id="{{ $location->id }}"
                                 data-booked="{{ $mapping ? 1 : 0 }}">
                                @if($mapping)
                                    <div style="text-align:center; font-size:11px; font-weight:normal;">
                                        <div><strong>{{ $location->name }}</strong></div>
                                        <div>{{ $mapping->test_name }}</div>
                                        <div>{{ $mapping->brand->name ?? '' }}</div>
                                        @php
                                            $reagentLabel = '';
                                            if(optional($mapping->reagentALocation)->id == $location->id) $reagentLabel = 'R-A';
                                            elseif(optional($mapping->reagentBLocation)->id == $location->id) $reagentLabel = 'R-B';
                                            elseif(optional($mapping->reagentCLocation)->id == $location->id) $reagentLabel = 'R-C';
                                            elseif(optional($mapping->reagentDLocation)->id == $location->id) $reagentLabel = 'R-D';
                                            elseif(optional($mapping->reagentELocation)->id == $location->id) $reagentLabel = 'R-E';
                                        @endphp
                                        <div>{{ $reagentLabel }}</div>
                                    </div>
                                @else
                                    {{ $location->name }}
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Save Button -->
                    <div style="text-align: center;">
                        <button id="saveAssignment" class="save-btn" style="display: none;">Save</button>
                    </div>
                  
            </div>    
        </div>   
    </div>     

    <div class="container mt-5">
        <h4 class="mb-3">Machine Data List</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-center">
                <thead class="thead-dark">
                    <tr>
                        <th>Test Name</th>
                        <th>Brand</th>
                        <th>Reagent A</th>
                        <th>Reagent B</th>
                        <th>Reagent C</th>
                        <th>Reagent D</th>
                        <th>Reagent E</th>
                       
                        <th style="display:none">Meta</th>
                        <th>Created</th>
                        <th style="display:none">Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($machineData) && $machineData->count() > 0)
                        @foreach($machineData as $data)
                            <tr id="row-{{ $data->id }}">
                                <td>{{ $data->test_name }}</td>
                                <td>{{ $data->brand->name ?? '' }}</td>
                                <td>{{ $data->reagentALocation->name ?? '' }}</td>
                                <td>{{ $data->reagentBLocation->name ?? '' }}</td>
                                <td>{{ $data->reagentCLocation->name ?? '' }}</td>
                                <td>{{ $data->reagentDLocation->name ?? '' }}</td>
                                <td>{{ $data->reagentELocation->name ?? '' }}</td>
                              
                                <td style="display:none">{{ json_encode($data->meta) }}</td>
                                <td>{{ $data->created_at }}</td>
                                <td style="display:none">{{ $data->updated_at }}</td>
                                <td>
                                   <button class="btn btn-danger btn-sm delete-btn" data-id="{{ $data->id }}">Delete </button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="18">No machine data found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>   
    </div> 
</div>


<!-- Modal -->
<div class="modal fade" id="addReagentModal" tabindex="-1" aria-labelledby="addReagentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="assignReagentsForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addReagentModalLabel">Assign Reagents & Locations</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="modalBrandId" name="brand_id">
          <input type="hidden" id="modalTestName" name="test_name">

          <div id="modalReagentsBody"></div>

          <hr>
          <div id="modalLocationsBody" class="d-flex flex-wrap gap-2">
            @foreach($locations as $location)
              @php
                $used = $machineData->first(function($data) use ($location) {
                    return in_array($location->id, [
                        optional($data->reagentALocation)->id,
                        optional($data->reagentBLocation)->id,
                        optional($data->reagentCLocation)->id,
                        optional($data->reagentDLocation)->id,
                        optional($data->reagentELocation)->id
                    ]);
                });
              @endphp
              <div class="form-check" style="width:100px; text-align:center;">
                <input class="form-check-input location-checkbox" type="checkbox" 
                       value="{{ $location->id }}" 
                       id="location{{ $location->id }}" 
                       {{ $used ? 'checked disabled' : '' }}>
                <label class="form-check-label" for="location{{ $location->id }}">
                  {{ $location->name }}
                </label>
              </div>
            @endforeach
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>





@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mac_id = "{{ $machine->mac_id }}";
    const testSelect = document.getElementById('chooseTest');
    const brandSelect = document.getElementById('selectBrand');
    const reagentList = document.getElementById('reagentList');
    const saveBtn = document.getElementById('saveAssignment');
    const locationGrid = document.getElementById('locationGrid');
    
    let selectedReagents = [];
    let selectedLocations = [];
    let currentMapping = {};
    let availableReagents = [];
    let assignedLocations = [];
    let totalReagentsCount = 0;
    let canSelectLocations = false;

    // Add click handlers to location cells
    document.querySelectorAll('.cell').forEach(cell => {
        cell.addEventListener('click', function() {
            if (this.dataset.booked === '1') return;
            
            // Prevent selection if test and brand not selected
            if (!canSelectLocations) {
                Swal.fire({
                    icon: 'info',
                    title: 'Select Test & Brand First',
                    text: 'Please select a test and brand before choosing locations.',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            // Remove disabled class and enable interaction
            if (this.classList.contains('disabled')) {
                this.classList.remove('disabled');
            }
            
            this.classList.toggle('active');
            const locationId = parseInt(this.dataset.locationId);
            
            if (this.classList.contains('active')) {
                if (!selectedLocations.includes(locationId)) {
                    selectedLocations.push(locationId);
                }
            } else {
                selectedLocations = selectedLocations.filter(id => id !== locationId);
            }
            
            updateSaveButton();
        });
    });

    function updateSaveButton() {
        // Save button is active only when ALL reagents are selected AND equal number of locations are selected
        if (totalReagentsCount > 0 && 
            selectedReagents.length === totalReagentsCount && 
            selectedLocations.length === totalReagentsCount) {
            saveBtn.style.display = 'inline-block';
        } else {
            saveBtn.style.display = 'none';
        }
    }

    // Test change → load brands
    testSelect.addEventListener('change', function() {
        const testId = this.value;
        const testName = this.options[this.selectedIndex]?.text || '';
        brandSelect.innerHTML = '<option value="">Select Brand</option>';
        reagentList.innerHTML = '';
        reagentList.style.display = 'none';
        selectedReagents = [];
        selectedLocations = [];
        saveBtn.style.display = 'none';
        canSelectLocations = false;
        
        // Reset all location cells to disabled state
        document.querySelectorAll('.cell:not(.booked)').forEach(cell => {
            cell.classList.remove('active');
            cell.classList.add('disabled');
        });
        
        if (!testId) return;

        fetch(`/machines/brands-by-test/${testId}`, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                data.forEach(brand => {
                    const option = document.createElement('option');
                    option.value = brand.id;
                    option.textContent = brand.name;
                    brandSelect.appendChild(option);
                });
            });
    });

    // Brand change → load reagents
    brandSelect.addEventListener('change', function() {
        const brandId = this.value;
        const brandName = this.options[this.selectedIndex]?.text || '';
        const testName = testSelect.options[testSelect.selectedIndex]?.text || '';
        
        selectedReagents = [];
        selectedLocations = [];
        saveBtn.style.display = 'none';
        canSelectLocations = false;
        
        // Reset all location cells
        document.querySelectorAll('.cell:not(.booked)').forEach(cell => {
            cell.classList.remove('active');
            cell.classList.add('disabled');
        });
        
        if (!brandId) {
            reagentList.innerHTML = '';
            reagentList.style.display = 'none';
            return;
        }

        fetch(`/machines/brands/${brandId}/reagents?machine_id={{ $machine->id }}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(res => res.json())
        .then(data => {
            reagentList.innerHTML = '';
            availableReagents = data.reagents || [];
            assignedLocations = (data.assignedLocations || []).map(id => parseInt(id));
            totalReagentsCount = availableReagents.length;
            selectedReagents = [];
            selectedLocations = [];
            
            // Enable location selection now that brand is selected
            canSelectLocations = true;
            
            // Remove disabled class from available cells
            document.querySelectorAll('.cell:not(.booked)').forEach(cell => {
                cell.classList.remove('disabled');
            });

            if (availableReagents.length === 0) {
                reagentList.innerHTML = '<p style="color: #bbb;">No reagents found.</p>';
                reagentList.style.display = 'block';
                canSelectLocations = false;
                return;
            }

            // Create reagent checkboxes
            availableReagents.forEach(r => {
                const label = document.createElement('label');
                
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.className = 'reagent';
                input.value = r.id;
                input.dataset.name = r.name;
                
                input.addEventListener('change', function() {
                    if (this.checked) {
                        selectedReagents.push({id: r.id, name: r.name});
                    } else {
                        selectedReagents = selectedReagents.filter(item => item.id !== r.id);
                    }
                    updateSaveButton();
                });
                
                label.appendChild(input);
                label.appendChild(document.createTextNode(' ' + r.name));
                reagentList.appendChild(label);
            });
            
            reagentList.style.display = 'block';
            updateSaveButton();
        });
    });

    // Save button handler
    saveBtn.addEventListener('click', function() {
        if (selectedReagents.length !== totalReagentsCount) {
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Selection',
                text: 'Please select all reagents for this brand.',
                confirmButtonText: 'OK'
            });
            return;
        }

        if (selectedLocations.length !== totalReagentsCount) {
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Selection',
                text: `Please select exactly ${totalReagentsCount} location(s) for the ${totalReagentsCount} reagent(s).`,
                confirmButtonText: 'OK'
            });
            return;
        }

        // Create mapping: reagent ID -> location ID
        currentMapping = {};
        selectedReagents.forEach((reagent, index) => {
            if (selectedLocations[index]) {
                currentMapping[reagent.id] = selectedLocations[index];
            }
        });

        // Prepare device data with reagent-location assignments
        const deviceData = {
            test_name: testSelect.options[testSelect.selectedIndex]?.text || '',
            brand_name: brandSelect.options[brandSelect.selectedIndex]?.text || '',
            assignments: []
        };

        // Build assignments array with reagent names and location numbers
        selectedReagents.forEach((reagent, index) => {
            const locationId = selectedLocations[index];
            const locationElement = document.querySelector(`[data-location-id="${locationId}"]`);
            const locationName = locationElement ? locationElement.textContent.trim() : locationId;
            
            deviceData.assignments.push({
                reagent_id: reagent.id,
                reagent_name: reagent.name,
                location_id: locationId,
                location_name: locationName
            });
        });

        const payload = {
            machine_id: {{ $machine->id }},
            mac_id: "{{ $machine->mac_id }}",
            test_name: testSelect.options[testSelect.selectedIndex]?.text || '',
            brand_id: brandSelect.value,
            mappings: currentMapping,
            device_data: deviceData  // Add device data to payload
        };

        console.log('Reagents:', selectedReagents);
        console.log('Locations:', selectedLocations);
        console.log('Device Data:', deviceData);
        console.log('Payload:', payload);

        fetch("{{ route('machines.saveReagentLocation') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Reagents: ' + selectedReagents.map(r => r.name).join(', ') + 
                          '\nLocations: ' + selectedLocations.join(', '),
                    confirmButtonText: 'OK'
                }).then(() => location.reload());
            } else if (data.status === 'pending') {
                Swal.fire({
                    icon: 'info',
                    title: 'Waiting for device acknowledgment...',
                    text: data.message || 'Command sent to device. Waiting for acknowledgment...',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    timer: 5000
                });
            } else {
                Swal.fire('Error', data.message || 'Something went wrong', 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Failed to save data', 'error');
            console.error(err);
        });
    });

    // Delete record
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This record will be deleted permanently!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/machines/data/' + id,
                    type: 'DELETE',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 1200,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        }
                    }
                });
            }
        });
    });

    // WebSocket listener
    window.Echo.channel('machine.' + mac_id)
        .listen('.device.data', (e) => {
            // Show in rxWindow (if present)
            const rxWindow = document.getElementById('rxWindow');
            if (rxWindow) {
                const div = document.createElement('div');
                div.textContent = JSON.stringify(e.data);
                rxWindow.appendChild(div);
                rxWindow.scrollTop = rxWindow.scrollHeight;
            }
            // Also show in esp32EchoLog
            const echoLog = document.getElementById('esp32EchoLog');
            if (echoLog) {
                const div = document.createElement('div');
                div.textContent = `[${new Date().toLocaleTimeString()}] ESP32 TX: ` + JSON.stringify(e.data);
                div.style.color = '#00bfff';
                echoLog.appendChild(div);
                echoLog.scrollTop = echoLog.scrollHeight;
            }
        })
        .listen('.device.acknowledged', (e) => {
            const echoLog = document.getElementById('esp32EchoLog');
            if (echoLog) {
                const div = document.createElement('div');
                div.textContent = `[${new Date().toLocaleTimeString()}] ACK: ` + JSON.stringify(e);
                div.style.color = e.status === 'acknowledged' ? '#00ff00' : '#ff6b6b';
                echoLog.appendChild(div);
                echoLog.scrollTop = echoLog.scrollHeight;
            }
        });

    // Listen for ALL raw messages on the channel (for debugging)
    // Robustly bind the raw message listener, even if connection is not ready immediately
    (function bindRawListener() {
        const tryBind = () => {
            if (window.Echo && window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection) {
                window.Echo.connector.pusher.connection.bind('message', function(message) {
                    const echoLog = document.getElementById('esp32EchoLog');
                    if (echoLog) {
                        const div = document.createElement('div');
                        div.textContent = `[${new Date().toLocaleTimeString()}] RAW: ` + (typeof message === 'string' ? message : JSON.stringify(message));
                        div.style.color = '#ff6b6b';
                        echoLog.appendChild(div);
                        echoLog.scrollTop = echoLog.scrollHeight;
                    }
                });
                // Only bind once
                return true;
            }
            return false;
        };
        if (!tryBind()) {
            // Retry every 500ms until connection is ready
            const interval = setInterval(() => {
                if (tryBind()) clearInterval(interval);
            }, 500);
        }
    })();
});

function clearEchoLog() {
    const echoLog = document.getElementById('esp32EchoLog');
    if (echoLog) {
        echoLog.innerHTML = '';
    }
}
</script>


@endsection

