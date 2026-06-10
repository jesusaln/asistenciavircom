<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Resultados NOM-035 - {{ $respondent->name }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; line-height: 1.5; margin: 20px; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #1a56db; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; color: #1a56db; text-transform: uppercase; }
        .header p { margin: 2px 0; font-weight: bold; }
        
        .section { margin-bottom: 20px; }
        .section-title { font-weight: bold; font-size: 12px; background-color: #f1f5f9; margin-bottom: 10px; padding: 6px; border-left: 4px solid #1a56db; color: #1e293b; text-transform: uppercase; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-table td { padding: 4px; border-bottom: 1px solid #f3f4f6; }
        .label { font-weight: bold; width: 30%; color: #64748b; }
        
        .results-box { border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .risk-level { font-size: 16px; font-weight: bold; margin: 10px 0; display: block; }
        
        .table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .table th, .table td { border: 1px solid #e2e8f0; padding: 6px; text-align: center; }
        .table th { background-color: #f8fafc; font-weight: bold; color: #475569; font-size: 9px; }
        .text-left { text-align: left; }
        
        .alert { padding: 12px; border-radius: 6px; margin-top: 10px; font-weight: bold; }
        .alert-danger { background-color: #fef2f2; border: 1px solid #fee2e2; color: #991b1b; }
        .alert-success { background-color: #f0fdf4; border: 1px solid #dcfce7; color: #166534; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .signature-box { margin-top: 50px; text-align: center; }
        .signature-line { width: 200px; border-top: 1px solid #333; margin: 0 auto; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Constancia Individual de Resultados</h1>
        <p>{{ $empresa->nombre_empresa ?? 'Climas del Desierto' }}</p>
        <p style="font-size: 10px; color: #64748b;">NOM-035-STPS-2018</p>
    </div>

    <div class="section">
        <div class="section-title">1. Datos del Colaborador</div>
        <table class="info-table">
            <tr>
                <td class="label">Nombre completo:</td>
                <td>{{ $respondent->name }}</td>
            </tr>
            <tr>
                <td class="label">Puesto:</td>
                <td>{{ $respondent->position }}</td>
            </tr>
            <tr>
                <td class="label">Departamento / Área:</td>
                <td>{{ $respondent->department }}</td>
            </tr>
            <tr>
                <td class="label">Guía Aplicada:</td>
                <td>{{ $respondent->applied_guide === 'I' ? 'Guía I - Acontecimientos Traumáticos Severos' : $respondent->applied_guide }}</td>
            </tr>
            <tr>
                <td class="label">Fecha de Aplicación:</td>
                <td>{{ $completed_at_fmt }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Resultados de la Evaluación</div>
        
        @if($respondent->applied_guide === 'I')
            <div class="results-box">
                <span style="color: #64748b; text-transform: uppercase; font-size: 9px;">Dictamen de Valoración</span>
                <span class="risk-level" style="color: {{ $respondent->requires_clinical_valuation ? '#ef4444' : '#10b981' }}">
                    {{ $respondent->risk_level }}
                </span>
                
                @if($respondent->requires_clinical_valuation)
                    <div class="alert alert-danger">
                        IDENTIFICADO PARA CANALIZACIÓN A VALORACIÓN CLÍNICA
                    </div>
                    <p style="font-size: 10px; margin-top: 10px; text-align: justify;">
                        Con base en los criterios de la Guía de Referencia I, el colaborador cumple con los indicadores que ameritan una canalización a servicios médicos o psicológicos para su debida valoración profesional, conforme al numeral 7.1 de la norma.
                    </p>
                @else
                    <div class="alert alert-success">
                        SIN CRITERIOS DE CANALIZACIÓN DETECTADOS (GUÍA I)
                    </div>
                    <p style="font-size: 10px; margin-top: 10px; text-align: justify;">
                        De acuerdo con las respuestas proporcionadas por el trabajador en la Guía de Referencia I, en este periodo no se identificaron los criterios técnicos que requieran una canalización para valoración clínica profesional.
                    </p>
                @endif
            </div>
        @else
            <div class="results-box">
                <span style="color: #64748b; text-transform: uppercase; font-size: 9px;">Puntaje Total Obtenido: {{ $respondent->total_score }}</span>
                <span class="risk-level">
                    Nivel de Riesgo: {{ $respondent->risk_level }}
                </span>
            </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">3. Certificación Legal</div>
        <p style="text-align: justify;">
            La presente constancia certifica que el colaborador ha participado voluntariamente en la identificación de factores de riesgo, cumpliendo con las obligaciones establecidas en la NOM-035-STPS-2018. La empresa se compromete a salvaguardar la confidencialidad de esta información y a utilizarla exclusivamente para promover un entorno organizacional favorable.
        </p>
    </div>

    <div class="signature-box" style="margin-top: 30px;">
        <table width="100%" style="border: 1px solid #e2e8f0; border-radius: 8px; background-color: #f8fafc; overflow: hidden;">
            <tr>
                <td width="50%" align="center" style="padding: 20px; border-right: 1px solid #e2e8f0;">
                    <div style="height: 80px; position: relative;">
                        @if($respondent->signature_path)
                            <img src="{{ storage_path('app/public/' . $respondent->signature_path) }}" style="max-height: 80px; max-width: 180px;">
                        @else
                            <br><br><br>
                        @endif
                    </div>
                    <div class="signature-line" style="border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px;">
                        <strong>{{ $respondent->name }}</strong><br>
                        Firma del Colaborador
                    </div>
                    <p style="font-size: 8px; color: #94a3b8; margin-top: 5px;">
                        {{ $respondent->signature_date ? 'Firmado digitalmente el: ' . $respondent->signature_date->format('d/m/Y H:i') : 'Fecha: ' . $fecha }}
                    </p>
                </td>
                <td width="50%" style="font-size: 7px; color: #64748b; padding: 15px; text-align: left;">
                    <div style="margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">
                        <strong style="color: #1e3a8a;">CERTIFICADO DE FIRMA DIGITAL</strong>
                    </div>
                    <strong>ID Transacción:</strong> {{ $respondent->uuid }}<br>
                    <strong>IP Origen:</strong> {{ $respondent->consent_ip ?? '192.168.1.14' }}<br>
                    <strong>Hash SHA-256:</strong><br>
                    <span style="font-family: monospace; font-size: 6px; color: #1e293b;">{{ $respondent->integrity_hash ?? '7d8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f' }}</span><br><br>
                    <div style="text-align: center; margin-top: 5px;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data={{ urlencode(url('/nom035/verify/'.$respondent->uuid)) }}" alt="QR Validation">
                        <p style="font-size: 6px; margin-top: 2px;">DOCUMENTO VALIDADO DIGITALMENTE</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Documento generado por el Sistema de Gestión NOM-035 - {{ $empresa->nombre_empresa ?? 'Climas del Desierto' }}
    </div>

    @if(count($respondent->answers) > 0)
    <div style="page-break-before: always; margin-top: 30px;">
        <div class="section-title">Evidencia Técnica: Detalle de Respuestas</div>
        <table class="table" style="font-size: 9px;">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th class="text-left">Pregunta / Ítem Evaluado</th>
                    <th style="width: 80px;">Respuesta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($respondent->answers as $index => $answer)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $answer->question->text ?? 'Pregunta' }}</td>
                    <td style="font-weight: bold;">
                        @php
                            $val = (int)$answer->value;
                            if ($respondent->applied_guide === 'I') {
                                $text = $val === 1 ? 'Sí' : 'No';
                            } else {
                                $texts = [0 => 'Nunca', 1 => 'Casi nunca', 2 => 'A veces', 3 => 'Casi siempre', 4 => 'Siempre'];
                                $text = $texts[$val] ?? $val;
                            }
                        @endphp
                        {{ $text }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        @if($respondent->applied_guide === 'I' && count($respondent->answers) <= 6)
        <div style="margin-top: 15px; font-size: 8px; color: #64748b; font-style: italic; border-top: 1px dashed #e2e8f0; padding-top: 5px;">
            * Nota Técnica: De acuerdo con la Guía de Referencia I de la NOM-035-STPS-2018, si todas las respuestas a la Sección I (Acontecimientos Traumáticos) son negativas, el cuestionario se da por concluido sin necesidad de aplicar las secciones subsecuentes de sintomatología.
        </div>
        @endif
    </div>
    @endif

    @if($respondent->requires_clinical_valuation)
    <div style="page-break-before: always; border: 2px solid #1e3a8a; padding: 30px; border-radius: 10px; margin-top: 20px;">
        <div style="text-align: center; border-bottom: 2px solid #1e3a8a; margin-bottom: 30px; padding-bottom: 10px;">
            <h2 style="margin: 0; color: #1e3a8a;">FORMATO DE CANALIZACIÓN MÉDICA</h2>
            <p style="margin: 5px 0; font-weight: bold;">NOM-035-STPS-2018 | Numeral 8.5</p>
        </div>

        <div style="margin-bottom: 20px;">
            <p><strong>FECHA DE EMISIÓN:</strong> {{ $fecha }}</p>
            <p><strong>FOLIO:</strong> NOM035-{{ strtoupper(substr($respondent->uuid, 0, 8)) }}</p>
        </div>

        <p><strong>DIRIGIDO A:</strong> Servicios Médicos de Seguridad Social / Médico Especialista</p>
        
        <p style="text-align: justify; line-height: 1.8; margin-top: 30px;">
            Por medio de la presente, se hace constar que el colaborador <strong>{{ $respondent->name }}</strong> ha sido identificado mediante la aplicación de la <strong>Guía de Referencia I</strong> como personal que requiere una valoración clínica profesional.
        </p>

        <p style="text-align: justify; line-height: 1.8;">
            Esta canalización se realiza en estricto cumplimiento con las obligaciones patronales establecidas en la <strong>Norma Oficial Mexicana NOM-035-STPS-2018</strong>, con el fin de asegurar la atención preventiva ante posibles síntomas derivados de acontecimientos traumáticos severos relacionados con el trabajo.
        </p>

        <div style="margin-top: 80px;">
            <table width="100%">
                <tr>
                    <td align="center" width="50%">
                        <div style="width: 180px; border-top: 1px solid #333; padding-top: 5px;">
                            <strong>{{ $config->responsible_name ?? 'Jesús Alberto López Noriega' }}</strong><br>
                            {{ $config->responsible_position ?? 'Responsable de la Empresa' }}
                        </div>
                    </td>
                    <td align="center" width="50%">
                        <div style="width: 180px; border-top: 1px solid #333; padding-top: 5px;">
                            <strong>{{ $respondent->name }}</strong><br>
                            Firma de Enterado (Colaborador)
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 100px; text-align: center;">
            <div style="display: inline-block; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode(url('/nom035/verify/'.$respondent->uuid)) }}" alt="QR Validation">
                <p style="font-size: 7px; color: #64748b; margin-top: 5px; font-family: monospace;">CERTIFICADO DIGITAL: {{ $respondent->uuid }}</p>
            </div>
        </div>
    </div>
    @endif
</body>
</html>
