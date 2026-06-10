<?php

namespace App\Jobs\Concerns;

use App\Models\Empresa;
use App\Models\Cita;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait HandlesCancelFlow
{
    protected function handleCancelarLista(string $msg, Empresa $empresa, string $stateKey): void
    {
        $cliente = $this->buscarClientePorWaId();
        if (!$cliente) {
            $this->sendReply("⚠️ No encontramos un cliente registrado con tu número. Para cancelar, contacta a un asesor.");
            Cache::put($stateKey, 'menu', now()->addDay());
            return;
        }

        $citas = Cita::where('empresa_id', $this->empresaId)
            ->where('cliente_id', $cliente->id)
            ->whereIn('estado', ['programado', 'pendiente', 'confirmado'])
            ->where('fecha_hora', '>=', now())
            ->orderBy('fecha_hora')
            ->get();

        if ($citas->isEmpty()) {
            $this->sendReply("✅ No tienes citas próximas para cancelar.");
            Cache::put($stateKey, 'menu', now()->addDay());
            return;
        }

        $numEmojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
        $reply = "🗑️ *Selecciona la cita a cancelar:*\n\n";
        $citasMap = [];
        foreach ($citas as $idx => $cita) {
            $emoji = $numEmojis[$idx] ?? ($idx + 1) . '.';
            $reply .= "{$emoji} {$cita->tipo_servicio} - {$cita->fecha_hora->format('d/m/Y H:i')} (Folio: {$cita->folio})\n";
            $citasMap[$idx + 1] = $cita->id;
        }
        $reply .= "\nResponde el *número* de la cita que deseas cancelar.";

        Cache::put("{$stateKey}_citas_map", $citasMap, now()->addDay());
        Cache::put($stateKey, 'cancelar_confirmar', now()->addDay());
        $this->sendReply($reply);
    }

    protected function handleCancelarConfirmar(string $msg, Empresa $empresa, string $stateKey): void
    {
        if (in_array(trim(strtolower($msg)), ['menu', 'atras', 'atrás', '0'])) {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        $citasMap = Cache::get("{$stateKey}_citas_map", []);
        $idx = (int) $msg;

        if (!$idx || !isset($citasMap[$idx])) {
            $this->sendReply("⚠️ Opción no válida. Por favor selecciona un *número* de la lista.");
            Cache::put($stateKey, 'cancelar_confirmar', now()->addDay());
            return;
        }

        try {
            $cita = Cita::find($citasMap[$idx]);
            if (!$cita) {
                $this->sendReply("⚠️ No se encontró la cita seleccionada.");
                Cache::put($stateKey, 'menu', now()->addDay());
                return;
            }

            $cita->update([
                'estado' => Cita::ESTADO_CANCELADO,
                'motivo_cancelacion' => 'Cancelado por el cliente vía WhatsApp',
            ]);

            $this->sendReply("✅ *Cita Cancelada*\n\nFolio: {$cita->folio}\nServicio: {$cita->tipo_servicio}\nFecha: {$cita->fecha_hora->format('d/m/Y H:i')}\n\nSi necesitas agendar nuevamente, escribe *menu*.");
            Cache::put($stateKey, 'menu', now()->addDay());
        } catch (\Exception $e) {
            Log::error("Error al cancelar cita desde WhatsApp: " . $e->getMessage());
            $this->sendReply("⚠️ Ocurrió un error al cancelar la cita. Por favor intenta de nuevo.");
            Cache::put($stateKey, 'menu', now()->addDay());
        }
    }
}
