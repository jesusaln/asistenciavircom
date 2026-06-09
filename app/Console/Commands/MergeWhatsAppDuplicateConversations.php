<?php

namespace App\Console\Commands;

use App\Models\WhatsAppChat;
use App\Models\WhatsAppConversation;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeWhatsAppDuplicateConversations extends Command
{
    protected $signature = 'whatsapp:merge-duplicate-conversations
                            {--empresa= : ID de empresa (opcional)}
                            {--dry-run : Solo listar fusiones sin escribir}';

    protected $description = 'Unifica en BD conversaciones y mensajes del mismo número (wa_id distinto por formato CRM vs Meta)';

    public function handle(): int
    {
        $empresaFilter = $this->option('empresa');
        $dry = (bool) $this->option('dry-run');

        $query = WhatsAppConversation::query()->select('empresa_id')->distinct();
        if ($empresaFilter) {
            $query->where('empresa_id', (int) $empresaFilter);
        }
        $empresaIds = $query->pluck('empresa_id')->unique()->values()->all();

        if ($empresaIds === []) {
            $this->info('No hay conversaciones.');

            return self::SUCCESS;
        }

        $merged = 0;

        foreach ($empresaIds as $empresaId) {
            $convs = WhatsAppConversation::query()
                ->where('empresa_id', $empresaId)
                ->orderByDesc('last_message_at')
                ->get();

            $buckets = [];
            foreach ($convs as $c) {
                try {
                    $key = WhatsAppService::canonicalWaId((string) $c->wa_id);
                } catch (\Throwable $e) {
                    $key = (string) $c->wa_id;
                }
                $buckets[$key][] = $c;
            }

            foreach ($buckets as $canonical => $group) {
                if (count($group) <= 1) {
                    $single = $group[0];
                    if (WhatsAppService::canonicalWaId((string) $single->wa_id) !== (string) $single->wa_id) {
                        if ($dry) {
                            $this->line("[dry-run] Empresa {$empresaId}: normalizar wa_id de conversación #{$single->id} → {$canonical}");
                            $merged++;
                        } else {
                            $this->mergeGroup($empresaId, $canonical, $group);
                            $merged++;
                        }
                    }

                    continue;
                }

                if ($dry) {
                    $ids = collect($group)->pluck('id')->implode(', ');
                    $this->line("[dry-run] Empresa {$empresaId}: fusionar ".count($group)." conversaciones ({$ids}) → wa_id {$canonical}");
                    $merged++;
                    continue;
                }

                $this->mergeGroup($empresaId, $canonical, $group);
                $merged++;
            }
        }

        if ($dry) {
            $this->info("Dry-run: {$merged} acción(es) posible(s). Ejecute sin --dry-run para aplicar.");
        } else {
            $this->info("Listo. Grupos procesados: {$merged}.");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, WhatsAppConversation>  $group
     */
    private function mergeGroup(int $empresaId, string $canonical, array $group): void
    {
        usort($group, function ($a, $b) {
            $ta = $a->last_message_at ? $a->last_message_at->getTimestamp() : 0;
            $tb = $b->last_message_at ? $b->last_message_at->getTimestamp() : 0;

            return $tb <=> $ta;
        });

        /** @var WhatsAppConversation $keeper */
        $keeper = $group[0];
        $dupes = array_slice($group, 1);

        $allWaIds = collect($group)->pluck('wa_id')->unique()->values()->all();

        DB::transaction(function () use ($empresaId, $canonical, $keeper, $dupes, $allWaIds) {
            WhatsAppChat::query()
                ->where('empresa_id', $empresaId)
                ->whereIn('wa_id', $allWaIds)
                ->update(['wa_id' => $canonical]);

            foreach ($dupes as $d) {
                $d->delete();
            }

            $tags = $keeper->tags ?? [];
            foreach ($dupes as $d) {
                if (! empty($d->tags) && is_array($d->tags)) {
                    $tags = array_values(array_unique(array_merge($tags, $d->tags)));
                }
            }

            $maxLast = $keeper->last_message_at;
            foreach ($dupes as $d) {
                if ($d->last_message_at && (! $maxLast || $d->last_message_at->gt($maxLast))) {
                    $maxLast = $d->last_message_at;
                }
            }

            $keeper->wa_id = $canonical;
            $keeper->tags = $tags ?: null;
            if ($maxLast) {
                $keeper->last_message_at = $maxLast;
            }
            if (! $keeper->assigned_to) {
                foreach ($dupes as $d) {
                    if ($d->assigned_to) {
                        $keeper->assigned_to = $d->assigned_to;
                        break;
                    }
                }
            }
            $keeper->save();
        });
    }
}
