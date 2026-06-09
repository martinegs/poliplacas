<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Entidad;

class EntidadController extends Controller
{
    public function index(){
        return view('entidades.index');
    }
    public function create(){
        return view('entidades.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'dni_cuit' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
            'coordenadas' => 'nullable|string|max:255',
        ]);

        Entidad::create($validated);

        return redirect()->route('entidades.index')->with('success', 'Entidad creada correctamente.');
    }

    public function edit(Entidad $entidad)
    {
        return view('entidades.edit', compact('entidad'));
    }

    public function update(Request $request, Entidad $entidad)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'dni_cuit' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
            'coordenadas' => 'nullable|string|max:255',
        ]);

        $entidad->update($validated);

        return redirect()->route('entidades.index')->with('success', 'Entidad actualizada correctamente.');
    }

    public function destroy(Entidad $entidad)
    {
        $entidad->delete();

        return redirect()->route('entidades.index')->with('success', 'Entidad eliminada correctamente.');
    }
}
