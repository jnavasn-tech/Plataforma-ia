<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        // Las vistas usan @vite; en pruebas no se requiere el manifiesto de recursos compilados.
        $this->withoutVite();
    }
}
