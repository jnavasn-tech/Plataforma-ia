<?php

namespace App\Models;

use App\Enums\EstadoRecurso;
use App\Enums\TipoRecurso;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Recurso de aprendizaje del catálogo (CU-07). */
class RecursoAprendizaje extends Model
{
    use HasFactory;

    protected $table = 'recursos_aprendizaje';

    protected $primaryKey = 'id_recurso';

    protected $fillable = ['id_modulo', 'titulo', 'descripcion', 'tipo', 'url', 'contenido', 'duracion_minutos', 'estado', 'creado_por'];

    protected $casts = ['id_modulo' => 'integer', 'tipo' => TipoRecurso::class, 'estado' => EstadoRecurso::class];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(ModuloContenido::class, 'id_modulo');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    public function esExterno(): bool
    {
        return $this->url !== null && $this->url !== '';
    }
}
