# Plataforma web de diagnóstico y desarrollo de competencias informáticas

Prototipo funcional del trabajo de graduación *“Desarrollo e implementación de una plataforma web basada en
inteligencia artificial para el diagnóstico y desarrollo de competencias informáticas en jóvenes egresados del
nivel diversificado aspirantes a puestos de trabajo en Grupo Ilimitado, S. A.”* (Universidad Mariano Gálvez de
Guatemala, Facultad de Ingeniería en Sistemas de Información).

| Componente | Tecnología |
|---|---|
| Backend | PHP 8.1 (compatible con 8.2 y 8.3) · Laravel 10 |
| Frontend | Blade · HTML5 · CSS3 · JavaScript · Bootstrap 5.3 (compilado con Vite) |
| Base de datos | MySQL 8.0 (SQLite en memoria para pruebas automatizadas) |
| Inteligencia artificial | API de OpenAI · modelo configurable (por defecto `gpt-4o-mini`) con salida JSON estricta y *fallback* por reglas |
| Reportes | DomPDF (PDF) · PhpSpreadsheet (Excel) |
| Despliegue | Docker Compose: `web` (Nginx), `app` (PHP-FPM), `cola`, `programador`, `db` (MySQL) |

> **Aviso de soporte.** Laravel 10 dejó de recibir parches de seguridad el 4 de febrero de 2025, PHP 8.1 el
> 31 de diciembre de 2025 y MySQL 8.0 finalizó su ciclo de vida en abril de 2026. Se conservan por ser la línea
> base aprobada en el diseño; antes de un despliegue público se recomienda migrar a versiones con soporte
> (PHP 8.3 o superior, Laravel 12 o 13 y MySQL 8.4 LTS; ver Capítulo V, hallazgos H-19 y H-24).
> La imagen de MySQL puede cambiarse sin modificar código con la variable `MYSQL_VERSION`.

## 1. Requisitos

- Docker 24 o superior con Docker Compose v2, **o** PHP 8.1–8.3 con extensiones `pdo_mysql`, `pdo_sqlite`,
  `mbstring`, `intl`, `gd`, `zip`, `xml`, `bcmath`; Composer 2; Node.js 20 y MySQL 8.0.
- Clave de la API de OpenAI (opcional: sin clave la plataforma funciona con reglas determinísticas).

## 2. Instalación con Docker (recomendada)

```bash
cp .env.example .env
# Edite .env: DB_PASSWORD, DB_ROOT_PASSWORD y, si aplica, OPENAI_API_KEY.
docker compose build
docker compose run --rm app php artisan key:generate --show   # copie el valor en APP_KEY del archivo .env
docker compose up -d
docker compose exec app php artisan migrate --seed --force
# Opcional en desarrollo: servidor de correo de pruebas
docker compose --profile dev up -d mailpit                       # http://localhost:8025
```

La plataforma queda disponible en `http://localhost:8080` (puerto configurable con `WEB_PUERTO`).
El seeder crea el catálogo de demostración y, fuera de producción, tres usuarios ficticios
(`admin.demo@example.com`, `aspirante1.demo@example.com`, `aspirante2.demo@example.com`). Sus contraseñas se
toman de `DEMO_ADMIN_PASSWORD` y `DEMO_ASPIRANTE_PASSWORD` o se generan y muestran una sola vez en la consola.

## 3. Instalación local sin Docker

```bash
cp .env.example .env            # ajuste DB_HOST=127.0.0.1 y credenciales de MySQL
composer install
php artisan key:generate
npm install && npm run build
php artisan migrate --seed
php artisan serve               # http://127.0.0.1:8000
php artisan queue:work          # en otra terminal: interpretación del perfil y rutas con IA
```

## 4. Pruebas automatizadas

```bash
# Local
php artisan test                 # o: vendor/bin/phpunit --testdox
# Docker
docker compose --profile pruebas run --rm pruebas   # resultados en storage/logs/junit.xml y testdox.txt
```

- `tests/Unit/Dominio`: reglas puras (motor adaptativo, puntajes, niveles, rutas, validación de IA, privacidad).
- `tests/Feature`: integración y sistema (autenticación, roles, diagnóstico, rutas, evaluaciones, IA simulada
  con `Http::fake()`, reportes, catálogo, seguridad, errores y base de datos).
- Las pruebas no consumen la API real ni usan datos personales.

## 5. Integración con IA

| Variable | Uso |
|---|---|
| `IA_HABILITADA` | `false` desactiva la IA; todo se genera por reglas. |
| `OPENAI_API_KEY` | Clave privada (solo en el servidor). |
| `IA_MODELO` | Modelo (por defecto `gpt-4o-mini`). |
| `IA_TEMPERATURA`, `IA_MAX_TOKENS` | Parámetros de generación. |
| `IA_TIMEOUT`, `IA_REINTENTOS` | Tiempo de espera (s) y reintentos ante 429/5xx/conexión. |

Los prompts institucionales versionados están en `resources/prompts/` (versión 1) y se administran desde
**Prompts IA** en el panel. Los esquemas de entrada y salida y ejemplos ficticios están en `docs/ia/`.
La IA nunca recibe nombre, correo, teléfono ni contraseña, y no toma decisiones de contratación.

## 6. Estructura

```
app/Dominio        Reglas de negocio puras (sin framework): diagnóstico, perfil, rutas, IA, evaluación, reportes
app/Services       Casos de uso: orquestan dominio, base de datos, IA, bitácora y notificaciones
app/Http           Controladores (Auth, Aspirante, Admin, Api), middleware y validaciones
app/Models         Modelos Eloquent (21 tablas de dominio)
database           Migraciones, seeders de demostración y esquema SQL (database/sql/esquema_mysql.sql)
resources          Vistas Blade (25 pantallas), prompts y recursos del frontend
docker             Configuración de Nginx, PHP y MySQL
docs/ia            Esquemas JSON y ejemplos de la integración con IA
tests              Pruebas unitarias y de integración (PHPUnit)
```

## 7. Seguridad

Contraseñas con bcrypt; protección CSRF; consultas parametrizadas (Eloquent); escape de salida en Blade;
autorización por rol y por propiedad; bloqueo de 15 minutos tras 5 intentos fallidos; sesiones en base de datos
cifradas; cabeceras de seguridad (CSP, X-Frame-Options, nosniff, HSTS con HTTPS); bitácora sin datos sensibles;
manejo centralizado de excepciones. En producción: `APP_ENV=production`, `APP_DEBUG=false`,
`SESSION_SECURE_COOKIE=true` y HTTPS obligatorio.

## 8. Comandos útiles

```bash
php artisan diagnosticos:marcar-abandonados   # programado diariamente a las 02:00
php artisan route:list --except-vendor        # endpoints (Anexo J)
php artisan db:seed --class=CatalogoSeeder    # catálogo sin usuarios de demostración
```
