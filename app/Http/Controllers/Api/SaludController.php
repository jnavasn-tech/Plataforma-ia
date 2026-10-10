<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/** GET /api/v1/salud: estado de la aplicación y de la base de datos (RNF-01). */
class SaludController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
            $baseDatos = 'ok';
        } catch (Throwable) {
            $baseDatos = 'error';
        }

        $estado = $baseDatos === 'ok' ? 'ok' : 'degradado';

        return response()->json([
            'estado' => $estado,
            'base_datos' => $baseDatos,
            'ia_configurada' => (bool) config('ia.habilitada') && filled(config('ia.openai.api_key')),
            'fecha' => now()->toIso8601String(),
        ], $estado === 'ok' ? 200 : 503);
    }
}
