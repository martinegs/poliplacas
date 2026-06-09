@extends('layouts.app')

@section('title', 'Editar Movimiento')

@section('content')
    <div class="d-flex justify-content-start align-items-center mb-3">
        <h1 class="h3 mb-0">Editar Movimiento</h1>
    </div>

    <div class="card">
        <div class="card-body">
            @livewire('⚡caja-form', ['caja' => $caja])
        </div>
    </div>
@endsection
