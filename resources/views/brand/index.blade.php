@extends('layout.app')
@section('title','Lab360::Brand List')
@section('content')
<div class="container-fluid">
     <div class="content-wrapper">
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4>Brands</h4>
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <!-- Create Brand -->
                        <form action="{{ route('brands.store') }}" method="POST" class="mb-3">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Test</label>
                                    <select name="test_id" class="form-control" required>
                                        <option value="">Select Test</option>
                                        @foreach($tests as $test)
                                            <option value="{{ $test->id }}">{{ $test->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                 <div class="col-md-6">
                                    <label>Brand Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                            </div>
                            <button class="btn btn-primary mt-2">Add Brand</button>
                        </form>

                        <!-- Brand List Table -->
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Test Name</th>
                                    <th>Brand Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($brands as $brand)
                                    <tr>
                                        <td>{{ $brand->id }}</td>
                                        <td>{{ $brand->test->name }}</td>
                                        <td>{{ $brand->name }}</td>
                                        <td>
                                            <!-- Edit Button -->
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editBrandModal{{ $brand->id }}">Edit</button>

                                            <!-- Delete -->
                                            <form action="{{ route('brands.destroy', $brand->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger">Delete</button>
                                            </form>

                                            <!-- Add Reagent Button -->
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addReagentModal{{ $brand->id }}">Set Reagent</button>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editBrandModal{{ $brand->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <form action="{{ route('brands.update', $brand->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Brand</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-2">
                                                            <label>Brand Name</label>
                                                            <input type="text" name="name" class="form-control" value="{{ $brand->name }}" required>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label>Test</label>
                                                            <select name="test_id" class="form-control" required>
                                                                @foreach($tests as $test)
                                                                    <option value="{{ $test->id }}" @if($brand->test_id==$test->id) selected @endif>{{ $test->name }}</option>
                                                                @endforeach
                                                            </select>
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
                                    <!-- /Edit Modal -->

                                    <!-- Add Reagent Modal -->
                                <!-- Add Reagent Modal -->
                                    <div class="modal fade" id="addReagentModal{{ $brand->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form action="{{ route('brands.assignReagents') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="brand_id" value="{{ $brand->id }}">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Assign Reagents for {{ $brand->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        @foreach($reagents as $reagent)
                                                            <div class="form-check">
                                                                <input type="checkbox" name="reagent_ids[]" value="{{ $reagent->id }}"
                                                                    class="form-check-input"
                                                                    @if($brand->reagents->contains($reagent->id)) checked @endif>
                                                                <label class="form-check-label">{{ $reagent->name }}</label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-success">Save</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <!-- /Add Reagent Modal -->
                                @endforeach
                            </tbody>
                        </table>

                        {{ $brands->links() }}
                    </div>
                </div>
            </div>    
        </div>   
    </div>     
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@endsection
