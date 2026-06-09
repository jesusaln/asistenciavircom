<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoreSchemaContractTest extends TestCase
{
    public function test_core_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('users'), 'Tabla users faltante.');
        $this->assertTrue(Schema::hasTable('empresas'), 'Tabla empresas faltante.');
        $this->assertTrue(Schema::hasTable('citas'), 'Tabla citas faltante.');
        $this->assertTrue(Schema::hasTable('prestamos'), 'Tabla prestamos faltante.');
        $this->assertTrue(Schema::hasTable('rentas'), 'Tabla rentas faltante.');
        $this->assertTrue(Schema::hasTable('traspasos'), 'Tabla traspasos faltante.');
        $this->assertTrue(Schema::hasTable('traspaso_items'), 'Tabla traspaso_items faltante.');
    }

    public function test_critical_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'ine'), 'users.ine faltante.');
        $this->assertTrue(Schema::hasColumn('users', 'face_reference_path'), 'users.face_reference_path faltante.');
        $this->assertTrue(Schema::hasColumn('users', 'face_descriptor'), 'users.face_descriptor faltante.');
        $this->assertTrue(Schema::hasColumn('prestamos', 'empleado_id'), 'prestamos.empleado_id faltante.');
        $this->assertTrue(Schema::hasColumn('citas', 'deleted_at'), 'citas.deleted_at faltante.');
        $this->assertTrue(Schema::hasColumn('almacenes', 'estado'), 'almacenes.estado faltante.');
        $this->assertTrue(Schema::hasColumn('empresas', 'nombre_razon_social'), 'empresas.nombre_razon_social faltante.');
        $this->assertTrue(Schema::hasColumn('empresas', 'whatsapp_default_language'), 'empresas.whatsapp_default_language faltante.');
        $this->assertTrue(Schema::hasColumn('compra_items', 'compra_id'), 'compra_items.compra_id faltante.');
        $this->assertTrue(Schema::hasColumn('traspasos', 'cantidad_total'), 'traspasos.cantidad_total faltante.');
    }
}
