<?php

use App\Http\Controllers\Api\SaludController;
use Illuminate\Support\Facades\Route;

/*
| Endpoint de verificación de disponibilidad (RNF-01) para monitoreo externo y Docker healthcheck.
| No expone datos personales ni detalles de configuración.
*/
Route::get('/v1/salud', SaludController::class)->name('api.salud');
