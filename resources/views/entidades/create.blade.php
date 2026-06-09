@extends('layouts.app')
@section('title', 'Crear Entidad')
@section('content')
    <div class="d-flex justify-content-start align-items-center mb-3">
        <h1 class="h3 mb-0">Crear Entidad</h1>
    </div>

    <div class="card">
        <div class="card-body">
            @livewire('⚡entidad-form')
        </div>
    </div>
@endsection