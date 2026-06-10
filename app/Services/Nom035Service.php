<?php

namespace App\Services;

use App\Models\Nom035Answer;
use App\Models\Nom035Respondent;
use Illuminate\Support\Collection;

class Nom035Service
{
    /**
     * Calcula resultados para Guía I (Acontecimientos Traumáticos)
     */
    public function calculateGuideI(iterable $answers): array
    {
        $sectionI = false;
        $countII = 0;
        $countIII = 0;
        $countIV = 0;

        foreach ($answers as $ans) {
            $q = $ans->question;
            if (!$q) {
                continue;
            }
            $val = (int) $ans->value; // 1 = Sí

            if ($val === 1) {
                if ($q->section === 'Sección I')
                    $sectionI = true;
                if ($q->section === 'Sección II')
                    $countII++;
                if ($q->section === 'Sección III')
                    $countIII++;
                if ($q->section === 'Sección IV')
                    $countIV++;
            }
        }

        $requiresAttention = false;
        if ($sectionI) {
            if ($countII >= 1 || $countIII >= 3 || $countIV >= 2) {
                $requiresAttention = true;
            }
        }

        return [
            'total_level' => $requiresAttention ? 'Se sugiere seguimiento según protocolo' : 'Sin hallazgos críticos detectados',
            'requires_attention' => $requiresAttention,
            'counts' => [
                'section_i' => $sectionI,
                'section_ii' => $countII,
                'section_iii' => $countIII,
                'section_iv' => $countIV,
            ]
        ];
    }

    /**
     * Calcula los resultados para la Guía II (Hasta 50 trabajadores)
     */
    public function calculateGuideII(iterable $answers): array
    {
        $totalScore = 0;
        $categoryScores = [
            'Ambiente de trabajo' => 0,
            'Factores propios de la actividad' => 0,
            'Organización del tiempo de trabajo' => 0,
            'Liderazgo y relaciones en el trabajo' => 0,
        ];

        $domainScores = [
            'Condiciones en el ambiente de trabajo' => 0,
            'Carga de trabajo' => 0,
            'Falta de control sobre el trabajo' => 0,
            'Jornada de trabajo' => 0,
            'Interferencia en la relación trabajo-familia' => 0,
            'Liderazgo' => 0,
            'Relaciones en el trabajo' => 0,
            'Violencia' => 0,
        ];

        foreach ($answers as $answer) {
            $value = $answer->value;
            $q = $answer->question;
            if (!$q) {
                continue;
            }

            $finalValue = $q->is_inverse ? (4 - $value) : $value;
            $totalScore += $finalValue;

            if ($q->category)
                $categoryScores[$q->category] += $finalValue;
            if ($q->domain)
                $domainScores[$q->domain] += $finalValue;
        }

        return [
            'total' => $totalScore,
            'total_level' => $this->getLevel('total', 'II', $totalScore),
            'categories' => collect($categoryScores)->map(fn($v, $k) => ['score' => $v, 'level' => $this->getLevel('category', 'II', $v, $k)]),
            'domains' => collect($domainScores)->map(fn($v, $k) => ['score' => $v, 'level' => $this->getLevel('domain', 'II', $v, $k)]),
        ];
    }

    /**
     * Calcula los resultados para la Guía III (Más de 50 trabajadores)
     */
    public function calculateGuideIII(iterable $answers): array
    {
        $totalScore = 0;
        $categoryScores = [
            'Ambiente de trabajo' => 0,
            'Factores propios de la actividad' => 0,
            'Organización del tiempo de trabajo' => 0,
            'Liderazgo y relaciones en el trabajo' => 0,
            'Entorno organizacional' => 0,
        ];

        $domainScores = [
            'Condiciones en el ambiente de trabajo' => 0,
            'Carga de trabajo' => 0,
            'Falta de control sobre el trabajo' => 0,
            'Jornada de trabajo' => 0,
            'Interferencia en la relación trabajo-familia' => 0,
            'Liderazgo' => 0,
            'Relaciones en el trabajo' => 0,
            'Violencia' => 0,
            'Reconocimiento del desempeño' => 0,
            'Insuficiente sentido de pertenencia e inestabilidad' => 0,
        ];

        foreach ($answers as $answer) {
            $value = $answer->value;
            $q = $answer->question;
            if (!$q) {
                continue;
            }

            $finalValue = $q->is_inverse ? (4 - $value) : $value;
            $totalScore += $finalValue;

            if ($q->category)
                $categoryScores[$q->category] += $finalValue;
            if ($q->domain)
                $domainScores[$q->domain] += $finalValue;
        }

        return [
            'total' => $totalScore,
            'total_level' => $this->getLevel('total', 'III', $totalScore),
            'categories' => collect($categoryScores)->map(fn($v, $k) => ['score' => $v, 'level' => $this->getLevel('category', 'III', $v, $k)]),
            'domains' => collect($domainScores)->map(fn($v, $k) => ['score' => $v, 'level' => $this->getLevel('domain', 'III', $v, $k)]),
        ];
    }

