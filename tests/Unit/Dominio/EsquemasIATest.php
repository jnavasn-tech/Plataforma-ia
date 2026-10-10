<?php

namespace Tests\Unit\Dominio;

use App\Dominio\IA\EsquemasIA;
use PHPUnit\Framework\TestCase;

/**
 * PU-IA-12. CU-03. El esquema enviado al proveedor cumple las reglas del modo estricto.
 */
class EsquemasIATest extends TestCase
{
    /** PU-IA-12 */
    public function test_los_esquemas_estrictos_requieren_todas_las_propiedades(): void
    {
        foreach ([EsquemasIA::PERFIL, EsquemasIA::RUTA, EsquemasIA::RETROALIMENTACION] as $clave) {
            $formato = EsquemasIA::formatoRespuesta($clave);
            $this->assertSame('json_schema', $formato['type']);
            $this->assertTrue($formato['json_schema']['strict']);
            $this->verificarObjetos($formato['json_schema']['schema']);
        }
    }

    private function verificarObjetos(array $esquema): void
    {
        if (($esquema['type'] ?? null) === 'object') {
            $this->assertFalse($esquema['additionalProperties']);
            $this->assertSame(array_keys($esquema['properties']), $esquema['required']);
            $this->assertArrayNotHasKey('maxLength', $esquema);
            foreach ($esquema['properties'] as $propiedad) {
                $this->verificarObjetos($propiedad);
            }
        }
        if (($esquema['type'] ?? null) === 'array') {
            $this->verificarObjetos($esquema['items']);
        }
    }
}
