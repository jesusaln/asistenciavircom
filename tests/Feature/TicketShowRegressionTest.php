<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Test de regresión para los 500 errors en TicketController@show.
 *
 * BUG HISTÓRICO [2026-07-23]:
 * - producción tenía `'poliza:id,numero,folio'` en eager loading
 *   pero la tabla polizas_servicio no tiene columna "numero".
 * - `'comentarios' => ... 'comentario', 'created_at'` pero la columna es "contenido".
 * - `'citas:id,ticket_id,fecha,hora'` pero la columna es "fecha_hora".
 *
 * Este test verifica directamente el schema y los queries que ejecuta el show(),
 * evitando depender de columnas de clientes que cambian entre entornos.
 */
class TicketShowRegressionTest extends TestCase
{
    /**
     * Documenta y verifica las columnas REALES que TicketController@show usa.
     * Si alguno de estos falla, significa que el código del controller referencia
     * una columna inexistente y va a generar 500 en producción.
     */
    public function test_show_uses_real_columns_polizas_servicio(): void
    {
        if (!Schema::hasTable('polizas_servicio')) {
            $this->markTestSkipped('Tabla polizas_servicio no existe en este entorno');
        }

        // El bug era usar 'numero' que no existe
        $this->assertFalse(
            Schema::hasColumn('polizas_servicio', 'numero'),
            'REGRESIÓN: la tabla polizas_servicio NO debe tener columna "numero" (causa 500 al abrir ticket con poliza). Si este test falla con NOT FALSE, alguien re-agregó la columna.'
        );

        // Las columnas correctas
        $this->assertTrue(Schema::hasColumn('polizas_servicio', 'folio'), 'polizas_servicio.folio requerida');
        $this->assertTrue(Schema::hasColumn('polizas_servicio', 'nombre'), 'polizas_servicio.nombre requerida');
    }

    public function test_show_uses_real_columns_ticket_comments(): void
    {
        if (!Schema::hasTable('ticket_comments')) {
            $this->markTestSkipped('Tabla ticket_comments no existe');
        }

        // El bug era usar 'comentario' que no existe (la real es 'contenido')
        $this->assertFalse(
            Schema::hasColumn('ticket_comments', 'comentario'),
            'REGRESIÓN: ticket_comments NO debe tener columna "comentario" (causa 500). La columna real es "contenido".'
        );
        $this->assertTrue(Schema::hasColumn('ticket_comments', 'contenido'), 'ticket_comments.contenido requerida');
    }

    public function test_show_uses_real_columns_citas(): void
    {
        if (!Schema::hasTable('citas')) {
            $this->markTestSkipped('Tabla citas no existe');
        }

        // El bug era usar 'fecha' y 'hora' separadas (la real es 'fecha_hora')
        $this->assertFalse(
            Schema::hasColumn('citas', 'fecha'),
            'REGRESIÓN: citas NO debe tener columna "fecha" aislada (causa 500). La columna real es "fecha_hora".'
        );
        $this->assertFalse(
            Schema::hasColumn('citas', 'hora'),
            'REGRESIÓN: citas NO debe tener columna "hora" aislada (causa 500). La columna real es "fecha_hora".'
        );
        $this->assertTrue(Schema::hasColumn('citas', 'fecha_hora'), 'citas.fecha_hora requerida');
    }

    /**
     * Verifica que el código fuente del TicketController no referencia columnas inexistentes.
     * Si alguien restaura el bug, este test falla antes de que llegue a producción.
     */
    public function test_controller_no_references_nonexistent_poliza_columns(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/TicketController.php'));

        // Buscar el patrón 'poliza:id,...,...' que referencia columnas en eager loading
        preg_match_all("/'poliza:id,([^']+)'/", $source, $matches);

        $this->assertNotEmpty($matches[1], 'No se encontró eager load de poliza en el controller');

        foreach ($matches[1] as $columns) {
            $columnsList = explode(',', $columns);
            $this->assertNotContains(
                'numero',
                $columnsList,
                "REGRESIÓN: TicketController eager-loads poliza con columna 'numero' que no existe. Columnas referenciadas: {$columns}"
            );
        }
    }

    public function test_controller_no_references_nonexistent_comentario_column(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/TicketController.php'));

        // Buscar el select de comentarios
        $this->assertStringNotContainsString(
            "'comentario', 'created_at'",
            $source,
            "REGRESIÓN: TicketController selecciona 'comentario' en ticket_comments pero la columna real es 'contenido'."
        );
    }

    public function test_controller_no_references_nonexistent_cita_columns(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/TicketController.php'));

        // Buscar el patrón 'citas:id,...'
        preg_match_all("/'citas:id,([^']+)'/", $source, $matches);

        foreach ($matches[1] as $columns) {
            $columnsList = explode(',', $columns);
            $this->assertNotContains(
                'fecha',
                $columnsList,
                "REGRESIÓN: TicketController eager-loads citas con columna 'fecha' que no existe. Columnas: {$columns}"
            );
            $this->assertNotContains(
                'hora',
                $columnsList,
                "REGRESIÓN: TicketController eager-loads citas con columna 'hora' que no existe. Columnas: {$columns}"
            );
        }
    }
}