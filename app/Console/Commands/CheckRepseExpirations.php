<?php

namespace App\Console\Commands;

use App\Models\Proveedor;
use App\Models\RepseContract;
use App\Models\User;
use App\Notifications\RepseExpirationNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckRepseExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repse:check-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisa contratos REPSE propios y de contratistas próximos a vencer, plazos de reporteo ICSOE bimestral, y envía notificaciones.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando escaneo de vencimientos REPSE...');

        $admins = User::role(['admin', 'super-admin'])->get();

        if ($admins->isEmpty()) {
            $this->warn('No se encontraron administradores para notificar.');
            return;
        }

        $this->checkOwnContractExpirations($admins);
        $this->checkContractorExpirations($admins);
        $this->checkIcsoeReportingDeadlines($admins);

        $this->info('Escaneo completado.');
    }

    /**
     * Fix #3 (partial) + original: Check own RepseContract expirations.
     */
    protected function checkOwnContractExpirations($admins)
    {
        $thresholds = [30, 15, 7, 1];

        foreach ($thresholds as $days) {
            $targetDate = Carbon::now()->addDays($days)->toDateString();

            $expiringContracts = RepseContract::with('cliente')
                ->whereDate('end_date', $targetDate)
                ->get();

            foreach ($expiringContracts as $contract) {
                $this->info("Notificando vencimiento de contrato propio {$contract->contract_number} en {$days} días.");

                Notification::send($admins, new RepseExpirationNotification($contract, $days));
            }
        }
    }

    /**
     * Fix #3: Check contractor (proveedores) REPSE expirations.
     * Scans proveedores with is_repse=true where repse_expiry is within 60, 30, 15, 7 days.
     */
    protected function checkContractorExpirations($admins)
    {
        $thresholds = [60, 30, 15, 7];

        foreach ($thresholds as $days) {
            $targetDate = Carbon::now()->addDays($days)->toDateString();

            $expiringContractors = Proveedor::where('is_repse', true)
                ->whereNotNull('repse_expiry')
                ->whereDate('repse_expiry', $targetDate)
                ->get();

            foreach ($expiringContractors as $contractor) {
                $this->info("Notificando vencimiento REPSE de contratista {$contractor->nombre_razon_social} en {$days} días.");

                Notification::send($admins, new RepseExpirationNotification($contractor, $days, 'contractor'));
            }
        }
    }

    /**
     * Fix #2: Check ICSOE bimonthly reporting deadlines.
     * Bimestres: Ene-Feb → reportar en Mar, Mar-Abr → May, May-Jun → Jul,
     *            Jul-Ago → Sep, Sep-Oct → Nov, Nov-Dic → Ene
     * Check if current month is a reporting month and within first 5 days.
     */
    protected function checkIcsoeReportingDeadlines($admins)
    {
        $today = Carbon::now();
        $currentMonth = (int) $today->format('m');
        $currentDay = (int) $today->format('d');

        $reportingMonths = [1, 3, 5, 7, 9, 11];

        if (!in_array($currentMonth, $reportingMonths) || $currentDay > 5) {
            return;
        }

        $bimestreLabel = match ($currentMonth) {
            3 => 'Ene-Feb',
            5 => 'Mar-Abr',
            7 => 'May-Jun',
            9 => 'Jul-Ago',
            11 => 'Sep-Oct',
            1 => 'Nov-Dic',
            default => ''
        };

        $activeContracts = RepseContract::with(['cliente', 'empleados'])
            ->whereHas('empleados')
            ->get();

        if ($activeContracts->isEmpty()) {
            $this->info('No hay contratos activos con empleados para reportar ICSOE.');
            return;
        }

        $this->info("Verificando plazo de reporteo ICSOE para bimestre {$bimestreLabel}...");

        foreach ($activeContracts as $contract) {
            $this->info("Recordatorio ICSOE para contrato {$contract->contract_number} (bimestre {$bimestreLabel}).");

            Notification::send($admins, new RepseExpirationNotification($contract, 0, 'icsoe_reporting', $bimestreLabel));
        }
    }
}
