<?php

namespace App\Models;

use App\Enums\EstadoDiagnostico;
use App\Enums\TipoDiagnostico;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Sesión de diagnóstico (tabla original diagnosticos). RF-02, CU-02. */
class Diagnostico extends Model
{
    use HasFactory;

    protected $table = 'diagnosticos';

    protected $primaryKey = 'id_diagnostico';

    protected $fillable = ['id_usuario', 'id_proceso', 'tipo', 'estado', 'total_items', 'iniciado_en', 'ultima_actividad_en', 'finalizado_en'];

    protected $casts = [
        'id_usuario' => 'integer',
        'id_proceso' => 'integer',
        'total_items' => 'integer',
        'tipo' => TipoDiagnostico::class,
        'estado' => EstadoDiagnostico::class,
        'iniciado_en' => 'datetime',
        'ultima_actividad_en' => 'datetime',
        'finalizado_en' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaDiagnostico::class, 'id_diagnostico')->orderBy('orden');
    }

    public function perfil(): HasOne
    {
        return $this->hasOne(PerfilCompetencia::class, 'id_diagnostico');
    }

    public function respondidas(): int
    {
        return $this->respuestas()->whereNotNull('respondida_en')->count();
    }
}
