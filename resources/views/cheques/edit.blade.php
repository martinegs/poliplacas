@extends('layouts.app')

@section('title', 'Editar Cheque')

@section('content')
    <div class="d-flex justify-content-start align-items-center mb-3">
        <h1 class="h3 mb-0">Editar Cheque</h1>
    </div>

    <div class="card">
        <div class="card-body">
            @livewire('⚡cheque-form', ['cheque' => $cheque])
        </div>
    </div>
@endsection
