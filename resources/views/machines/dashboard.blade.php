@extends('layout.app')
@section('title', 'Lab360::Dashboard')

@section('content')
<div class="container-fluid">
    <div class="content-wrapper">
        <div class="row justify-content-center">
            {{-- Machine Add Form --}}
            <div class="col-md-12"><h1>Machine Name - {{ $machine->name }}</h1></div>
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                       
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                      
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
