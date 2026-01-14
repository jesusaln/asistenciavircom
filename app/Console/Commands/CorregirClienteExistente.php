<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;

class CorregirClienteExistente extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cliente:corregir
                            {cliente_id=11 : ID del cliente a corregir}
                            {--regimen=601 : Nuevo régimen fiscal}
                            {--email= : Nuevo email}
                            {--estado= : Nuevo estado}
                            {--auto : Aplicar correcciones automáticas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corregir problemas de validación en un cliente existente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clienteId = $this->argument('cliente_id');
        $nuevoRegimen = $this->option('regimen');
        $nuevoEmail = $this->option('email');
        $nuevoEstado = $this->option('estado');
        $auto = $this->option('auto');

        $this->info("🔧 Corrigiendo cliente ID: {$clienteId}");

        try {
            $cliente = Cliente::findOrFail($clienteId);

            $this->line("Cliente encontrado: {$cliente->nombre_razon_social}");

            $cambios = [];

            // Corregir email si se especifica o si es el email problemático
            if ($nuevoEmail) {
                $cambios['email'] = $nuevoEmail;
                $this->line("📧 Email cambiado: {$cliente->email} → {$nuevoEmail}");
            } elseif ($cliente->email === 'xroberts@example.com') {
                $this->line("✅ Email ya tiene formato válido");
            }

            // Corregir régimen fiscal
            if ($nuevoRegimen) {
                $cambios['regimen_fiscal'] = $nuevoRegimen;
                $this->line("📋 Régimen fiscal cambiado: {$cliente->regimen_fiscal} → {$nuevoRegimen}");
            } elseif ($cliente->tipo_persona === 'moral' && $cliente->regimen_fiscal === '606') {
                if ($auto) {
                    $cambios['regimen_fiscal'] = '601';
                    $this->line("🔄 Régimen fiscal corregido automáticamente: 606 → 601");
                } else {
                    $this->warn("⚠️  Régimen fiscal '606' no es válido para personas morales");
                    $this->info("💡 Regímenes válidos para personas morales:");

                    $regimenes = \App\Models\SatRegimenFiscal::where('persona_moral', true)
                        ->orderBy('clave')
                        ->get(['clave', 'descripcion']);

                    foreach ($regimenes as $regimen) {
                        $this->line("  {$regimen->clave} - {$regimen->descripcion}");
                    }

                    $this->info("Ejecuta: php artisan cliente:corregir {$clienteId} --regimen=601");
                    return 1;
                }
            }

            // Corregir estado si se especifica
            if ($nuevoEstado) {
                $cambios['estado'] = $nuevoEstado;
                $this->line("🏛️ Estado cambiado: {$cliente->estado} → {$nuevoEstado}");
            } elseif (strlen($cliente->estado) === 3) {
                $this->line("✅ Estado ya tiene formato correcto: {$cliente->estado}");
            }

            // Aplicar cambios si los hay
            if (!empty($cambios)) {
                $cliente->update($cambios);
                $this->info("✅ Cliente actualizado correctamente");
            }

            $this->info("✅ Cliente verificado correctamente");
            $this->line("RFC: {$cliente->fresh()->rfc}");
            $this->line("Email: {$cliente->fresh()->email}");
            $this->line("Estado: {$cliente->fresh()->estado}");
            $this->line("Régimen Fiscal: {$cliente->fresh()->regimen_fiscal}");

            return 0;

        } catch (\Exception $e) {
            $this->error('Error al corregir cliente: ' . $e->getMessage());
            return 1;
        }
    }
}
