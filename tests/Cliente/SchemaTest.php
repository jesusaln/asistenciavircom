<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class SchemaTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ejecutar seeders necesarios para catálogos SAT
        $this->seed([
            \Database\Seeders\DatabaseSeeder::class,
        ]);
    }

    public function test_tabla_clientes_tiene_estructura_esperada(): void
    {
        $this->assertTrue(Schema::hasTable('clientes'), 'La tabla "clientes" debería existir.');

        $columns = [
            'id',
            'nombre_razon_social',
            'tipo_persona',
            'tipo_identificacion',
            'identificacion',
            'curp',
            'rfc',
            'regimen_fiscal',
            'uso_cfdi',
            'email',
            'telefono',
            'calle',
            'numero_exterior',
            'numero_interior',
            'colonia',
            'codigo_postal',
            'municipio',
            'estado',
            'pais',
            'notas',
            'activo',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('clientes', $column),
                "Falta la columna 'clientes.{$column}'"
            );
        }

        $columnsInfo = DB::select('PRAGMA table_info(clientes)');

        // Función local para obtener info de columna
        $getColumnInfo = function ($name) use ($columnsInfo) {
            foreach ($columnsInfo as $col) {
                if ($col->name === $name) {
                    return $col;
                }
            }
            return null;
        };

        // Validar id es pk y autoincrement
        $id = $getColumnInfo('id');
        $this->assertNotNull($id);
        $this->assertEquals(1, $id->pk);

        // Validar tipo rfc es VARCHAR (sin tamaño en SQLite)
        $rfc = $getColumnInfo('rfc');
        $this->assertNotNull($rfc);
        $this->assertStringContainsString('VARCHAR', strtoupper($rfc->type));

        // Validar activo es integer y default 1 (limpiando comillas)
        $activo = $getColumnInfo('activo');
        $this->assertNotNull($activo);
        $this->assertStringContainsString('INT', strtoupper($activo->type));
        $defaultActivo = $this->cleanDefault($activo->dflt_value);
        $this->assertEquals('1', $defaultActivo);

        // Validar país default 'México' (limpiando comillas)
        $pais = $getColumnInfo('pais');
        $this->assertNotNull($pais);
        $defaultPais = $this->cleanDefault($pais->dflt_value);
        $this->assertEquals('México', $defaultPais);

        // Validar notas tipo TEXT o VARCHAR
        $notas = $getColumnInfo('notas');
        $this->assertNotNull($notas);
        $this->assertTrue(
            str_contains(strtoupper($notas->type), 'TEXT') ||
                str_contains(strtoupper($notas->type), 'VARCHAR')
        );

        // Validar timestamps no tienen default
        $created = $getColumnInfo('created_at');
        $updated = $getColumnInfo('updated_at');
        $this->assertNotNull($created);
        $this->assertNull($created->dflt_value);
        $this->assertNotNull($updated);
        $this->assertNull($updated->dflt_value);
    }

    // Método privado para limpiar comillas simples de valores default
    private function cleanDefault(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }
        return trim($value, "'");
    }
}
