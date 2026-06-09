@extends('layouts.app')

@section('title', 'Registrar Movimiento')

@section('content')
    <div class="d-flex justify-content-start align-items-center mb-3">
        <h1 class="h3 mb-0">Registrar Movimiento</h1>
    </div>

    <div class="card">
        <div class="card-body">
            @livewire('⚡caja-form')
        </div>
    </div>
@endsection
