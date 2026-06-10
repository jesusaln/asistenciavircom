<?php

namespace App\Services;

use App\Models\User;
use App\Models\Nom035EvaluationPeriod;
use App\Models\Nom035Respondent;
use Illuminate\Support\Facades\DB;

class Nom035CoverageService
{
    public function forPeriod(?Nom035EvaluationPeriod $period): array
    {
        $empresa_id = \App\Support\EmpresaResolver::resolveId();
        
        // Total de empleados activos que DEBERÍAN participar
        $activeEmployees = User::where('empresa_id', $empresa_id)
            ->where('es_empleado', true)
            ->where('activo', true)
            ->get();
        
        $totalEmployees = $activeEmployees->count();
        $employeeIds = $activeEmployees->pluck('id')->all();
        $employeeEmails = $activeEmployees->pluck('email')
            ->filter()
            ->map(fn($e) => strtolower(trim($e)))
            ->unique()
            ->all();

        $completedRespondents = 0;
        if ($period && $totalEmployees > 0) {
            // Contamos encuestados que:
            // 1. Estén vinculados por empleado_id a un empleado activo
            // 2. O tengan un email que coincida con un empleado activo
            $completedRespondents = Nom035Respondent::where('evaluation_period_id', $period->id)
                ->where(function ($query) {
                    $query->where('status', 'completed')
                        ->orWhereNotNull('risk_level');
                })
                ->where(function ($query) use ($employeeIds, $employeeEmails) {
                    $query->whereIn('empleado_id', $employeeIds);
                    
                    if (!empty($employeeEmails)) {
                        $query->orWhereIn(DB::raw('LOWER(TRIM(email))'), $employeeEmails);
                    }
                })
                ->get(['empleado_id', 'email'])
                ->unique(function ($item) {
                    return ($item->empleado_id ?: 'no_id') . '|' . strtolower(trim($item->email ?: 'no_email'));
                })
                ->count();
        }

        $completedRespondents = min($completedRespondents, $totalEmployees);
        $coverageRate = $totalEmployees > 0
            ? round(($completedRespondents / $totalEmployees) * 100, 1)
            : 0.0;

        return [
            'total_employees' => $totalEmployees,
            'evaluable_employees' => $totalEmployees, // Todos son evaluables
            'employees_without_email' => $activeEmployees->whereNull('email')->count(),
            'completed_respondents' => $completedRespondents,
            'coverage_rate' => (float)$coverageRate,
        ];
    }
}
