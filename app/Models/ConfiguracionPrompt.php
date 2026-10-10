<?php

namespace App\Models;

use App\Enums\ClavePrompt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Prompt institucional versionado (RF-08). Solo una versión activa por clave. */
class ConfiguracionPrompt extends Model
{
    protected $table = 'configuraciones_prompt';

    protected $primaryKey = 'id_prompt';

    protected $fillable = ['clave', 'version', 'contenido', 'descripcion_cambio', 'activo', 'creado_por', 'activado_en'];

    protected $casts = ['clave' => ClavePrompt::class, 'activo' => 'boolean', 'activado_en' => 'datetime', 'version' => 'integer'];

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    public static function activo(ClavePrompt $clave): ?self
    {
        return static::where('clave', $clave->value)->where('activo', true)->orderByDesc('version')->first();
    }

    /** Identificador de versión que se guarda junto a cada resultado generado, por ejemplo "ruta-v2". */
    public function etiquetaVersion(): string
    {
        return $this->clave->value.'-v'.$this->version;
    }
}
