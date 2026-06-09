<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Venta {{ $venta->numero_venta }}</title>
    @php
        $configuracion = \App\Models\EmpresaConfiguracion::getConfig();
        $moneda = $configuracion->moneda ?? 'MXN';
    @endphp
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 2px solid {{ $empresa['color_principal'] ?? '#FF6B35' }};
            padding-bottom: 15px;
        }
        .company-info {
            float: left;
            width: 60%;
        }
        .company-info h1 {
            margin: 0 0 5px 0;
            font-size: 20px;
            color: {{ $empresa['color_principal'] ?? '#FF6B35' }};
        }
        .company-info .rfc {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        .company-info p {
            margin: 0;
            line-height: 1.4;
            color: #666;
        }
        .invoice-info {
            float: right;
            text-align: right;
            width: 35%;
        }
        .invoice-info h2 {
            margin: 0;
            font-size: 18px;
            color: {{ $empresa['color_principal'] ?? '#FF6B35' }};
            background-color: {{ $empresa['color_principal'] ?? '#FF6B35' }}15;
            padding: 8px 15px;
            border-radius: 4px;
        }
        .invoice-info .folio {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0 5px 0;
        }
        .clear {
            clear: both;
        }
        .client-info {
            margin: 25px 0;
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid {{ $empresa['color_principal'] ?? '#FF6B35' }};
        }
        .client-info h3 {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .client-info .name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .client-info p {
            margin: 3px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th {
            background-color: {{ $empresa['color_principal'] ?? '#FF6B35' }};
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 8px;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-container {
            width: 100%;
            margin-top: 20px;
        }
        .totals {
            width: 45%;
            float: right;
        }
        .totals table {
            margin-bottom: 0;
        }
        .totals table td {
            border: none;
            padding: 6px 10px;
        }
        .totals table tr:nth-child(even) {
            background-color: transparent;
        }
        .total-row {
            font-weight: bold;
            font-size: 15px;
            background-color: {{ $empresa['color_principal'] ?? '#FF6B35' }}15 !important;
        }
        .total-row td {
            padding: 8px 10px !important;
        }
        .bank-section {
            clear: both;
            margin-top: 10px; /* Reducido de 25px */
            padding: 8px; /* Reducido de 15px */
            background-color: #e8f4fd;
            border-left: 3px solid {{ $empresa['color_principal'] ?? '#FF6B35' }};
            border-radius: 4px;
        }
        .bank-section h4 {
            margin: 0 0 5px 0; /* Reducido */
            color: {{ $empresa['color_principal'] ?? '#FF6B35' }};
            font-size: 10px; /* Reducido */
        }
        .bank-info-container {
            font-size: 9px; /* Más pequeño */
            display: table;
            width: 100%;
        }
        .bank-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .bank-info-container p {
            margin: 1px 0; /* Muy pegado */
        }
        .warranty-section {
            margin-top: 10px; /* Reducido de 20px */
            padding: 8px; /* Reducido de 12px */
            background-color: #f5f5f5;
            border-radius: 4px;
            font-size: 6px; /* Reducido de 7px */
            line-height: 1.2; /* Line-height más apretado */
            color: #555;
        }
        .warranty-section strong {
            color: #333;
        }
        .footer {
            position: fixed;
            bottom: 15px; /* Más abajo */
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 8px; /* Más pequeño */
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
        .footer p {
            margin: 2px 0;
        }
        .notes {
            margin-top: 10px;
            padding: 8px;
            background-color: #fffbeb;
            border-left: 3px solid #f59e0b;
            border-radius: 4px;
            page-break-inside: avoid;
        }
        .notes h4 {
            margin: 0 0 3px 0;
            color: #d97706; /* Amber 600 */
            font-size: 10px;
        }
        .notes p {
            margin: 0;
            font-size: 9px;
            color: #555;
            line-height: 1.4;
        }
        /* Ajustar tabla de productos para ahorrar espacio vertical */
        td {
            padding: 6px 4px; /* Reducido padding vertical */
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            @if(!empty($empresa['logo_base64']))
                <img src="{{ $empresa['logo_base64'] }}" alt="Logo" style="max-height: 40px; margin-bottom: 5px;">
            @elseif(!empty($empresa['logo_path_absolute']))
                <img src="{{ $empresa['logo_path_absolute'] }}" alt="Logo" style="max-height: 40px; margin-bottom: 5px;">
            @endif
            <h1 style="font-size: 18px;">{{ $empresa['nombre'] ?? 'Empresa' }}</h1>
            @if(!empty($empresa['razon_social']))
                <p style="font-size: 10px;"><strong>Razón Social:</strong> {{ $empresa['razon_social'] }}</p>
            @endif
            @if(!empty($empresa['rfc']))
                <p class="rfc" style="font-size: 10px;">RFC: {{ $empresa['rfc'] }}</p>
            @endif
            <p style="font-size: 10px;">
                {{ $empresa['direccion'] ?? '' }}<br>
                @if(!empty($empresa['telefono'])) Tel: {{ $empresa['telefono'] }} @endif
                @if(!empty($empresa['email'])) | {{ $empresa['email'] }} @endif
            </p>
        </div>
        <div class="invoice-info">
            <h2 style="font-size: 14px; padding: 5px 10px;">NOTA DE VENTA</h2>
            <p class="folio" style="margin: 5px 0;">{{ $venta->numero_venta }}</p>
            <p style="font-size: 10px;">
                <strong>Fecha:</strong> {{ $venta->created_at ? $venta->created_at->format('d/m/Y') : ($venta->fecha ?? date('d/m/Y')) }}
            </p>
            <p style="font-size: 10px;"><strong>Moneda:</strong> {{ $moneda }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="client-info" style="margin: 15px 0; padding: 10px;">
        <h3 style="margin: 0 0 5px 0; font-size: 11px;">Datos del Cliente</h3>
        <p class="name" style="font-size: 11px; margin-bottom: 2px;">{{ $venta->cliente ? $venta->cliente->nombre_razon_social : 'Público General' }}</p>
        <div style="font-size: 10px;">
            @if($venta->cliente && $venta->cliente->rfc && $venta->cliente->rfc !== 'XAXX010101000')
                <span style="margin-right: 15px;"><strong>RFC:</strong> {{ $venta->cliente->rfc }}</span>
            @endif
            @if($venta->cliente && $venta->cliente->telefono)
                <span style="margin-right: 15px;"><strong>Tel:</strong> {{ $venta->cliente->telefono }}</span>
            @endif
            @if($venta->cliente && $venta->cliente->email)
                <span><strong>Email:</strong> {{ $venta->cliente->email }}</span>
            @endif
            @if($venta->cliente && ($venta->cliente->direccion ?? $venta->cliente->direccion_completa ?? false))
                <br><strong>Dir:</strong> {{ $venta->cliente->direccion ?? $venta->cliente->direccion_completa }}
            @endif
        </div>
    </div>

    <table style="margin-bottom: 15px;">
        <thead>
            <tr>
                <th style="width: 8%; padding: 5px;" class="text-center">Cant.</th>
                <th style="width: 47%; padding: 5px;">Descripción</th>
                <th style="width: 15%; padding: 5px;" class="text-right">Precio Unit.</th>
                <th style="width: 10%; padding: 5px;" class="text-right">Desc.</th>
                <th style="width: 20%; padding: 5px;" class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Recopilar items con precio 0 que son componentes de kit (solo para trackear series)
                $itemsConSeriesOcultos = $venta->items->filter(fn($i) => $i->precio == 0 && $i->series->count() > 0);
                $seriesDeComponentes = [];
                foreach ($itemsConSeriesOcultos as $itemOculto) {
                    $productoId = $itemOculto->ventable_id;
                    if (!isset($seriesDeComponentes[$productoId])) {
                        $seriesDeComponentes[$productoId] = collect();
                    }
                    $seriesDeComponentes[$productoId] = $seriesDeComponentes[$productoId]->merge($itemOculto->series);
                }
            @endphp
            @foreach($venta->items as $item)
            @php
                $ventable = $item->ventable;
                $esKit = $ventable && method_exists($ventable, 'esKit') && $ventable->esKit();
                // Ocultar items con precio 0 (son componentes de kit para trackear series)
                $esComponenteOculto = $item->precio == 0;
            @endphp
            @if(!$esComponenteOculto)
            <tr>
                <td class="text-center">{{ $item->cantidad }}</td>
                <td>
                    {{ $ventable ? $ventable->nombre : 'Producto no encontrado' }}
                    @if($esKit)
                        <span style="color: #7c3aed; font-size: 10px; font-weight: bold;"> (KIT)</span>
                    @endif
                    {{-- Series del item principal (para productos individuales) --}}
                    @if(!$esKit && $item->series && $item->series->count() > 0)
                        <div class="series-info">SN: {{ $item->series->pluck('numero_serie')->implode(', ') }}</div>
                    @endif
                </td>
                <td class="text-right">${{ number_format($item->precio, 2) }}</td>
                <td class="text-right">{{ $item->descuento > 0 ? number_format($item->descuento, 0) . '%' : '-' }}</td>
                <td class="text-right">${{ number_format($item->cantidad * $item->precio * (1 - ($item->descuento / 100)), 2) }}</td>
            </tr>
            {{-- Si es un kit, mostrar sus componentes --}}
            @if($esKit)
                @php
                    // Cargar los componentes del kit
                    $kitItems = $ventable->kitItems()->with('item')->get();
                @endphp
                {{-- Encabezado de componentes del kit --}}
                <tr style="background-color: #ede9fe;">
                    <td colspan="5" style="font-size: 10px; color: #5b21b6; padding: 4px 6px 4px 20px; font-style: italic;">
                        Incluye:
                    </td>
                </tr>
                @foreach($kitItems as $kitItem)
                    @php
                        $componente = $kitItem->item;
                        $cantidadComponente = $kitItem->cantidad * $item->cantidad;
                        $seriesComponente = collect();
                        
                        // Buscar series del componente en los items ocultos
                        if ($componente && $kitItem->esProducto() && isset($seriesDeComponentes[$componente->id])) {
                            $seriesComponente = $seriesDeComponentes[$componente->id];
                        }
                    @endphp
                    <tr style="background-color: #faf5ff;">
                        <td class="text-center" style="color: #888; font-size: 10px; border-bottom: 1px dashed #e9d5ff;">
                            {{ $cantidadComponente }}
                        </td>
                        <td style="padding-left: 30px; font-size: 10px; color: #666; border-bottom: 1px dashed #e9d5ff;">
                            &bull; {{ $componente ? $componente->nombre : 'Componente' }}
                            @if($kitItem->esServicio())
                                <span style="color: #059669; font-size: 9px;"> (Servicio)</span>
                            @endif
                            {{-- Series del componente --}}
                            @if($seriesComponente->count() > 0)
                                <div style="font-size: 9px; color: #7c3aed; margin-left: 10px;">
                                    SN: {{ $seriesComponente->pluck('numero_serie')->implode(', ') }}
                                </div>
                            @endif
                        </td>
                        <td class="text-right" style="color: #aaa; font-size: 9px; border-bottom: 1px dashed #e9d5ff;">-</td>
                        <td class="text-right" style="color: #aaa; font-size: 9px; border-bottom: 1px dashed #e9d5ff;">-</td>
                        <td class="text-right" style="color: #7c3aed; font-size: 9px; font-style: italic; border-bottom: 1px dashed #e9d5ff;">incl.</td>
                    </tr>
                @endforeach
            @endif
            @endif
            @endforeach
        </tbody>
    </table>

    <div class="totals-container" style="margin-top: 10px;">
        <div class="totals">
            <table>
                <tr>
                    <td class="text-right" style="padding: 2px;"><strong>Subtotal:</strong></td>
                    <td class="text-right" style="padding: 2px;">${{ number_format($venta->subtotal, 2) }} {{ $moneda }}</td>
                </tr>
                @if($venta->descuento_general > 0)
                <tr>
                    <td class="text-right" style="padding: 2px;"><strong>Descuento:</strong></td>
                    <td class="text-right" style="padding: 2px;">-${{ number_format($venta->descuento_general, 2) }} {{ $moneda }}</td>
                </tr>
                @endif
                <tr>
                    <td class="text-right" style="padding: 2px;"><strong>IVA (16%):</strong></td>
                    <td class="text-right" style="padding: 2px;">${{ number_format($venta->iva, 2) }} {{ $moneda }}</td>
                </tr>
                @if(($venta->isr ?? 0) > 0)
                <tr>
                    <td class="text-right" style="padding: 2px;"><strong>Retención ISR:</strong></td>
                    <td class="text-right" style="padding: 2px; color: #dc3545;">-${{ number_format($venta->isr, 2) }} {{ $moneda }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td class="text-right" style="padding: 5px !important;"><strong>TOTAL:</strong></td>
                    <td class="text-right" style="padding: 5px !important;"><strong>${{ number_format($venta->total, 2) }} {{ $moneda }}</strong></td>
                </tr>
            </table>
        </div>
        <div class="clear"></div>
    </div>

    @if($venta->notas)
    <div class="notes">
        <h4>Notas Adicionales:</h4>
        <p>{!! nl2br(e($venta->notas)) !!}</p>
    </div>
    @endif

    {{-- DATOS BANCARIOS (Compacto) --}}
    @if($configuracion->banco || $configuracion->clabe || $configuracion->cuenta)
    <div class="bank-section">
        <h4>Datos Bancarios Banamex</h4>
        <div class="bank-info-container">
            <div class="bank-col">
                @if($configuracion->titular || $configuracion->nombre_titular) <p><strong>Titular:</strong> {{ $configuracion->nombre_titular ?? $configuracion->titular }}</p> @endif
            </div>
            <div class="bank-col">
               @if($configuracion->clabe) <p><strong>CLABE:</strong> {{ $configuracion->clabe }}</p> @endif
            </div>
        </div>
    </div>
    @endif

    {{-- SECCIÓN DE GARANTÍA (Compacta) --}}
    <div class="warranty-section">
        <strong style="font-size: 7px;">GARANTÍA:</strong> <strong>365 días</strong> para equipos | <strong>3 meses</strong> para partes eléctricas.<br>
        En caso de falla: <strong>662-460-6840</strong> o <strong>climasdeldesierto.com/soporte</strong>.<br>
        <strong>Requisitos:</strong> Nota de venta, Nombre, Teléfono, Dirección, Modelo/Serie, Falla, Fotos/Videos.<br>
        <em>Solo defectos de fabricación y uso adecuado.</em>
    </div>

    <div class="footer">
        <p><strong>{{ $empresa['nombre'] ?? 'Empresa' }}</strong> - Gracias por su preferencia.</p>
        @if(!empty($empresa['telefono']) || !empty($empresa['email']))
            <p>
                @if(!empty($empresa['telefono'])) {{ $empresa['telefono'] }} @endif
                @if(!empty($empresa['telefono']) && !empty($empresa['email'])) | @endif
                @if(!empty($empresa['email'])) {{ $empresa['email'] }} @endif
            </p>
        @endif
        <p style="font-size: 7px;">Este documento no es un comprobante fiscal</p>
    </div>
</body>
</html>

