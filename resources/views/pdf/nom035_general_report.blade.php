<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Resultados NOM-035 - {{ $empresa->nombre_empresa }}</title>
    <style>
        @page { margin: 1cm 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #1e293b; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16px; color: #1e3a8a; text-transform: uppercase; }
        
        .section { margin-bottom: 20px; }
        .section-title { font-weight: bold; font-size: 11px; background-color: #f1f5f9; margin-bottom: 8px; padding: 6px; border-left: 4px solid #1e3a8a; color: #0f172a; text-transform: uppercase; }
        
        .grid-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .grid-table td { padding: 4px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .label { font-weight: bold; width: 30%; color: #475569; }
        
        .table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .table th, .table td { border: 1px solid #e2e8f0; padding: 5px; text-align: center; }
        .table th { background-color: #f8fafc; font-weight: bold; color: #1e293b; font-size: 9px; text-transform: uppercase; }
        .text-left { text-align: left; }
        
        .risk-badge { padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 8px; text-transform: uppercase; display: inline-block; }
        .risk-Nulo { background-color: #10b981; color: white; }
        .risk-Bajo { background-color: #3b82f6; color: white; }
        .risk-Medio { background-color: #f59e0b; color: white; }
        .risk-Alto { background-color: #f97316; color: white; }
        .risk-Muy-Alto { background-color: #ef4444; color: white; }
        
        .alert-box { background-color: #fef2f2; border: 1px solid #fee2e2; padding: 10px; border-radius: 8px; color: #991b1b; margin-top: 10px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
        .signature-section { margin-top: 40px; }
        .signature-line { width: 200px; border-top: 1px solid #333; margin: 0 auto; padding-top: 5px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Informe de Resultados de la Identificación y Análisis de Factores de Riesgo Psicosocial</h1>
        <p><strong>NOM-035-STPS-2018</strong></p>
    </div>

    <div class="section">
        <div class="section-title">1. Datos del Centro de Trabajo (Numeral 7.7-a)</div>
        <table class="grid-table">
            <tr>
                <td class="label">Nombre o Razón Social:</td>
                <td>{{ $empresa->razon_social }} ({{ $empresa->nombre_empresa }})</td>
            </tr>
            <tr>
                <td class="label">RFC:</td>
                <td>{{ $empresa->rfc }}</td>
            </tr>
            <tr>
                <td class="label">Domicilio:</td>
                <td>
                    {{ $empresa->calle }} {{ $empresa->numero_exterior }}, 
                    Col. {{ $empresa->colonia }}, {{ $empresa->ciudad }}, {{ $empresa->estado }}.
                </td>
            </tr>
            <tr>
                <td class="label">Actividad Principal:</td>
                <td>Servicios de Aire Acondicionado, Refrigeración y Climatización Industrial y Comercial.</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Objetivo y Metodología (Numeral 7.7-b, c, d)</div>
        <p style="text-align: justify;">
            <strong>Objetivo:</strong> Identificar, analizar y prevenir los factores de riesgo psicosocial, así como promover un entorno organizacional favorable en el centro de trabajo.<br>
            <strong>Principales Actividades:</strong> Instalación, mantenimiento y reparación de sistemas de climatización; gestión administrativa y operativa en campo.<br>
            <strong>Método:</strong> Se aplicaron las Guías de Referencia oficiales de la STPS. @if(!$stats['has_risk_factors']) Actualmente este informe se centra en la <strong>Guía de Referencia I</strong> (Acontecimientos Traumáticos Severos), cumpliendo con la etapa de identificación de personal expuesto a eventos críticos. @else Se aplicaron las Guías de Referencia I y {{ $respondents->where('applied_guide', '!=', 'I')->first()->applied_guide ?? 'II/III' }} para un diagnóstico integral. @endif
        </p>
    </div>

    <div class="section">
        <div class="section-title">3. Resumen de Participación y Riesgo General</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Evaluaciones Realizadas</th>
                    <th>Resultados Sin Hallazgos</th>
                    <th>Resultados Con Riesgo/Trauma</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $stats['total_respondents'] }}</td>
                    <td>{{ $stats['risk_levels']['Nulo'] }}</td>
                    <td>{{ $stats['total_respondents'] - $stats['risk_levels']['Nulo'] }}</td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 10px;">
            <strong>Distribución por Niveles de Riesgo Sugeridos:</strong>
            <table class="table" style="width: 50%; margin: 5px auto;">
                @foreach(['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'] as $level)
                @if(($stats['risk_levels'][$level] ?? 0) > 0)
                <tr>
                    <td class="text-left">{{ $level }}</td>
                    <td>{{ $stats['risk_levels'][$level] }}</td>
                    <td>
                        <span class="risk-badge risk-{{ $level }}">
                            {{ round(($stats['risk_levels'][$level] / $stats['total_respondents']) * 100) }}%
                        </span>
                    </td>
                </tr>
                @endif
                @endforeach
            </table>
        </div>
    </div>

    @if($stats['trauma_cases'] > 0)
    <div class="section">
        <div class="section-title">4. Identificación de Acontecimientos Traumáticos (Guía I)</div>
        <div class="alert-box">
            Se han identificado <strong>{{ $stats['trauma_cases'] }} colaboradores</strong> que requieren valoración clínica profesional por exposición a acontecimientos traumáticos severos durante o con motivo del trabajo.
        </div>
    </div>
    @endif

    @if($stats['has_risk_factors'])
    <div class="section">
        <div class="section-title">5. Resultados por Categoría y Dominio</div>
        <p>Desglose técnico de factores psicosociales detectados:</p>
        <table class="table">
            <thead>
                <tr>
                    <th class="text-left">Categoría / Dominio</th>
                    <th>Nivel Detectado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats['detailed_averages']['categories'] as $cat => $data)
                <tr>
                    <td class="text-left"><strong>{{ $cat }}</strong> (Categoría)</td>
                    <td><span class="risk-badge risk-{{ str_replace(' ', '-', $data['level']) }}">{{ $data['level'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="section">
        <div class="section-title">5. Nota sobre Factores de Riesgo (Guía II/III)</div>
        <p style="font-style: italic; color: #64748b;">
            Este centro de trabajo ha completado la Fase 1 (Identificación de Eventos Traumáticos). Para un cumplimiento al 100% de la Fase 2 (Diagnóstico de Entorno Organizacional), se recomienda la aplicación de la Guía de Referencia II o III según el número total de trabajadores, lo cual habilitará los desgloses por categorías y dominios psicosociales.
        </p>
    </div>
    @endif

    <div class="section" style="page-break-before: always;">
        <div class="section-title">Anexo: Interpretación de Niveles de Riesgo (Guía II/III)</div>
        <table class="table" style="font-size: 8px;">
            <thead>
                <tr>
                    <th width="15%">Nivel</th>
                    <th width="85%">Acciones Requeridas por la Norma (Numeral 8.4)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="risk-badge risk-Nulo">Nulo</span></td>
                    <td class="text-left">No se requiere adoptar medidas adicionales.</td>
                </tr>
                <tr>
                    <td><span class="risk-badge risk-Bajo">Bajo</span></td>
                    <td class="text-left">Difundir la política, promover el entorno favorable y reforzar la sensibilización de los trabajadores.</td>
                </tr>
                <tr>
                    <td><span class="risk-badge risk-Medio">Medio</span></td>
                    <td class="text-left">Revisar los programas de capacitación, el liderazgo y las cargas de trabajo. Realizar acciones preventivas.</td>
                </tr>
                <tr>
                    <td><span class="risk-badge risk-Alto">Alto</span></td>
                    <td class="text-left">Realizar un análisis detallado, implementar medidas de intervención y asegurar el seguimiento médico.</td>
                </tr>
                <tr>
                    <td><span class="risk-badge risk-Muy-Alto">Muy Alto</span></td>
                    <td class="text-left">Intervención inmediata. Exámenes médicos obligatorios y revisión total de la estructura organizacional.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">6. Conclusiones y Recomendaciones (Numeral 7.7-f, g)</div>
        <p style="text-align: justify;">
            <strong>Conclusiones:</strong> El centro de trabajo ha iniciado satisfactoriamente el proceso de cumplimiento. @if($stats['trauma_cases'] > 0) Se ha detectado personal que requiere atención prioritaria. @else No se detectaron casos urgentes de trauma en la muestra evaluada. @endif<br>
            <strong>Recomendaciones:</strong> @if($stats['trauma_cases'] > 0) Canalizar de inmediato a los colaboradores identificados a los servicios preventivos de salud. @endif Continuar con el registro de evidencias en la Matriz de Acciones y Control para demostrar la proactividad de la empresa ante la autoridad.
        </p>
    </div>

    <div class="signature-section">
        <table width="100%">
            <tr>
                <td align="center" width="50%">
                    <br><br><br>
                    <div class="signature-line">
                        <strong>{{ auth()->user()->name }}</strong><br>
                        Responsable de la Evaluación
                    </div>
                </td>
                <td align="center" width="50%">
                    <div style="border: 1px solid #cbd5e1; padding: 10px; border-radius: 8px; text-align: left; font-size: 7px; color: #475569; background-color: #f8fafc;">
                        <strong>CERTIFICADO DE AUTENTICIDAD NOM-035</strong><br>
                        Este reporte consolida la información técnica de {{ $stats['total_respondents'] }} evaluaciones.<br><br>
                        <strong>Sello Digital del Centro:</strong><br>
                        <span style="font-family: monospace; word-break: break-all;">{{ hash('sha256', $empresa->rfc . $periodo->id . now()) }}</span><br><br>
                        <strong>Cadena Original de Certificación:</strong><br>
                        <span style="font-family: monospace; word-break: break-all;">||NOM035|{{ $empresa->rfc }}|{{ $periodo->name }}|{{ count($activities) }}|{{ $stats['total_respondents'] }}|{{ now() }}||</span><br><br>
                        <span style="font-style: italic;">Documento generado y certificado por el Sistema de Gestión de Riesgo Psicosocial.</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Este documento constituye evidencia legal de cumplimiento parcial o total con la NOM-035-STPS-2018. <br>
        Generado el {{ $fecha }} | Página 1
    </div>
</body>
</html>
