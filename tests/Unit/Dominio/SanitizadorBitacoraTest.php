<?php

namespace Tests\Unit\Dominio;

use App\Dominio\Seguridad\SanitizadorBitacora;
use PHPUnit\Framework\TestCase;

/**
 * PU-SEG-01 a PU-SEG-03. RNF-03, registros sin datos sensibles.
 */
class SanitizadorBitacoraTest extends TestCase
{
    /** PU-SEG-01 */
    public function test_omite_claves_sensibles_incluso_anidadas(): void
    {
        $limpio = (new SanitizadorBitacora())->sanitizar([
            'accion' => 'login',
            'password' => 'Secreta123',
            'datos' => ['correo' => 'a@b.com', 'api_key' => 'sk-xxx', 'id_modulo' => 4],
        ]);

        $this->assertSame('login', $limpio['accion']);
        $this->assertSame('[OMITIDO]', $limpio['password']);
        $this->assertSame('[OMITIDO]', $limpio['datos']['correo']);
        $this->assertSame('[OMITIDO]', $limpio['datos']['api_key']);
        $this->assertSame(4, $limpio['datos']['id_modulo']);
    }

    /** PU-SEG-02 */
    public function test_enmascara_correos_y_trunca_textos(): void
    {
        $limpio = (new SanitizadorBitacora(20))->sanitizar(['detalle' => 'escribió juan@example.com en el campo de comentarios']);

        $this->assertStringNotContainsString('juan@example.com', $limpio['detalle']);
        $this->assertSame(21, mb_strlen($limpio['detalle']));
    }

    /** PU-SEG-03 */
    public function test_anonimiza_direcciones_ip(): void
    {
        $sanitizador = new SanitizadorBitacora();

        $this->assertSame('192.168.10.0', $sanitizador->anonimizarIp('192.168.10.77'));
        $this->assertSame('2001:db8:85a3::', $sanitizador->anonimizarIp('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
        $this->assertNull($sanitizador->anonimizarIp('no-es-ip'));
        $this->assertNull($sanitizador->anonimizarIp(null));
    }
}
