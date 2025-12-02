@extends('layout.app')
@section('title', 'Lab360::Add Machine')

@section('content')
<div class="container-fluid">
    <div class="content-wrapper">
        <div class="row">
            {{-- Machine Add Form --}}
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Add Machine</h4>
                        <form action="{{ route('machines.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>Machine Name</label>
                                <input type="text" name="machine_name" class="form-control" placeholder="Machine Name" required>
                            </div>
                            <div class="form-group">
                                <label>Authentication Code</label>
                                <input type="text" name="auth_code" class="form-control" placeholder="Authentication Code" required>
                            </div>
                            <input type="submit" class="btn btn-primary mt-2" value="Submit">
                        </form>
                    </div>
                </div>
            </div>

            {{-- Machine List --}}
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Machine List</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Machine Name</th>
                                        <th>Dashboard</th>
                                        <th>Settings</th>
                                        <th>Operation</th>
                                        <th>Payment Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($machines as $machine)
                                    <tr>
                                        <td>{{ $machine->machine_name }}</td>
                                        <td><a href="{{ route('machines.dashboard', $machine->id) }}" class="btn btn-primary">Dashboard</a></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">Settings</button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="{{ route('machines.setup', $machine->id) }}">RA Setup</a>
                                                    <a class="dropdown-item" href="{{ route('machines.calibrate.setup', $machine->id) }}">Machine Calibrate</a>
                                                    <a class="dropdown-item" href="{{ route('machines.machine-setup', $machine->id) }}">Machine Setup</a>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">Operation</button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="{{ route('machines.menual-test', $machine->id) }}">Manual Test</a>
                                                    <a class="dropdown-item" href="{{ route('machines.operation', $machine->id) }}">Auto Mode Test</a>
                                                </div>
                                            </div>
                                        </td>
                                        <td><a href="#" class="btn btn-primary">Payment</a></td>
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editMachineModal{{ $machine->id }}">Edit/View</button>
                                            <button class="btn btn-danger btn-sm delete-btn" data-id="{{ $machine->id }}">Delete</button>
                                        </td>
                                    </tr>

                                    {{-- Edit Modal --}}
                                    <div class="modal fade" id="editMachineModal{{ $machine->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form action="{{ route('machines.update', $machine->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-content">
                                                    <div class="modal-header"><h5>Edit Machine</h5></div>
                                                    <div class="modal-body">
                                                        <input type="text" name="machine_name" class="form-control" value="{{ $machine->machine_name }}" required>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-primary">Update</button>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                                </tbody>
                            </table>
                            <div>{{ $machines->links() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Machine Ready Popup --}}
    <div class="modal fade" id="machineReadyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Success!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Your machine is ready to connect!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Machine Ready Popup --}}
<div class="modal fade" id="machineReadyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Success!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Your machine is ready to connect!
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

{{-- Duplicate Popup --}}
<div class="modal fade" id="duplicateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Warning!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Machine name or authentication code already exists. Please use a new one.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Show Machine Ready Modal
    @if(session('showPopup'))
        var myModalEl = document.getElementById('machineReadyModal');
        var myModal = new bootstrap.Modal(myModalEl);
        myModal.show();

        // Reload page when modal is hidden
        myModalEl.addEventListener('hidden.bs.modal', function () {
            location.reload();
        });
    @endif

    // Show Duplicate Modal
    @if(session('duplicate'))
        var dupModalEl = document.getElementById('duplicateModal');
        var dupModal = new bootstrap.Modal(dupModalEl);
        dupModal.show();

        // Reload page when modal is hidden (optional, or can skip)
        dupModalEl.addEventListener('hidden.bs.modal', function () {
            location.reload();
        });
    @endif

    // Delete machine
    $(document).on('click', '.delete-btn', function() {
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
                    url: '/machines/' + id,
                    type: 'DELETE',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(response) {
                        if(response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 1200,
                                showConfirmButton: false
                            }).then(()=> location.reload());
                        } else {
                            Swal.fire({icon: 'error', title: 'Error!', text: response.message});
                        }
                    },
                    error: function(xhr){
                        Swal.fire({icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Something went wrong!'});
                    }
                });
            }
        });
    });
});
</script>
@endsection

