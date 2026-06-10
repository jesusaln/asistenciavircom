<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Traspaso de Inventario - {{ $traspaso->folio }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 12px; line-height: 1.5; }
        .header { border-bottom: 2px solid {{ $configuracion['colores']['primary'] ?? '#1a73e8' }}; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { max-width: 180px; }
        .company-info { float: right; text-align: right; }
        .title { font-size: 18px; font-weight: bold; color: {{ $configuracion['colores']['primary'] ?? '#1a73e8' }}; margin-top: 0; }
        .info-grid { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-grid td { padding: 5px; vertical-align: top; }
        .label { font-weight: bold; color: #666; font-size: 10px; text-transform: uppercase; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th { background: {{ $configuracion['colores']['primary'] ?? '#1a73e8' }}; color: white; padding: 10px; text-align: left; }
        .table td { padding: 10px; border-bottom: 1px solid #eee; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .status-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .status-completado { background: #e6f4ea; color: #1e8e3e; }
        .signature-box { margin-top: 50px; width: 100%; }
        .signature-line { border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div style="float: left;">
            @if($empresa['logo'])
                <img src="{{ public_path('storage/'.$empresa['logo']) }}" class="logo">
            @else
                <h1 style="margin:0; color: {{ $configuracion['colores']['primary'] ?? '#1a73e8' }}">{{ $empresa['nombre'] }}</h1>
            @endif
        </div>
        <div class="company-info">
            <p class="title">COMPROBANTE DE TRASPASO</p>
            <p style="font-size: 14px; font-weight: bold;">Folio: {{ $traspaso->folio }}</p>
            <p>Fecha: {{ $traspaso->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <table class="info-grid">
        <tr>
            <td width="50%">
                <p class="label">Almacén Origen</p>
                <p><strong>{{ $traspaso->almacenOrigen->nombre }}</strong></p>
                @if($traspaso->almacenOrigen->ubicacion)
                    <p>{{ $traspaso->almacenOrigen->ubicacion }}</p>
                @endif
            </td>
            <td width="50%">
                <p class="label">Almacén Destino</p>
                <p><strong>{{ $traspaso->almacenDestino->nombre }}</strong></p>
                @if($traspaso->almacenDestino->ubicacion)
                    <p>{{ $traspaso->almacenDestino->ubicacion }}</p>
                @endif
            </td>
        </tr>
        <tr>
            <td>
                <p class="label">Estado</p>
                <span class="status-badge status-{{ $traspaso->estado }}">
                    {{ $traspaso->estado }}
                </span>
            </td>
            <td>
                <p class="label">Registrado por</p>
                <p>{{ $traspaso->usuarioEnvia->name }}</p>
            </td>
        </tr>
    </table>

    @if($traspaso->observaciones)
        <div style="background: #f9f9f9; padding: 10px; border-radius: 8px;">
            <p class="label">Observaciones</p>
            <p>{{ $traspaso->observaciones }}</p>
        </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th width="15%">Código</th>
                <th>Descripción del Producto</th>
                <th width="15%" style="text-align: center;">Cantidad</th>
                <th width="15%">U. Medida</th>
            </tr>
        </thead>
        <tbody>
            @foreach($traspaso->items as $item)
                <tr>
                    <td>{{ $item->producto->codigo }}</td>
                    <td>
                        <strong>{{ $item->producto->nombre }}</strong>
                        @if(!empty($item->series_ids))
                            <br>
                            <span style="font-size: 9px; color: #666;">
                                S/N: {{ implode(', ', $item->getSeries()->pluck('numero_serie')->toArray()) }}
                            </span>
                        @endif
                    </td>
                    <td style="text-align: center;">{{ $item->cantidad }}</td>
                    <td>{{ $item->producto->unidad_medida }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-box">
        <table width="100%">
            <tr>
                <td width="50%" style="text-align: center;">
                    <div style="height: 60px;"></div>
                    <div class="signature-line">Entrega</div>
                    <p style="font-size: 10px;">{{ $traspaso->usuarioEnvia->name }}</p>
                </td>
                <td width="50%" style="text-align: center;">
                    <div style="height: 60px;"></div>
                    <div class="signature-line">Recibe</div>
                    <p style="font-size: 10px;">{{ $traspaso->usuarioRecibe->name ?? '____________________' }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>{{ $empresa['nombre'] }} - {{ $empresa['direccion'] }}</p>
        <p>Generado automáticamente el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
