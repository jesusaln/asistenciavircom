<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dossier de Cumplimiento REPSE - {{ $contratista->rfc }}</title>
    <style>
        @page { margin: 2cm; }
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.5; font-size: 11px; }
        .header { text-align: center; border-bottom: 2px solid #4f46e5; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #4f46e5; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; color: #666; font-size: 10px; }
        
        .section { margin-bottom: 25px; }
        .section-title { background: #f3f4f6; padding: 8px 15px; border-left: 4px solid #4f46e5; font-weight: bold; text-transform: uppercase; margin-bottom: 15px; font-size: 12px; }
        
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .grid td { padding: 8px; border: 1px solid #e5e7eb; vertical-align: top; }
        .label { color: #6b7280; font-size: 9px; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 3px; }
        .value { font-weight: bold; color: #111827; }
        
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef9c3; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        .matrix { width: 100%; border-collapse: collapse; }
        .matrix th { background: #f9fafb; padding: 10px; border: 1px solid #e5e7eb; font-size: 9px; text-align: center; }
        .matrix td { padding: 10px; border: 1px solid #e5e7eb; text-align: center; }
        .status-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
        
        .audit-trail { font-size: 9px; color: #666; }
        .footer { position: fixed; bottom: -1cm; left: 0; right: 0; text-align: center; font-size: 9px; border-top: 1px solid #e5e7eb; padding-top: 10px; }
        
        .watermark { position: absolute; top: 40%; left: 10%; font-size: 100px; color: #f3f4f6; transform: rotate(-45deg); z-index: -1; opacity: 0.5; }
    </style>
</head>
<body>
    <div class="watermark">AUDITADO</div>

    <div class="header">
        <h1>Dossier de Cumplimiento REPSE</h1>
        <p>Generado el {{ $date }} | Portal de Cumplimiento Legal - Climas del Desierto</p>
    </div>

    <div class="section">
        <div class="section-title">Información del Contratista</div>
        <table class="grid">
            <tr>
                <td width="50%">
                    <span class="label">Razón Social</span>
                    <span class="value">{{ $contratista->nombre_razon_social }}</span>
                </td>
                <td width="50%">
                    <span class="label">RFC</span>
                    <span class="value">{{ $contratista->rfc }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Número REPSE</span>
                    <span class="value">{{ $contratista->repse_number ?? 'NO REGISTRADO' }}</span>
                </td>
                <td>
                    <span class="label">Vigencia REPSE</span>
                    <span class="value {{ $contratista->repse_expiry && $contratista->repse_expiry->isPast() ? 'badge-danger' : '' }}">
                        {{ $contratista->repse_expiry ? $contratista->repse_expiry->format('d/m/Y') : 'N/A' }}
                    </span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Estatus SAT (Lista 69-B)</span>
                    <span class="badge {{ $contratista->sat_status === 'active' ? 'badge-success' : 'badge-danger' }}">
                        {{ strtoupper($contratista->sat_status ?? 'PENDIENTE') }}
                    </span>
                </td>
                <td>
                    <span class="label">Actividad Especializada</span>
                    <span class="value">{{ $contratista->repse_activity ?? 'N/A' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Matriz de Cumplimiento Documental ({{ now()->year }})</div>
        <table class="matrix">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>SAT</th>
                    <th>IMSS</th>
                    <th>INFONAVIT</th>
                    <th>SUA / Pago</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach(range(1, 12) as $m)
                    @php
                        $monthDocs = $docs->get($m) ?? collect();
                        $sat = $monthDocs->where('type', 'sat_opinion')->first();
                        $imss = $monthDocs->where('type', 'imss_opinion')->first();
                        $info = $monthDocs->where('type', 'infonavit_opinion')->first();
                        $sua = $monthDocs->where('type', 'sua')->first();
                        
                        $countValidated = $monthDocs->where('status', 'validated')->count();
                    @endphp
                    <tr>
                        <td style="font-weight: bold;">{{ Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</td>
                        <td>{!! $sat ? ($sat->status === 'validated' ? '✅' : '⏳') : '❌' !!}</td>
                        <td>{!! $imss ? ($imss->status === 'validated' ? '✅' : '⏳') : '❌' !!}</td>
                        <td>{!! $info ? ($info->status === 'validated' ? '✅' : '⏳') : '❌' !!}</td>
                        <td>{!! $sua ? ($sua->status === 'validated' ? '✅' : '⏳') : '❌' !!}</td>
                        <td>
                            @if($countValidated >= 3)
                                <span class="badge badge-success">CUMPLIDO</span>
                            @elseif($monthDocs->count() > 0)
                                <span class="badge badge-warning">EN PROCESO</span>
                            @else
                                <span class="badge badge-danger">FALTANTE</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Audit Trail (Últimas Acciones)</div>
        <table class="grid audit-trail">
            <thead>
                <tr style="background: #f9fafb;">
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                @foreach($audit_trail as $audit)
                    <tr>
                        <td>{{ $audit->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $audit->user->name ?? 'Sistema' }}</td>
                        <td>{{ strtoupper($audit->event) }}</td>
                        <td>
                            @if($audit->event === 'updated')
                                Cambio en: {{ implode(', ', array_keys($audit->new_values)) }}
                            @else
                                Documento {{ $audit->auditable_type }} ID: {{ $audit->auditable_id }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Este documento es una representación digital del expediente de cumplimiento almacenado en el sistema.<br>
        <strong>Climas del Desierto S.A. de C.V.</strong> | RFC: {{ $miEmpresa->rfc ?? 'N/A' }} | 
        ID de Verificación Digital: {{ md5($contratista->id . now()->toDateString()) }}
    </div>
</body>
</html>
