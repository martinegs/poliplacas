<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use Illuminate\Http\Request;

class ChequeController extends Controller
{
    public function index()
    {
        return view('cheques.index');
    }

    public function create()
    {
        return view('cheques.create');
    }

    public function edit(Cheque $cheque)
    {
        return view('cheques.edit', compact('cheque'));
    }
}
