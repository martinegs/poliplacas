<?php

use App\Http\Controllers\EntidadController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ChequeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/entidades', [EntidadController::class, 'index'])->name('entidades.index');
Route::get('/entidades/create', [EntidadController::class, 'create'])->name('entidades.create');
Route::post('/entidades', [EntidadController::class, 'store'])->name('entidades.store');
Route::get('/entidades/{entidad}/edit', [EntidadController::class, 'edit'])->name('entidades.edit');
Route::put('/entidades/{entidad}', [EntidadController::class, 'update'])->name('entidades.update');
Route::delete('/entidades/{entidad}', [EntidadController::class, 'destroy'])->name('entidades.destroy');

Route::get('/caja', [CajaController::class, 'index'])->name('cajas.index');
Route::get('/caja/create', [CajaController::class, 'create'])->name('cajas.create');
Route::get('/caja/{caja}/edit', [CajaController::class, 'edit'])->name('cajas.edit');

Route::get('/cheques', [ChequeController::class, 'index'])->name('cheques.index');
Route::get('/cheques/create', [ChequeController::class, 'create'])->name('cheques.create');
Route::get('/cheques/{cheque}/edit', [ChequeController::class, 'edit'])->name('cheques.edit');
