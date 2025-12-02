@extends('layout.app')
@section('title', 'Lab360::Esp Sensor Data')

@section('content')
<div class="container-fluid">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Esp Sensor Data List</h4>

                        <!-- নতুন ডেটা ইনসার্ট করার বাটন -->
                        <button id="insertBtn" class="btn btn-primary mb-3">
                            Insert Random Sensor
                        </button>

                        <div class="table-responsive">
                            <table class="table" id="sensorTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Test Name</th>
                                        <th>Brand</th>
                                        <th>R</th>
                                        <th>G</th>
                                        <th>B</th>
                                        <th>Temp</th>
                                        <th>Sen 1</th>
                                        <th>Sen 2</th>
                                        <th>Sen 3</th>
                                        <th>Sen 4</th>
                                        <th>Sen 5</th>
                                        <th>Sen 6</th>
                                        <th>Recorded At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sensors as $sensor)
                                        <tr>
                                            <td>{{ $sensor->id }}</td>
                                            <td>{{ $sensor->test_name }}</td>
                                            <td>{{ $sensor->brand }}</td>
                                            <td>{{ $sensor->color_r }}</td>
                                            <td>{{ $sensor->color_g }}</td>
                                            <td>{{ $sensor->color_b }}</td>
                                            <td>{{ $sensor->tem }}</td>
                                            <td>{{ $sensor->sen_1 }}</td>
                                            <td>{{ $sensor->sen_2 }}</td>
                                            <td>{{ $sensor->sen_3 }}</td>
                                            <td>{{ $sensor->sen_4 }}</td>
                                            <td>{{ $sensor->sen_5 }}</td>
                                            <td>{{ $sensor->sen_6 }}</td>
                                            <td>{{ $sensor->recorded_at }}</td>
                                            <td>
                                                <form action="{{ route('espsensor.destroy', $sensor->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
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
<script>
document.getElementById("insertBtn").addEventListener("click", function(){
    fetch("/esp-sensor")
        .then(res => res.json())
        .then(sensor => {
            const row = `
                <tr>
                    <td>${sensor.id}</td>
                    <td>${sensor.test_name}</td>
                    <td>${sensor.brand}</td>
                    <td>${sensor.color_r}</td>
                    <td>${sensor.color_g}</td>
                    <td>${sensor.color_b}</td>
                    <td>${sensor.tem}</td>
                    <td>${sensor.sen_1}</td>
                    <td>${sensor.sen_2}</td>
                    <td>${sensor.sen_3}</td>
                    <td>${sensor.sen_4}</td>
                    <td>${sensor.sen_5}</td>
                    <td>${sensor.sen_6}</td>
                    <td>${sensor.created_at}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" disabled>Delete</button>
                    </td>
                </tr>
            `;
            document.querySelector("#sensorTable tbody").insertAdjacentHTML("afterbegin", row);
        })
        .catch(err => console.error(err));
});
</script>
@endsection
