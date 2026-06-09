<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GuiaTecnicaController extends Controller
{
    public function index()
    {
        return \App\Models\GuiaTecnica::all();
    }
}
