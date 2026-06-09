<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestContpaqiStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contpaqi:test-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle()
    {
        $this->info("Probando conexión con CONTPAQi Bridge...");

        try {
            $service = app(\App\Services\ContpaqiService::class);
            $status = $service->checkStatus();

            $this->table(['Key', 'Value'], collect($status)->map(fn($v, $k) => [$k, $v]));

            if (($status['status'] ?? '') === 'Online') {
                $this->info("✅ Conexión exitosa.");
            } else {
                $this->error("❌ Estado no Online.");
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
