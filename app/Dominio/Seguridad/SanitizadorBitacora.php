<?php

namespace App\Dominio\Seguridad;

/**
 * Elimina datos sensibles antes de escribir en la bitácora o en los registros técnicos
 * (privacidad por diseño, "registros sin datos sensibles").
 */
final class SanitizadorBitacora
{
    private const PATRON_CLAVE_SENSIBLE = '/(pass|contrase|token|secret|api[_-]?key|correo|email|telefono|nombre|dpi|authorization|cookie|session)/i';

    private const PATRON_CORREO = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';

    public function __construct(private readonly int $longitudMaxima = 200)
    {
    }

    /**
     * @param  array<string,mixed>  $detalle
     * @return array<string,mixed>
     */
    public function sanitizar(array $detalle): array
    {
        $limpio = [];
        foreach ($detalle as $clave => $valor) {
            if (is_string($clave) && preg_match(self::PATRON_CLAVE_SENSIBLE, $clave) === 1) {
                $limpio[$clave] = '[OMITIDO]';

                continue;
            }
            $limpio[$clave] = match (true) {
                is_array($valor) => $this->sanitizar($valor),
                is_string($valor) => $this->limpiarTexto($valor),
                is_scalar($valor) || $valor === null => $valor,
                default => '[NO SERIALIZABLE]',
            };
        }

        return $limpio;
    }

    /** Anonimiza la IP: IPv4 conserva los tres primeros octetos; IPv6, los tres primeros grupos. */
    public function anonimizarIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $octetos = explode('.', $ip);
            $octetos[3] = '0';

            return implode('.', $octetos);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $grupos = str_split(bin2hex((string) inet_pton($ip)), 4);

            return implode(':', array_map(fn (string $g) => ltrim($g, '0') ?: '0', array_slice($grupos, 0, 3))).'::';
        }

        return null;
    }

    private function limpiarTexto(string $texto): string
    {
        $texto = preg_replace(self::PATRON_CORREO, '[CORREO OMITIDO]', $texto) ?? '';

        return mb_strlen($texto) > $this->longitudMaxima ? mb_substr($texto, 0, $this->longitudMaxima).'…' : $texto;
    }
}
