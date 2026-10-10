<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Aspirante;
use App\Http\Controllers\Auth\RecuperacionController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\Auth\SesionController;
use App\Http\Controllers\Auth\VerificacionController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\PanelRedireccionController;
use App\Http\Controllers\PerfilUsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web de la plataforma (trazabilidad: ver Anexo J del Capítulo V)
|--------------------------------------------------------------------------
*/

// Pantalla 1 (inicio) y aviso de privacidad.
Route::get('/', [InicioController::class, 'index'])->name('inicio');
Route::view('/privacidad', 'publico.privacidad')->name('privacidad');

// CU-01: registro, inicio de sesión y recuperación de contraseña (pantallas 2, 3 y 4).
Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:registro')->name('registro.store');
    Route::get('/iniciar-sesion', [SesionController::class, 'create'])->name('login');
    Route::post('/iniciar-sesion', [SesionController::class, 'store'])->name('login.store');
    Route::get('/recuperar-contrasena', [RecuperacionController::class, 'create'])->name('password.request');
    Route::post('/recuperar-contrasena', [RecuperacionController::class, 'store'])->middleware('throttle:recuperacion')->name('password.email');
});

// El enlace de restablecimiento también funciona con una sesión abierta (CU-08, flujo alterno).
Route::get('/restablecer-contrasena/{token}', [RecuperacionController::class, 'edit'])->name('password.reset');
Route::post('/restablecer-contrasena', [RecuperacionController::class, 'update'])->middleware('throttle:recuperacion')->name('password.update');

