<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/** Validación del registro (CU-01, pasos 2 y 3; flujo alterno de correo ya registrado). */
class RegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['correo' => mb_strtolower(trim((string) $this->input('correo')))]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:120', "regex:/^[\pL\s'.-]+$/u"],
            'correo' => ['required', 'string', 'email:rfc', 'max:150', 'unique:usuarios,correo'],
            'contrasena' => ['required', 'string', 'confirmed', Password::defaults()],
            'declara_elegibilidad' => ['accepted'],
            'acepta_privacidad' => ['accepted'],
            'es_menor_edad' => ['nullable', 'boolean'],
            'consentimiento_tutor' => ['nullable', 'accepted_if:es_menor_edad,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'correo.unique' => 'Este correo ya está registrado. ¿Desea iniciar sesión?',
            'nombre.regex' => 'El nombre solo puede contener letras, espacios, apóstrofos, puntos y guiones.',
            'declara_elegibilidad.accepted' => 'Debe confirmar que cumple los criterios de participación.',
            'acepta_privacidad.accepted' => 'Debe aceptar el aviso de privacidad y tratamiento de datos.',
            'consentimiento_tutor.accepted_if' => 'Si es menor de edad, se requiere el consentimiento de su madre, padre o tutor.',
        ];
    }
}
