<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Models\SatEstado;

class SatCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRegimenes();
        $this->seedUsosCfdi();
        $this->seedEstados();
        $this->seedMetodosPago();
        $this->seedFormasPago();
        $this->seedClavesUnidad();
        $this->seedImpuestos();
    }

    private function seedRegimenes(): void
    {
        $regimenes = [
            ['clave' => '601', 'descripcion' => 'General de Ley Personas Morales', 'persona_fisica' => false, 'persona_moral' => true],
            ['clave' => '603', 'descripcion' => 'Personas Morales con Fines no Lucrativos', 'persona_fisica' => false, 'persona_moral' => true],
            ['clave' => '605', 'descripcion' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios', 'persona_fisica' => true, 'persona_moral' => false],
            ['clave' => '606', 'descripcion' => 'Arrendamiento', 'persona_fisica' => true, 'persona_moral' => false],
            ['clave' => '608', 'descripcion' => 'Demás ingresos', 'persona_fisica' => true, 'persona_moral' => false],
            ['clave' => '612', 'descripcion' => 'Personas Físicas con Actividades Empresariales y Profesionales', 'persona_fisica' => true, 'persona_moral' => false],
            ['clave' => '614', 'descripcion' => 'Ingresos por intereses', 'persona_fisica' => true, 'persona_moral' => false],
            ['clave' => '616', 'descripcion' => 'Sin obligaciones fiscales', 'persona_fisica' => true, 'persona_moral' => false],
            ['clave' => '621', 'descripcion' => 'Incorporación Fiscal', 'persona_fisica' => true, 'persona_moral' => false],
            ['clave' => '625', 'descripcion' => 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas', 'persona_fisica' => true, 'persona_moral' => false],
            ['clave' => '626', 'descripcion' => 'Régimen Simplificado de Confianza', 'persona_fisica' => true, 'persona_moral' => true],
        ];

        foreach ($regimenes as $r) {
            SatRegimenFiscal::updateOrCreate(['clave' => $r['clave']], $r);
        }
        $this->command->info('✓ SAT Regímenes Fiscales');
    }

    private function seedUsosCfdi(): void
    {
        $usos = [
            ['clave' => 'G01', 'descripcion' => 'Adquisición de mercancías', 'persona_fisica' => true, 'persona_moral' => true],
            ['clave' => 'G02', 'descripcion' => 'Devoluciones, descuentos o bonificaciones', 'persona_fisica' => true, 'persona_moral' => true],
            ['clave' => 'G03', 'descripcion' => 'Gastos en general', 'persona_fisica' => true, 'persona_moral' => true],
            ['clave' => 'I01', 'descripcion' => 'Construcciones', 'persona_fisica' => true, 'persona_moral' => true],
            ['clave' => 'I02', 'descripcion' => 'Mobiliario y equipo de oficina por inversiones', 'persona_fisica' => true, 'persona_moral' => true],
            ['clave' => 'I03', 'descripcion' => 'Equipo de transporte', 'persona_fisica' => true, 'persona_moral' => true],
            ['clave' => 'I04', 'descripcion' => 'Equipo de cómputo y accesorios', 'persona_fisica' => true, 'persona_moral' => true],
            ['clave' => 'I08', 'descripcion' => 'Otra maquinaria y equipo', 'persona_fisica' => true, 'persona_moral' => true],
            ['clave' => 'D01', 'descripcion' => 'Honorarios médicos, dentales y gastos hospitalarios', 'persona_fisica' => true, 'persona_moral' => false],
            ['clave' => 'CP01', 'descripcion' => 'Pagos', 'persona_fisica' => true, 'persona_moral' => true],
            ['clave' => 'CN01', 'descripcion' => 'Nómina', 'persona_fisica' => true, 'persona_moral' => false],
            ['clave' => 'S01', 'descripcion' => 'Sin efectos fiscales', 'persona_fisica' => true, 'persona_moral' => true],
        ];

        foreach ($usos as $u) {
            SatUsoCfdi::updateOrCreate(['clave' => $u['clave']], $u);
        }
        $this->command->info('✓ SAT Usos CFDI');
    }

    private function seedEstados(): void
    {
        $estados = [
            ['clave' => 'BCN', 'nombre' => 'Baja California'],
            ['clave' => 'BCS', 'nombre' => 'Baja California Sur'],
            ['clave' => 'SON', 'nombre' => 'Sonora'],
            ['clave' => 'SIN', 'nombre' => 'Sinaloa'],
            ['clave' => 'CHH', 'nombre' => 'Chihuahua'],
            ['clave' => 'COA', 'nombre' => 'Coahuila'],
            ['clave' => 'NLE', 'nombre' => 'Nuevo León'],
            ['clave' => 'TAM', 'nombre' => 'Tamaulipas'],
            ['clave' => 'DUR', 'nombre' => 'Durango'],
            ['clave' => 'ZAC', 'nombre' => 'Zacatecas'],
            ['clave' => 'SLP', 'nombre' => 'San Luis Potosí'],
            ['clave' => 'NAY', 'nombre' => 'Nayarit'],
            ['clave' => 'JAL', 'nombre' => 'Jalisco'],
            ['clave' => 'COL', 'nombre' => 'Colima'],
            ['clave' => 'MIC', 'nombre' => 'Michoacán'],
            ['clave' => 'GUA', 'nombre' => 'Guanajuato'],
            ['clave' => 'QUE', 'nombre' => 'Querétaro'],
            ['clave' => 'HID', 'nombre' => 'Hidalgo'],
            ['clave' => 'MEX', 'nombre' => 'Estado de México'],
            ['clave' => 'CDMX', 'nombre' => 'Ciudad de México'],
            ['clave' => 'MOR', 'nombre' => 'Morelos'],
            ['clave' => 'PUE', 'nombre' => 'Puebla'],
            ['clave' => 'TLA', 'nombre' => 'Tlaxcala'],
            ['clave' => 'VER', 'nombre' => 'Veracruz'],
            ['clave' => 'TAB', 'nombre' => 'Tabasco'],
            ['clave' => 'CAM', 'nombre' => 'Campeche'],
            ['clave' => 'YUC', 'nombre' => 'Yucatán'],
            ['clave' => 'ROO', 'nombre' => 'Quintana Roo'],
            ['clave' => 'GRO', 'nombre' => 'Guerrero'],
            ['clave' => 'OAX', 'nombre' => 'Oaxaca'],
            ['clave' => 'CHP', 'nombre' => 'Chiapas'],
            ['clave' => 'AGU', 'nombre' => 'Aguascalientes'],
        ];

        foreach ($estados as $e) {
            SatEstado::updateOrCreate(['clave' => $e['clave']], $e);
        }
        $this->command->info('✓ SAT Estados');
    }

    private function seedMetodosPago(): void
    {
        $metodos = [
            ['clave' => 'PUE', 'descripcion' => 'Pago en una sola exhibición'],
            ['clave' => 'PPD', 'descripcion' => 'Pago en parcialidades o diferido'],
        ];

        foreach ($metodos as $m) {
            DB::table('sat_metodos_pago')->updateOrInsert(['clave' => $m['clave']], array_merge($m, ['activo' => true, 'created_at' => now(), 'updated_at' => now()]));
        }
        $this->command->info('✓ SAT Métodos de Pago');
    }

    private function seedFormasPago(): void
    {
        $formas = [
            ['clave' => '01', 'descripcion' => 'Efectivo', 'bancarizado' => false, 'orden' => 1],
            ['clave' => '02', 'descripcion' => 'Cheque nominativo', 'bancarizado' => true, 'orden' => 2],
            ['clave' => '03', 'descripcion' => 'Transferencia electrónica de fondos', 'bancarizado' => true, 'orden' => 3],
            ['clave' => '04', 'descripcion' => 'Tarjeta de crédito', 'bancarizado' => true, 'orden' => 4],
            ['clave' => '28', 'descripcion' => 'Tarjeta de débito', 'bancarizado' => true, 'orden' => 5],
            ['clave' => '99', 'descripcion' => 'Por definir', 'bancarizado' => false, 'orden' => 99],
        ];

        foreach ($formas as $f) {
            DB::table('sat_formas_pago')->updateOrInsert(['clave' => $f['clave']], array_merge($f, ['activo' => true, 'created_at' => now(), 'updated_at' => now()]));
        }
        $this->command->info('✓ SAT Formas de Pago');
    }

    private function seedClavesUnidad(): void
    {
        $unidades = [
            ['clave' => 'H87', 'nombre' => 'Pieza'],
            ['clave' => 'E48', 'nombre' => 'Unidad de servicio'],
            ['clave' => 'ACT', 'nombre' => 'Actividad'],
            ['clave' => 'KGM', 'nombre' => 'Kilogramo'],
            ['clave' => 'LTR', 'nombre' => 'Litro'],
        ];

        foreach ($unidades as $u) {
            DB::table('sat_claves_unidad')->updateOrInsert(['clave' => $u['clave']], array_merge($u, ['activo' => true, 'uso_comun' => true, 'created_at' => now(), 'updated_at' => now()]));
        }
        $this->command->info('✓ SAT Claves de Unidad');
    }

    private function seedImpuestos(): void
    {
        $impuestos = [
            ['clave' => '001', 'descripcion' => 'ISR', 'retencion' => true, 'traslado' => false, 'local_o_federal' => 'federal'],
            ['clave' => '002', 'descripcion' => 'IVA', 'retencion' => false, 'traslado' => true, 'local_o_federal' => 'federal'],
            ['clave' => '003', 'descripcion' => 'IEPS', 'retencion' => false, 'traslado' => true, 'local_o_federal' => 'federal'],
        ];

        foreach ($impuestos as $i) {
            DB::table('sat_impuestos')->updateOrInsert(['clave' => $i['clave']], array_merge($i, ['created_at' => now(), 'updated_at' => now()]));
        }
        $this->command->info('✓ SAT Impuestos');
    }
}
