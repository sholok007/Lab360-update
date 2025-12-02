@extends('layout.app')
@section('title', 'Lab360::Machine Operation-Menual Test')

@section('content')
<style>
.schedule-container {
  background: #fff;
  border-radius: 10px;
  padding: 25px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.schedule-container h2 {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 20px;
  text-align: center;
}
.form-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}
.days label {
  margin-right: 10px;
  font-size: 14px;
}
.days input {
  margin-right: 4px;
}
#alarmList li, #historyList li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8f9fa;
  border-radius: 6px;
  padding: 10px;
  margin-bottom: 8px;
}
#alarmList button {
  border: none;
  background: #dc3545;
  color: white;
  border-radius: 4px;
  padding: 4px 10px;
  cursor: pointer;
}
</style>

<div class="container-fluid">
  <div class="content-wrapper">
    <div class="row justify-content-center">
      <div class="col-md-12">
        <h1>Machine Name - {{ $machine->machine_name }}</h1>
      </div>

      <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
          <div class="card-body d-flex justify-content-center">
            @if(session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card col-md-6" >
              <h2>Menul Test</h2>

              <!-- Input Row -->
              <div class="form-row p-5">
                <!-- Test Name Select -->
                <select id="testName" class="form-control col-md-12" style="flex:1">
                  <option value="">Select Test Name</option>
                  @foreach($tests as $test)
                    <option value="{{ $test->name }}">{{ $test->name }}</option>
                  @endforeach
                </select>
                <!-- Add Button -->
                <button class="btn btn-success " >Run</button>
              </div>
              <!-- Scheduled List -->
            
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection


@section('script')
<script>

</script>
@endsection
