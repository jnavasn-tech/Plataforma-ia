<?php

namespace App\Http\Requests\Admin;

use App\Enums\UsoPregunta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Validación de preguntas del banco: entre 2 y 5 opciones y exactamente una correcta. */
class PreguntaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->esAdministrador();
    }

    /** Descarta opciones vacías y reajusta el índice de la opción correcta. */
    protected function prepareForValidation(): void
    {
        $opciones = (array) $this->input('opciones', []);
        $correcta = $this->input('correcta');
        $filtradas = [];
        $nuevaCorrecta = null;
        foreach ($opciones as $i => $texto) {
            if (is_string($texto) && trim($texto) !== '') {
                if ((string) $i === (string) $correcta) {
                    $nuevaCorrecta = count($filtradas);
                }
                $filtradas[] = trim($texto);
            }
        }
        $this->merge(['opciones' => $filtradas, 'correcta' => $nuevaCorrecta]);
    }

    public function rules(): array
    {
        return [
            'id_area' => ['required', 'integer', 'exists:areas_competencia,id_area'],
            'uso' => ['required', Rule::enum(UsoPregunta::class)],
            'id_modulo' => ['nullable', 'required_if:uso,evaluacion_modulo', 'integer', 'exists:modulos_contenido,id_modulo'],
            'dificultad' => ['required', 'integer', 'between:1,3'],
            'enunciado' => ['required', 'string', 'min:10', 'max:1000'],
            'explicacion' => ['nullable', 'string', 'max:1000'],
            'opciones' => ['required', 'array', 'min:2', 'max:5'],
            'opciones.*' => ['required', 'string', 'max:300'],
            'correcta' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'opciones.min' => 'La pregunta debe tener al menos dos opciones.',
            'correcta.required' => 'Indique cuál es la opción correcta.',
            'id_modulo.required_if' => 'Las preguntas de evaluación deben asociarse a un módulo.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $opciones = array_values((array) $this->input('opciones', []));
            if (! array_key_exists((int) $this->input('correcta'), $opciones)) {
                $validator->errors()->add('correcta', 'La opción correcta seleccionada no existe.');
            }
            if (count($opciones) !== count(array_unique(array_map('mb_strtolower', array_map('trim', $opciones))))) {
                $validator->errors()->add('opciones', 'Las opciones no deben repetirse.');
            }
        });
    }
}
