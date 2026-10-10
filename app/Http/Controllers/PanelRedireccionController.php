<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Redirige al panel correspondiente según el rol autenticado (RF-01). */
class PanelRedireccionController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return $request->user()->esAdministrador()
            ? redirect()->route('admin.panel')
            : redirect()->route('aspirante.panel');
    }
}
