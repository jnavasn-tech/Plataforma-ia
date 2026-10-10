<?php

namespace App\Services\Bitacora;

use App\Dominio\Seguridad\SanitizadorBitacora;
use App\Models\Bitacora;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Registro centralizado de la bitácora de actividad (CU-01, CU-06, RF-08).
 * Sanitiza el detalle y anonimiza la IP; un fallo de bitácora nunca interrumpe la operación principal.
 */
class RegistradorBitacora
{
    public function __construct(private readonly SanitizadorBitacora $sanitizador)
    {
    }

    /** @param array<string,mixed> $detalle */
    public function registrar(string $accion, ?Usuario $usuario = null, ?Model $entidad = null, array $detalle = [], ?Request $request = null): void
    {
        try {
            $request ??= request();
            Bitacora::create([
                'id_usuario' => $usuario?->id_usuario,
                'accion' => mb_substr($accion, 0, 60),
                'entidad' => $entidad ? class_basename($entidad) : null,
                'id_entidad' => $entidad?->getKey(),
                'detalle_json' => $detalle === [] ? null : $this->sanitizador->sanitizar($detalle),
                'ip_anonimizada' => $this->sanitizador->anonimizarIp($request?->ip()),
            ]);
        } catch (Throwable $e) {
            Log::warning('No fue posible registrar la bitácora', ['accion' => $accion, 'error' => class_basename($e)]);
        }
    }
}
