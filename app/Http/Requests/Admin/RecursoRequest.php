<?php

namespace App\Http\Requests\Admin;

use App\Enums\EstadoRecurso;
use App\Enums\TipoRecurso;
use App\Models\RecursoAprendizaje;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Validación del alta y edición de recursos (CU-07: campos obligatorios y URL duplicada). */
class RecursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->esAdministrador();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['url' => $this->filled('url') ? trim((string) $this->input('url')) : null]);
    }

    public function rules(): array
    {
        return [
            'id_modulo' => ['required', 'integer', 'exists:modulos_contenido,id_modulo'],
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'tipo' => ['required', Rule::enum(TipoRecurso::class)],
            'url' => ['nullable', 'url', 'regex:/^https?:\/\//i', 'max:500'],
            'contenido' => ['nullable', 'required_without:url', 'string', 'max:10000'],
            'duracion_minutos' => ['nullable', 'integer', 'between:1,600'],
            'estado' => ['sometimes', Rule::enum(EstadoRecurso::class)],
            'guardar_pendiente' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['contenido.required_without' => 'Ingrese una URL o el contenido de la lectura interna.'];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $url = $this->input('url');
            if (! $url) {
                return;
            }
            $actual = $this->route('recurso');
            $existente = RecursoAprendizaje::where('url', $url)
                ->when($actual, fn ($q) => $q->whereKeyNot($actual->id_recurso))
                ->first();
            if ($existente) {
                $validator->errors()->add('url', "Ya existe un recurso con esta URL: {$existente->titulo}. ¿Desea editarlo en su lugar?");
                session()->flash('recurso_duplicado', $existente->id_recurso);
            }
        });
    }
}
