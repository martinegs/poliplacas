@extends('layouts.app')

@section('title', 'Caja')

@section('content')
    <div class="d-flex justify-content-start align-items-center mb-3">
        <h1 class="h1 mb-0">Caja</h1>
    </div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-end align-items-end mb-3">
        <a href="{{ route('cajas.create') }}" class="btn btn-primary">Registrar Movimiento</a>
    </div>
    @livewire('⚡caja-table')
@endsection
