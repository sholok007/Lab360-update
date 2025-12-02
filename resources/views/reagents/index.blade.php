@extends('layout.app')
@section('title', 'Lab360::Reagents')

@section('content')
    <div class="container-fluid">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Reagents</h4>

                            @if(session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif

                            <!-- Add New Reagent -->
                            <form action="{{ route('reagents.store') }}" method="POST" class="mb-3">
                                @csrf
                                <div class="input-group">
                                    <input type="text" name="name" class="form-control" placeholder="Enter reagent name" required>
                                    <button type="submit" class="btn btn-primary">Add Reagent</button>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#ID</th>
                                            <th>Reagent Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($reagents as $reagent)
                                            <tr>
                                                <td>#{{ $reagent->id }}</td>
                                                <td>{{ $reagent->name }}</td>
                                                <td>
                                                    <!-- Edit Button -->
                                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editReagentModal{{ $reagent->id }}">
                                                        Edit
                                                    </button>

                                                    <!-- Delete -->
                                                    <form action="{{ route('reagents.destroy', $reagent->id) }}" method="POST" style="display:inline-block;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this reagent?')">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                {{ $reagents->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
    </div>

    <!-- Edit Modals -->
    @foreach($reagents as $reagent)
    <div class="modal fade" id="editReagentModal{{ $reagent->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('reagents.update', $reagent->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Reagent</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Reagent Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $reagent->name }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endforeach
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@endsection
