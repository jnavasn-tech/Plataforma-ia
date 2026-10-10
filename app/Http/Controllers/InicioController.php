<?php

namespace App\Http\Controllers;

use App\Models\AreaCompetencia;
use Illuminate\View\View;

/** Pantalla 1: página de inicio pública. */
class InicioController extends Controller
{
    public function index(): View
    {
        return view('publico.inicio', ['areas' => AreaCompetencia::activas()->get()]);
    }
}
