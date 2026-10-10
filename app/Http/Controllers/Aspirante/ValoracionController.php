<?php

namespace App\Http\Controllers\Aspirante;

use App\Enums\EstadoRutaModulo;
use App\Http\Controllers\Controller;
use App\Models\RutaModulo;
use App\Models\ValoracionModulo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Opinión del aspirante al finalizar cada módulo (indicador de satisfacción del Capítulo I). */
class ValoracionController extends Controller
{
    public function store(Request $request, RutaModulo $rutaModulo): RedirectResponse
    {
        $this->authorize('avanzar', $rutaModulo);
        abort_unless($rutaModulo->estado === EstadoRutaModulo::Completado, 422, 'El módulo aún no ha concluido.');

        $datos = $request->validate([
            'puntuacion' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);

        ValoracionModulo::updateOrCreate(
            ['id_ruta_modulo' => $rutaModulo->id_ruta_modulo],
            ['id_usuario' => $request->user()->id_usuario] + $datos
        );

        return back()->with('estado', 'Gracias por su opinión sobre el módulo.');
    }
}
