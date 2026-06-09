@extends('layouts.app')

@section('title', 'Entidades')

@section('content')
    <div class="d-flex justify-content-start align-items-center mb-3">
        <h1 class="h3 mb-0">Entidades</h1>
    </div>


    <div class="d-flex justify-content-end align-items-end mb-3">
        <a href="{{ route('entidades.create') }}" class="btn btn-primary">Crear Entidad</a>
    </div>
    @livewire('⚡entidad-table')
@endsection