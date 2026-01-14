# Tests y calidad

## T-01 CI sin ejecucion de tests ni lint
- Archivo/ruta: `.github/workflows/deploy.yml`
- Descripción: El workflow solo despliega; no ejecuta tests ni validaciones de calidad.
- Severidad: Media
- Impacto: Riesgo de desplegar regresiones sin deteccion automatica.
- Sugerencia: Agregar un workflow separado para tests/lint antes de deploy.

## T-02 Pruebas presentes pero sin cobertura visible
- Archivo/ruta: `tests/`
- Descripción: Hay suites `Unit` y `Feature`, pero no hay reporte de cobertura ni control de minima.
- Severidad: Media
- Impacto: Dificil medir calidad real y detectar areas sin tests.
- Sugerencia: Integrar cobertura (phpunit + xdebug/pcov) y metas minimas.

## T-03 Falta de linting JS en scripts
- Archivo/ruta: `package.json`
- Descripción: No hay scripts de lint/format para el frontend.
- Severidad: Baja
- Impacto: Estilo inconsistente y errores de calidad en JS/Vue.
- Sugerencia: Agregar ESLint/Prettier y ejecutarlos en CI.

## T-04 Pint instalado pero no usado
- Archivo/ruta: `composer.json`
- Descripción: `laravel/pint` esta en dev, pero no se ejecuta en scripts ni CI.
- Severidad: Baja
- Impacto: Formato PHP inconsistente y diffs ruidosos.
- Sugerencia: Agregar script `lint:php` y ejecutarlo en CI.

## Comandos recomendados (si el entorno lo permite)
- `composer install`
- `npm ci`
- `php artisan test`
- `./vendor/bin/pint`
- `npm run build`
