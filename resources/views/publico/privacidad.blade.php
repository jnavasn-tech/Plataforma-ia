@extends('layouts.app')
@section('titulo', 'Aviso de privacidad')
@section('contenido')
<article class="bg-white p-4 rounded-3 shadow-sm">
    <h1 class="h3">Aviso de privacidad y tratamiento de datos</h1>
    <p class="text-muted small">Versión {{ config('plataforma.privacidad.version_aviso') }}. [COMPLETAR: revisar con Grupo Ilimitado, S. A. antes de la validación con usuarios]</p>
    <h2 class="h5 mt-3">Finalidad</h2>
    <p>Los datos se utilizan exclusivamente para diagnosticar competencias informáticas, generar rutas de aprendizaje personalizadas y apoyar el proceso de selección de Grupo Ilimitado, S. A.</p>
    <h2 class="h5">Datos que se recopilan</h2>
    <p>Nombre, correo electrónico, teléfono (opcional), respuestas del diagnóstico, avance formativo y evaluaciones. No se solicitan documentos de identificación ni datos sensibles.</p>
    <h2 class="h5">Uso de inteligencia artificial</h2>
    <p>Para generar interpretaciones, rutas y retroalimentación se envían a un proveedor de IA únicamente puntajes, niveles y nombres de áreas o módulos. Nunca se envían su nombre, correo, teléfono ni contraseña. La IA no toma decisiones de contratación: esa decisión corresponde exclusivamente al personal de Recursos Humanos.</p>
    <h2 class="h5">Conservación y derechos</h2>
    <p>Puede solicitar la consulta, rectificación o eliminación de sus datos al administrador de la plataforma. [COMPLETAR: plazo de conservación y canal de contacto]</p>
    <h2 class="h5">Personas menores de edad</h2>
    <p>Si usted tiene 17 años, su registro requiere el consentimiento de su madre, padre o tutor.</p>
</article>
@endsection
