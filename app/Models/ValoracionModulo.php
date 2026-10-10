<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Opinión del aspirante al final de cada módulo (variable dependiente: satisfacción). */
class ValoracionModulo extends Model
{
    protected $table = 'valoraciones_modulo';

    protected $primaryKey = 'id_valoracion';

    protected $fillable = ['id_ruta_modulo', 'id_usuario', 'puntuacion', 'comentario'];

    protected $casts = ['puntuacion' => 'integer'];

    public function rutaModulo(): BelongsTo
    {
        return $this->belongsTo(RutaModulo::class, 'id_ruta_modulo');
    }
}
