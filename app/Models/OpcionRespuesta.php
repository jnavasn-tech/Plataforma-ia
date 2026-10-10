<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpcionRespuesta extends Model
{
    use HasFactory;

    protected $table = 'opciones_respuesta';

    protected $primaryKey = 'id_opcion';

    protected $fillable = ['id_pregunta', 'texto', 'es_correcta', 'orden'];

    protected $casts = ['es_correcta' => 'boolean'];

    /** El indicador de respuesta correcta nunca se serializa hacia la vista del aspirante. */
    protected $hidden = ['es_correcta'];

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'id_pregunta');
    }
}
