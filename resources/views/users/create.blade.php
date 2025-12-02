@extends('layout.app')
@section('title', 'EspSensor::Creat User')

@section('content')
    <div class="container-fluid">
         <div class="content-wrapper">
            <div class="row">
                <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Create User</h4>
                     @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                    <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                      <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="Full Name">
                      </div>
                     <div class="form-group">
                          <label>User Name</label>
                        <input type="text" name="username" class="form-control" placeholder="User Name" required>
                      </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control"  placeholder="Email" required>
                    </div>
                     
                     <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>

                    <div class="form-group">
                        <label>Contact No</label>
                        <input type="text" name="contact_no" class="form-control" placeholder="Contact No" required>
                    </div>

                    <div class="form-group">
                      <label >Roll</label>
                      <select name="role" class="form-control">
                        <option value="Proprietor">Proprietor</option>
                        <option value="Moderator">Moderator</option>
                      </select>
                    </div>
                      <input type="submit" class="btn btn-primary" value="Create User">
                    </form>
                  </div>
                </div>
              </div>
            </div>
        </div>
    </div>
@endsection      