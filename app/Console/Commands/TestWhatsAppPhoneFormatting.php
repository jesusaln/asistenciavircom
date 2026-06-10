<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class TestWhatsAppPhoneFormatting extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'whatsapp:test-phone-formatting';

    /**
     * The console command description.
     */
    protected $description = 'Probar la función de formateo de números de teléfono para WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Probando formateo de números de teléfono para WhatsApp...');
        $this->newLine();

        $results = WhatsAppService::testPhoneFormatting();

        $this->line('<fg=yellow>RESULTADOS DE FORMATEO:</>');
        $this->line(str_repeat('=', 80));

        foreach ($results as $result) {
            $status = $result['valid']
                ? '<fg=green>✅ VÁLIDO</>'
                : '<fg=red>❌ INVÁLIDO</>';

            $this->line("Original:  <fg=cyan>{$result['original']}</>");
            $this->line("Formateado: <fg=yellow>{$result['formatted']}</>");
            $this->line("Estado: {$status}");
            $this->line(str_repeat('-', 50));
        }

        $validos = count(array_filter($results, fn($r) => $r['valid']));
        $total = count($results);

        $this->newLine();
        $this->line("Resumen: <fg=cyan>{$validos}/{$total}</> números válidos después del formateo");

        if ($validos === $total) {
            $this->info('✅ Todos los números se formatearon correctamente');
            return 0;
        } else {
            $this->warn('⚠️  Algunos números podrían necesitar revisión manual');
            return 1;
        }
    }
}