Route::middleware('auth')->group(function () {
    Route::post('/cerrar-sesion', [SesionController::class, 'destroy'])->name('logout');
    Route::get('/correo/verificar', [VerificacionController::class, 'aviso'])->name('verification.notice');
    Route::get('/correo/verificar/{id}/{hash}', [VerificacionController::class, 'verificar'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/correo/reenviar', [VerificacionController::class, 'reenviar'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('/panel', PanelRedireccionController::class)->name('panel');

    // CU-08: actualizar perfil y contraseña (pantalla 17), para ambos roles.
    Route::get('/mi-perfil', [PerfilUsuarioController::class, 'edit'])->name('perfil.edit');
    Route::put('/mi-perfil', [PerfilUsuarioController::class, 'update'])->name('perfil.update');
    Route::put('/mi-perfil/contrasena', [PerfilUsuarioController::class, 'actualizarContrasena'])->name('perfil.contrasena');
    Route::post('/mi-perfil/enlace-restablecimiento', [PerfilUsuarioController::class, 'enviarEnlace'])->middleware('throttle:recuperacion')->name('perfil.enlace');
});

// Rol aspirante: CU-02, CU-03 (resultado), CU-04 (pantallas 5 a 16).
Route::middleware(['auth', 'verified', 'rol:aspirante'])->prefix('aspirante')->name('aspirante.')->group(function () {
    Route::get('/panel', [Aspirante\PanelController::class, 'index'])->name('panel');

    Route::get('/diagnostico/{tipo}/instrucciones', [Aspirante\DiagnosticoController::class, 'instrucciones'])->whereIn('tipo', ['pretest', 'postest'])->name('diagnostico.instrucciones');
    Route::post('/diagnostico/{tipo}', [Aspirante\DiagnosticoController::class, 'iniciar'])->whereIn('tipo', ['pretest', 'postest'])->name('diagnostico.iniciar');
    Route::get('/diagnostico/sesion/{diagnostico}', [Aspirante\DiagnosticoController::class, 'mostrar'])->name('diagnostico.mostrar');
    Route::post('/diagnostico/sesion/{diagnostico}/responder', [Aspirante\DiagnosticoController::class, 'responder'])->middleware('throttle:diagnostico')->name('diagnostico.responder');
    Route::get('/resultados/{diagnostico}', [Aspirante\ResultadoController::class, 'show'])->name('resultados');

    Route::get('/ruta', [Aspirante\RutaController::class, 'show'])->name('ruta');
    Route::get('/ruta/modulos/{rutaModulo}', [Aspirante\ModuloController::class, 'show'])->name('modulo');
    Route::get('/ruta/modulos/{rutaModulo}/recursos/{recurso}', [Aspirante\RecursoController::class, 'show'])->name('recurso');
    Route::post('/ruta/modulos/{rutaModulo}/recursos/{recurso}/completar', [Aspirante\RecursoController::class, 'completar'])->name('recurso.completar');
    Route::post('/ruta/modulos/{rutaModulo}/recursos/{recurso}/reportar', [Aspirante\RecursoController::class, 'reportar'])->name('recurso.reportar');
    Route::get('/ruta/modulos/{rutaModulo}/evaluacion', [Aspirante\EvaluacionController::class, 'create'])->name('evaluacion.create');
    Route::post('/ruta/modulos/{rutaModulo}/evaluacion', [Aspirante\EvaluacionController::class, 'store'])->middleware('throttle:evaluacion')->name('evaluacion.store');
    Route::get('/evaluaciones/{evaluacion}', [Aspirante\EvaluacionController::class, 'show'])->name('evaluacion.show');
    Route::post('/ruta/modulos/{rutaModulo}/valoracion', [Aspirante\ValoracionController::class, 'store'])->name('valoracion.store');
    Route::get('/progreso', [Aspirante\ProgresoController::class, 'index'])->name('progreso');
});

// Rol administrador RRHH: CU-05, CU-06, CU-07 y CU-09 propuesto (pantallas 18 a 25).
Route::middleware(['auth', 'verified', 'rol:admin_rrhh'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\PanelController::class, 'index'])->name('panel');

    Route::get('/aspirantes', [Admin\AspiranteController::class, 'index'])->name('aspirantes.index');
    Route::get('/aspirantes/{aspirante}', [Admin\AspiranteController::class, 'show'])->name('aspirantes.show');
    Route::put('/aspirantes/{aspirante}/postulaciones/{postulacion}/estado', [Admin\AspiranteController::class, 'actualizarEstado'])->name('aspirantes.estado');
    Route::post('/aspirantes/{aspirante}/proceso', [Admin\AspiranteController::class, 'asignarProceso'])->name('aspirantes.proceso');
    Route::post('/aspirantes/{aspirante}/mensaje', [Admin\AspiranteController::class, 'enviarMensaje'])->name('aspirantes.mensaje');
    Route::post('/aspirantes/{aspirante}/invitacion', [Admin\AspiranteController::class, 'reenviarInvitacion'])->name('aspirantes.invitacion');
    Route::put('/perfiles/{perfil}/control', [Admin\AspiranteController::class, 'registrarControl'])->name('perfiles.control');

    Route::resource('procesos', Admin\ProcesoController::class)->only(['index', 'store', 'update'])->parameters(['procesos' => 'proceso']);
    Route::resource('modulos', Admin\ModuloController::class)->except(['show', 'destroy'])->parameters(['modulos' => 'modulo']);
    Route::resource('recursos', Admin\RecursoController::class)->except(['show', 'destroy'])->parameters(['recursos' => 'recurso']);
    Route::patch('/recursos/{recurso}/estado', [Admin\RecursoController::class, 'cambiarEstado'])->name('recursos.estado');
    Route::resource('preguntas', Admin\PreguntaController::class)->except(['show', 'destroy'])->parameters(['preguntas' => 'pregunta']);
    Route::patch('/preguntas/{pregunta}/estado', [Admin\PreguntaController::class, 'cambiarEstado'])->name('preguntas.estado');

    Route::get('/reportes', [Admin\ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar', [Admin\ReporteController::class, 'exportar'])->middleware('throttle:reportes')->name('reportes.exportar');

    Route::get('/prompts', [Admin\PromptController::class, 'index'])->name('prompts.index');
    Route::get('/prompts/crear', [Admin\PromptController::class, 'create'])->name('prompts.create');
    Route::post('/prompts', [Admin\PromptController::class, 'store'])->name('prompts.store');
    Route::patch('/prompts/{prompt}/activar', [Admin\PromptController::class, 'activar'])->name('prompts.activar');

    Route::get('/bitacora', [Admin\BitacoraController::class, 'index'])->name('bitacora.index');
});
