@extends('layouts.app')

@section('title', 'Cheques')

@section('content')
    <div class="d-flex justify-content-start align-items-center mb-3">
        <h1 class="h1 mb-0">Cheques</h1>
    </div>


    <div class="d-flex justify-content-end align-items-end mb-3">
        <a href="{{ route('cheques.create') }}" class="btn btn-primary">Registrar Cheque</a>
    </div>
    @livewire('⚡cheque-table')
@endsection
