<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acuse de Firma Digital</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #4f46e5; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #4f46e5; }
        .title { font-size: 18px; text-transform: uppercase; margin-top: 10px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 12px; font-weight: bold; color: #666; text-transform: uppercase; border-bottom: 1px solid #eee; margin-bottom: 10px; }
        .data-row { margin-bottom: 5px; font-size: 14px; }
        .label { font-weight: bold; width: 150px; display: inline-block; }
        .seal-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; font-family: monospace; font-size: 10px; word-break: break-all; margin-top: 30px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999; padding-top: 10px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">CLIMAS DEL DESIERTO</div>
        <div class="title">Acuse de Recibo de Firma Electrónica Avanzada</div>
    </div>

    <div class="section">
        <div class="section-title">Información del Documento</div>
        <div class="data-row"><span class="label">Título:</span> {{ $contrato->titulo }}</div>
        <div class="data-row"><span class="label">Folio Interno:</span> {{ $contrato->id }}</div>
        <div class="data-row"><span class="label">Fecha de Firma:</span> {{ $contrato->signed_at->format('d/m/Y H:i:s') }}</div>
        <div class="data-row"><span class="label">Hash (SHA-256):</span> {{ hash('sha256', $contrato->contenido) }}</div>
    </div>

    <div class="section">
        <div class="section-title">Información del Firmante (Beneficiario)</div>
        <div class="data-row"><span class="label">Cliente Registrado:</span> {{ $contrato->cliente->nombre_razon_social }}</div>
        <div class="data-row"><span class="label">Identidad SAT (FIEL):</span> <span style="font-weight: bold; color: #4f46e5;">{{ $contrato->metadata['nombre_firmante'] ?? '—' }}</span></div>
        <div class="data-row"><span class="label">RFC del Firmante:</span> {{ $contrato->metadata['rfc_firmante'] ?? '—' }}</div>
        <div class="data-row"><span class="label">Serie Certificado:</span> {{ $contrato->metadata['cer_serial'] ?? '—' }}</div>
    </div>

    <div class="section">
        <div class="section-title">Sellos Digitales de Integridad y Aceptación</div>
        <p style="font-size: 11px;">Este documento cuenta con las firmas electrónicas de ambas partes, utilizando certificados emitidos por el Servicio de Administración Tributaria (SAT). Los siguientes sellos garantizan que el contenido no ha sido alterado.</p>
        
        <div class="seal-box" style="margin-top: 15px;">
            <strong>CONSTANCIA DE EMISIÓN (SISTEMA CLIMAS DEL DESIERTO):</strong><br>
            <span style="color:#666;">RFC: {{ $contrato->metadata['rfc_proveedor'] ?? 'LONJ880321KMA' }} | {{ $contrato->signature_provider ?? 'CONSTANCIA_EMISION_SISTEMA' }}</span><br>
            {{ $contrato->metadata['hash_proveedor'] ?? hash('sha256', 'CDD' . $contrato->id) }}
        </div>

        <div class="seal-box" style="margin-top: 10px; background: #f0fdf4; border-color: #bbf7d0;">
            <strong>SELLO DEL BENEFICIARIO (CLIENTE):</strong><br>
            <span style="color:#166534;">RFC: {{ $contrato->metadata['rfc_firmante'] ?? '—' }} | {{ $contrato->signature_client }}</span><br>
            {{ $contrato->hash_documento }}
        </div>
    </div>

    <div style="margin-top: 50px; text-align: center;">
        <p style="font-size: 12px; color: #4f46e5; font-weight: bold;">DOCUMENTO LEGALMENTE VINCULANTE</p>
        <p style="font-size: 10px;">Cumple con los requisitos de la Firma Electrónica Avanzada según la Ley de Firma Electrónica Avanzada y el Código de Comercio.</p>
    </div>

    <div class="footer">
        Este documento es un acuse de recibo generado por el Sistema de Blindaje REPSE de Climas del Desierto.<br>
        Identificador Único de Operación: {{ $contrato->signing_token }}
    </div>
</body>
</html>