    private function getLevel(string $type, string $guide, int $score, ?string $name = null): string
    {
        if ($guide === 'II') {
            if ($type === 'total') {
                if ($score < 20) return 'Nulo';
                if ($score < 45) return 'Bajo';
                if ($score < 70) return 'Medio';
                if ($score < 90) return 'Alto';
                return 'Muy Alto';
            }
            if ($type === 'category') {
                switch ($name) {
                    case 'Ambiente de trabajo': return $this->calcLevel($score, 3, 5, 7, 9);
                    case 'Factores propios de la actividad': return $this->calcLevel($score, 10, 20, 30, 40);
                    case 'Organización del tiempo de trabajo': return $this->calcLevel($score, 4, 6, 9, 12);
                    case 'Liderazgo y relaciones en el trabajo': return $this->calcLevel($score, 10, 18, 28, 38);
                }
            }
            if ($type === 'domain') {
                switch ($name) {
                    case 'Condiciones en el ambiente de trabajo': return $this->calcLevel($score, 3, 5, 7, 9);
                    case 'Carga de trabajo': return $this->calcLevel($score, 12, 16, 20, 24);
                    case 'Falta de control sobre el trabajo': return $this->calcLevel($score, 5, 8, 11, 14);
                    case 'Jornada de trabajo': return $this->calcLevel($score, 1, 2, 4, 6);
                    case 'Interferencia en la relación trabajo-familia': return $this->calcLevel($score, 1, 2, 4, 6);
                    case 'Liderazgo': return $this->calcLevel($score, 3, 5, 8, 11);
                    case 'Relaciones en el trabajo': return $this->calcLevel($score, 5, 8, 11, 14);
                    case 'Violencia': return $this->calcLevel($score, 7, 10, 13, 16);
                }
            }
        }

        if ($guide === 'III') {
            if ($type === 'total') {
                if ($score < 50) return 'Nulo';
                if ($score < 75) return 'Bajo';
                if ($score < 99) return 'Medio';
                if ($score < 140) return 'Alto';
                return 'Muy Alto';
            }
            if ($type === 'category') {
                switch ($name) {
                    case 'Ambiente de trabajo': return $this->calcLevel($score, 5, 9, 11, 14);
                    case 'Factores propios de la actividad': return $this->calcLevel($score, 15, 30, 45, 60);
                    case 'Organización del tiempo de trabajo': return $this->calcLevel($score, 5, 7, 10, 13);
                    case 'Liderazgo y relaciones en el trabajo': return $this->calcLevel($score, 14, 29, 42, 58);
                    case 'Entorno organizacional': return $this->calcLevel($score, 10, 14, 18, 23);
                }
            }
            if ($type === 'domain') {
                switch ($name) {
                    case 'Condiciones en el ambiente de trabajo': return $this->calcLevel($score, 5, 9, 11, 14);
                    case 'Carga de trabajo': return $this->calcLevel($score, 15, 21, 27, 37);
                    case 'Falta de control sobre el trabajo': return $this->calcLevel($score, 11, 16, 21, 25);
                    case 'Jornada de trabajo': return $this->calcLevel($score, 1, 2, 4, 6);
                    case 'Interferencia en la relación trabajo-familia': return $this->calcLevel($score, 4, 6, 8, 10);
                    case 'Liderazgo': return $this->calcLevel($score, 9, 12, 16, 20);
                    case 'Relaciones en el trabajo': return $this->calcLevel($score, 10, 13, 17, 21);
                    case 'Violencia': return $this->calcLevel($score, 7, 10, 13, 16);
                    case 'Reconocimiento del desempeño': return $this->calcLevel($score, 6, 10, 14, 18);
                    case 'Insuficiente sentido de pertenencia e, inestabilidad': return $this->calcLevel($score, 4, 6, 8, 10);
                }
            }
        }

        return 'Nulo';
    }

    private function calcLevel(int $score, int $nulo, int $bajo, int $medio, int $alto): string
    {
        if ($score < $nulo) return 'Nulo';
        if ($score < $bajo) return 'Bajo';
        if ($score < $medio) return 'Medio';
        if ($score < $alto) return 'Alto';
        return 'Muy Alto';
    }

