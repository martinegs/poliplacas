<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function index()
    {
        return view('cajas.index');
    }

    public function create()
    {
        return view('cajas.create');
    }

    public function edit(Caja $caja)
    {
        return view('cajas.edit', compact('caja'));
    }
}
