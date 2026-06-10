<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acuse de Recibo de Denuncia - NOM-035</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; line-height: 1.6; margin: 30px; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #1a56db; padding-bottom: 15px; }
        .header h1 { margin: 0; font-size: 22px; color: #1a56db; }
        .folio-box { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; text-align: center; border-radius: 10px; margin-bottom: 30px; }
        .folio-number { font-size: 24px; font-weight: bold; color: #1e293b; margin: 5px 0; }
        .section { margin-bottom: 25px; }
        .section-title { font-weight: bold; font-size: 14px; color: #475569; border-bottom: 1px solid #f1f5f9; margin-bottom: 10px; padding-bottom: 5px; text-transform: uppercase; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 8px 0; border-bottom: 1px solid #f9f9f9; }
        .label { font-weight: bold; width: 30%; color: #64748b; }
        .description-box { background-color: #fcfcfc; border: 1px dashed #cbd5e1; padding: 15px; border-radius: 5px; font-style: italic; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 10px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; background-color: #1a56db; color: white; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Acuse de Recibo: Mecanismo de Quejas</h1>
        <p style="margin-top: 5px; font-weight: bold;">{{ $empresa->nombre_empresa ?? 'Climas del Desierto' }}</p>
        <p style="font-size: 10px; color: #64748b;">Cumplimiento NOM-035-STPS-2018 (Numerales 7.1 y 8.1)</p>
    </div>

    <div class="folio-box">
        <span style="font-size: 10px; text-transform: uppercase; color: #64748b;">Folio de Seguimiento</span>
        <div class="folio-number">{{ $complaint->folio }}</div>
        <p style="font-size: 10px; color: #10b981; font-weight: bold;">✓ Registrado exitosamente en el sistema</p>
    </div>

    <div class="section">
        <div class="section-title">Detalles del Registro</div>
        <table class="info-table">
            <tr>
                <td class="label">Fecha de registro:</td>
                <td>{{ $complaint->created_at->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <td class="label">Tipo de situación:</td>
                <td><span class="badge">{{ strtoupper($complaint->type) }}</span></td>
            </tr>
            <tr>
                <td class="label">Fecha del incidente:</td>
                <td>{{ $complaint->incident_date ? $complaint->incident_date->format('d/m/Y') : 'No especificada' }}</td>
            </tr>
            <tr>
                <td class="label">Modalidad:</td>
                <td>{{ $complaint->is_anonymous ? 'Anónima (Confidencial)' : 'Identificada' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Descripción del Incidente</div>
        <div class="description-box">
            {{ $complaint->description }}
        </div>
    </div>

    <div class="section" style="margin-top: 40px;">
        <p style="text-align: justify; font-size: 11px; color: #475569;">
            Este documento confirma que su queja/sugerencia ha sido recibida por el departamento encargado de la gestión de factores de riesgo psicosocial. La empresa iniciará un proceso de revisión conforme a la política interna, garantizando en todo momento la confidencialidad y la ausencia de represalias.
        </p>
        <p style="text-align: justify; font-size: 11px; color: #475569; font-weight: bold;">
            Puede consultar el estatus de su folio en el portal oficial de la empresa utilizando el código de seguimiento arriba mencionado.
        </p>
    </div>

    <div class="footer">
        Este documento es un comprobante automático de recepción digital. <br>
        {{ $empresa->nombre_empresa ?? 'Climas del Desierto' }} - {{ date('Y') }}
    </div>
</body>
</html>
