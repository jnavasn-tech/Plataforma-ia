<?php

namespace Tests\Feature\Infraestructura;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/** CP-ERR-01, CP-ERR-02 y CP-API-01. Manejo centralizado de errores y endpoint de salud (RNF-01). */
class ErroresYApiTest extends TestCase
{
    use RefreshDatabase;

    /** CP-ERR-01 */
    public function test_pagina_no_encontrada_en_espanol(): void
    {
        $this->get('/ruta-que-no-existe')->assertNotFound()->assertSee('Página no encontrada');
    }

    /** CP-ERR-02 */
    public function test_error_interno_no_expone_detalles_tecnicos(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/prueba-error-interno', fn () => throw new RuntimeException('Detalle interno SQLSTATE secreto'));

        $this->get('/prueba-error-interno')
            ->assertStatus(500)
            ->assertSee('Ocurrió un error inesperado')
            ->assertDontSee('SQLSTATE secreto');
    }

    /** CP-API-01 */
    public function test_endpoint_de_salud_responde_en_formato_json(): void
    {
        $this->getJson(route('api.salud'))
            ->assertOk()
            ->assertJson(['estado' => 'ok', 'base_datos' => 'ok', 'ia_configurada' => false])
            ->assertJsonStructure(['estado', 'base_datos', 'ia_configurada', 'fecha']);
    }
}
