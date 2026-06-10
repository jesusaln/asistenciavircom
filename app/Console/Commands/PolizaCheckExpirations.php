<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PolizaAutomationService;

class PolizaCheckExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polizas:check-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for expiring policies and sends WhatsApp notifications via PolizaAutomationService';

    /**
     * Execute the console command.
     */
    public function handle(PolizaAutomationService $service)
    {
        $this->info('Starting policy expiration check...');

        $service->processExpiringPolicies();

        $this->info('Policy expiration check completed.');
        return Command::SUCCESS;
    }
}
