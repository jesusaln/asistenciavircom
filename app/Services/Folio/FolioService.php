<?php

namespace App\Services\Folio;

use App\Models\FolioConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FolioService
{
    /**
     * Map document types to their Model classes
     */
    /**
     * Custom prefixes and padding for specific document types.
     * Overrides the default (first letter + 4-digit padding).
     */
    protected array $customFormat = [
        'ecoclimas' => ['prefix' => 'EC', 'padding' => 3],
        'liverpool' => ['prefix' => 'LIV', 'padding' => 3],
        'sears' => ['prefix' => 'SEA', 'padding' => 3],
        'home_depot' => ['prefix' => 'HOM', 'padding' => 3],
        'coppel' => ['prefix' => 'COP', 'padding' => 3],
        'electra' => ['prefix' => 'ELE', 'padding' => 3],
        'city_club' => ['prefix' => 'CIT', 'padding' => 3],
        'sams_club' => ['prefix' => 'SAM', 'padding' => 3],
        'kit' => ['prefix' => 'KIT', 'padding' => 3],
    ];

    protected array $modelMap = [
        'cotizacion' => \App\Models\Cotizacion::class,
        'venta' => \App\Models\Venta::class,
        'orden_compra' => \App\Models\OrdenCompra::class,
        'pedido' => \App\Models\Pedido::class,
        'compra' => \App\Models\Compra::class,
        'cliente' => \App\Models\Cliente::class,
        'cita' => \App\Models\Cita::class,
        'herramienta' => \App\Models\Herramienta::class,
        'proveedor' => \App\Models\Proveedor::class,
        'producto' => \App\Models\Producto::class,
        'servicio' => \App\Models\Servicio::class,
        'mantenimiento' => \App\Models\Mantenimiento::class,
        'nomina' => \App\Models\Nomina::class,
        'prestamo' => \App\Models\Prestamo::class,
        'renta' => \App\Models\Renta::class,
        'ticket' => \App\Models\Ticket::class,
        'traspaso' => \App\Models\Traspaso::class,
        'vacacion' => \App\Models\Vacacion::class,
        'taller' => \App\Models\TallerOrden::class,
        'kit' => \App\Models\Producto::class,
        'ecoclimas' => \App\Models\Cita::class,
        'liverpool' => \App\Models\Cita::class,
        'sears' => \App\Models\Cita::class,
        'home_depot' => \App\Models\Cita::class,
        'coppel' => \App\Models\Cita::class,
        'electra' => \App\Models\Cita::class,
        'city_club' => \App\Models\Cita::class,
        'sams_club' => \App\Models\Cita::class,
    ];

    /**
     * Get the field name used for the folio/code in a specific model.
     *
     * @param string $type
     * @return string
     */
    public function getFieldNameByType(string $type): string
    {
        return match ($type) {
            'cotizacion' => 'numero_cotizacion',
            'venta' => 'numero_venta',
            'pedido' => 'numero_pedido',
            'orden_compra' => 'numero_orden',
            'compra' => 'numero_compra',
            'cliente', 'proveedor', 'producto', 'servicio', 'kit' => 'codigo',
            'cita', 'mantenimiento', 'nomina', 'prestamo', 'traspaso', 'vacacion', 'ticket', 'taller' => 'folio',
            'herramienta' => 'codigo_inventario',
            'renta' => 'numero_contrato',
            default => 'codigo' // Default fallback
        };
    }

    /**
     * Get the next folio string for a given document type.
     * Transactional and atomic.
     */
    public function getNextFolio(string $type): string
    {
        return DB::transaction(function () use ($type) {
            // Lock the config row for update or create it if missing (Atomic)
            $fmt = $this->customFormat[$type] ?? [];
            $config = FolioConfig::firstOrCreate(
                [
                    'document_type' => $type,
                    'empresa_id' => \App\Support\EmpresaResolver::resolveId()
                ],
                [
                    'prefix' => $fmt['prefix'] ?? strtoupper(substr($type, 0, 1)),
                    'current_number' => 0,
                    'padding' => $fmt['padding'] ?? 4,
                ]
            );

            // Re-lock for update specifically to be safe
            $config = FolioConfig::where('id', $config->id)->lockForUpdate()->first();

            // Increment
            $nextNumber = $config->current_number + 1;

            // Format
            $prefix = $config->prefix ?? '';
            $folio = $prefix . str_pad($nextNumber, $config->padding, '0', STR_PAD_LEFT);

            // Double check existence (paranoid check)
            if ($this->folioExists($type, $folio)) {
                // Determine the actual max in DB to recover from desync
                $recoveredNumber = $this->analyzeAndRepair($type);
                // If recovered is higher than our current attempt, use it
                if ($recoveredNumber >= $nextNumber) {
                    $nextNumber = $recoveredNumber + 1;

                    // Re-fetch config to safe update
                    $config->refresh();
                    // We keep the calculated nextNumber from recovered max
                    $folio = $prefix . str_pad($nextNumber, $config->padding, '0', STR_PAD_LEFT);
                }
            }

            // Update config
            $config->update(['current_number' => $nextNumber]);

            return $folio;
        });
    }

    /**
     * Preview the next folio string for a given document type WITHOUT incrementing.
     * Used for UI display only.
     */
    public function previewNextFolio(string $type): string
    {
        try {
            $config = FolioConfig::where('document_type', $type)->first();

            if (!$config) {
                // Determine prefix from type
                $prefix = strtoupper(substr($type, 0, 1));
                $padding = 3;
                
                // Try to find the max in DB as a fallback
                $maxNum = $this->findMaxInDb($type, $prefix);
                $nextNumber = $maxNum + 1;
            } else {
                $nextNumber = $config->current_number + 1;
                $prefix = $config->prefix ?? '';
                $padding = $config->padding;
                
                // Periodically verify if the max in DB is higher than our config (Self-healing preview)
                if (rand(1, 10) === 1) { // 10% chance to check
                   $realMax = $this->findMaxInDb($type, $prefix);
                   if ($realMax >= $nextNumber) {
                       $nextNumber = $realMax + 1;
                   }
                }
            }

            return $prefix . str_pad($nextNumber, $padding, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            Log::warning("Error in previewNextFolio for {$type}: " . $e->getMessage());
            return strtoupper(substr($type, 0, 1)) . '001'; // Safe default
        }
    }

    /**
     * Helper to find max number in DB for a specific document type
     */
    private function findMaxInDb(string $type, string $prefix): int
    {
        if (!isset($this->modelMap[$type])) {
            return 0;
        }

        $modelClass = $this->modelMap[$type];
        $fieldName = $this->getFieldNameByType($type);
        $castType = config('database.default') === 'pgsql' ? 'INTEGER' : 'UNSIGNED';
        $prefixLength = strlen($prefix);

        $query = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))
            ? $modelClass::withTrashed()
            : $modelClass::query();

        try {
            if (empty($prefix)) {
                 $maxRecord = $query->selectRaw("MAX(CAST({$fieldName} AS {$castType})) as max_num")->first();
            } else {
                 // Sintaxis compatible con Postgres y MySQL: SUBSTRING(campo, posicion)
                 $maxRecord = $query->where($fieldName, 'LIKE', $prefix . '%')
                    ->selectRaw("MAX(CAST(SUBSTRING({$fieldName}, " . ($prefixLength + 1) . ") AS {$castType})) as max_num")
                    ->first();
            }
            return (int) ($maxRecord->max_num ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check if a specific folio already exists in the real table.
     */
    protected function folioExists(string $type, string $folio): bool
    {
        if (!isset($this->modelMap[$type])) {
            return false;
        }

        $modelClass = $this->modelMap[$type];

        // Assumption: The field name is usually 'numero_cotizacion', 'numero_pedido', etc.
        // We might need a map for field names too if they are inconsistent.
        $fieldName = $this->getFieldNameByType($type);

        $query = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))
            ? $modelClass::withTrashed()
            : $modelClass::query();

        return $query->where($fieldName, $folio)->exists();
    }

    /**
     * Analyze the real table and update the current_number to the max found.
     * Returns the max number found.
     */
    public function analyzeAndRepair(string $type): int
    {
        $config = FolioConfig::where('document_type', $type)
            ->where('empresa_id', \App\Support\EmpresaResolver::resolveId())
            ->first();
        if (!$config || !isset($this->modelMap[$type])) {
            return 0;
        }

        $modelClass = $this->modelMap[$type];
        $fieldName = $this->getFieldNameByType($type);
        $prefix = $config->prefix;

        // Query for the max number with that prefix
        // If prefix is 'C', we look for 'C%' and extract the number.
        // This query depends on DB engine, assuming MySQL/Postgres compatible regex or substring.

        if (empty($prefix)) {
            // If no prefix, just max cast or default logic
            // Ideally we always have prefixes.
            return 0;
        }

        // Fetch all matching folios to find max
        // PostgreSQL requires CAST(... AS INTEGER) instead of UNSIGNED
        $castType = config('database.default') === 'pgsql' ? 'INTEGER' : 'UNSIGNED';

        $prefixLength = strlen($prefix);

        $query = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))
            ? $modelClass::withTrashed()
            : $modelClass::query();

        // This finds the max number part appearing after the prefix
        // Filtered by empresa automáticamente via global scope in $modelClass
        $maxRecord = $query->where($fieldName, 'LIKE', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING({$fieldName}, " . ($prefixLength + 1) . ") AS {$castType})) as max_num")
            ->first();


        $maxNum = $maxRecord ? (int) $maxRecord->max_num : 0;

        // Update config
        if ($maxNum > $config->current_number) {
            $config->update(['current_number' => $maxNum]);
        }

        return $maxNum;
    }
}

