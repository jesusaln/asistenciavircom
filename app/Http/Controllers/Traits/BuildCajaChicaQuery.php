<?php

namespace App\Http\Controllers\Traits;

use App\Models\CajaChica;

trait BuildCajaChicaQuery
{
    protected function cajaChicaBaseQuery(array $filters, string $sortBy, string $sortDir)
    {
        return CajaChica::with(['user', 'adjuntos'])
            ->when($filters['tipo'] ?? null, function ($query, $tipo) {
                $query->where('tipo', $tipo);
            })
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('concepto', 'like', '%' . $search . '%')
                        ->orWhere('nota', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['categoria'] ?? null, function ($query, $categoria) {
                $query->where('categoria', $categoria);
            })
            ->when($filters['desde'] ?? null, function ($query, $desde) {
                $query->whereDate('fecha', '>=', $desde);
            })
            ->when($filters['hasta'] ?? null, function ($query, $hasta) {
                $query->whereDate('fecha', '<=', $hasta);
            })
            ->when($sortBy === 'usuario', function ($query) {
                $query->leftJoin('users', 'users.id', '=', 'caja_chica.user_id')
                    ->select('caja_chica.*', 'users.name as usuario_nombre');
            });
    }
}