    public function getDetailedAverages(Collection $respondents): array
    {
        $categoryTotals = [];
        $domainTotals = [];
        $counts = $respondents->filter(fn($r) => in_array($r->applied_guide, ['II', 'III', 'Guía II', 'Guía III']))->count();
        
        if ($counts === 0) return ['categories' => [], 'domains' => []];

        $guide = 'II';
        foreach ($respondents as $r) {
            if (in_array($r->applied_guide, ['III', 'Guía III'])) $guide = 'III';
            
            $results = $r->results;
            if (isset($results['categories'])) {
                foreach ($results['categories'] as $cat => $data) {
                    if (!isset($categoryTotals[$cat])) $categoryTotals[$cat] = 0;
                    $categoryTotals[$cat] += $data['score'] ?? 0;
                }
            }
            if (isset($results['domains'])) {
                foreach ($results['domains'] as $dom => $data) {
                    if (!isset($domainTotals[$dom])) $domainTotals[$dom] = 0;
                    $domainTotals[$dom] += $data['score'] ?? 0;
                }
            }
        }

        return [
            'categories' => collect($categoryTotals)->map(function($sum, $name) use ($counts, $guide) {
                $avg = $sum / $counts;
                return [
                    'avg_score' => round($avg, 1),
                    'level' => $this->getLevel('category', $guide, round($avg), $name)
                ];
            })->toArray(),
            'domains' => collect($domainTotals)->map(function($sum, $name) use ($counts, $guide) {
                $avg = $sum / $counts;
                return [
                    'avg_score' => round($avg, 1),
                    'level' => $this->getLevel('domain', $guide, round($avg), $name)
                ];
            })->toArray(),
        ];
    }

    public function getAggregatedStats(Collection $respondents): array
    {
        $totalRespondents = $respondents->count();
        $riskLevels = ['Nulo' => 0, 'Bajo' => 0, 'Medio' => 0, 'Alto' => 0, 'Muy Alto' => 0];
        $traumaCount = 0;
        
        foreach ($respondents as $r) {
            $level = $r->risk_level ?: 'Nulo';
            
            // Map Guía I terminology to Risk Levels for aggregation
            if ($level === 'Sin hallazgos críticos detectados') $level = 'Nulo';
            if ($level === 'Se sugiere seguimiento según protocolo') {
                $level = 'Alto';
                $traumaCount++;
            }

            if (!isset($riskLevels[$level])) $riskLevels[$level] = 0;
            $riskLevels[$level]++;
        }

        return [
            'total_respondents' => $totalRespondents,
            'risk_levels' => $riskLevels,
            'by_department' => $respondents->groupBy('department')->map(function ($items) {
                $stats = ['total' => 0];
                foreach ($items as $item) {
                    $level = $item->risk_level ?: 'Nulo';
                    if ($level === 'Sin hallazgos críticos detectados') $level = 'Nulo';
                    if ($level === 'Se sugiere seguimiento según protocolo') $level = 'Alto';
                    
                    if (!isset($stats[$level])) $stats[$level] = 0;
                    $stats[$level]++;
                    $stats['total']++;
                }
                return $stats;
            })->toArray(),
            'trauma_cases' => $traumaCount,
            'detailed_averages' => $this->getDetailedAverages($respondents),
            'has_risk_factors' => $respondents->whereIn('applied_guide', ['II', 'III', 'Guía II', 'Guía III'])->count() > 0
        ];
    }

    public function getRecommendations(array $stats): array
    {
        $recommendations = [];
        $riskLevels = $stats['risk_levels'] ?? [];
        $totalRisk = collect($riskLevels)->sortDesc()->keys()->first();

        if (in_array($totalRisk, ['Alto', 'Muy Alto'])) {
            $recommendations[] = [
                'scope' => 'Global',
                'priority' => 'Crítica',
                'action' => 'Realizar un análisis de causa raíz sobre los factores de riesgo detectados.',
                'details' => 'Se requiere la intervención inmediata de especialistas.'
            ];
        }

        if (($stats['trauma_cases'] ?? 0) > 0) {
            $recommendations[] = [
                'scope' => 'Salud Individual',
                'priority' => 'Urgente',
                'action' => 'Canalizar a los colaboradores con eventos traumáticos a valoración profesional.',
                'details' => "Se han detectado {$stats['trauma_cases']} casos que requieren atención."
            ];
        }

        return $recommendations;
    }
}
