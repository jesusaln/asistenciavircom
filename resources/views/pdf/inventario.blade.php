<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Inventario - {{ $almacen->nombre ?? 'General' }}</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #FF5C1F;
            margin: 0;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            margin: 0;
            color: #444;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #eee;
            padding: 10px;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
        }
        .label {
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            font-size: 8px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.data-table th {
            background-color: #444;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
        }
        table.data-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .category-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        
        .totals-box {
            margin-top: 20px;
            float: right;
            width: 250px;
        }
        .total-row {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .grand-total {
            background-color: #FF5C1F;
            color: white;
            padding: 8px;
            margin-top: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: -30px;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #999;
        }
        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            color: white;
            font-weight: bold;
        }
        .badge-danger { background-color: #dc3545; }
        .badge-warning { background-color: #ffc107; color: #000; }
        .badge-success { background-color: #28a745; }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%">
            <tr>
                <td width="50%">
                    @if(isset($empresa['logo_base64']))
                        <img src="{{ $empresa['logo_base64'] }}" style="max-height: 50px;">
                    @else
                        <p class="company-name">{{ $empresa['nombre'] }}</p>
                    @endif
                    <div style="font-size: 8px; color: #666; margin-top: 5px;">
                        {{ $empresa['razon_social'] }} | RFC: {{ $empresa['rfc'] }}<br>
                        {{ $empresa['direccion'] }}
                    </div>
                </td>
                <td width="50%" valign="top">
                    <p class="report-title">AUDITORÍA DE INVENTARIO</p>
                    <p style="text-align: right; font-size: 10px; color: #666;">
                        Folio: {{ date('Ymd-His') }}<br>
                        Emisión: {{ $fecha }}
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-box">
        <table class="info-table">
            <tr>
                <td width="33%">
                    <span class="label">Almacén:</span><br>
                    <strong>{{ $almacen->nombre ?? 'GENERAL' }}</strong>
                </td>
                <td width="33%">
                    <span class="label">Responsable:</span><br>
                    <strong>{{ $user->name }}</strong>
                </td>
                <td width="33%">
                    <span class="label">Estado Almacén:</span><br>
                    <strong>{{ strtoupper($almacen->estado ?? 'ACTIVO') }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="12%">CÓDIGO</th>
                <th width="33%">PRODUCTO / CATEGORÍA</th>
                <th width="10%" class="text-center">STOCK</th>
                <th width="8%" class="text-center">U.M.</th>
                <th width="12%" class="text-right">COSTO UNIT.</th>
                <th width="12%" class="text-right">P. VENTA</th>
                <th width="13%" class="text-right">VALOR TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalArticulos = 0;
                $valorTotalInventario = 0;
                $currentAlmacen = '';
            @endphp
            @foreach($inventario as $item)
                @if($almacen->nombre == 'Todos los Almacenes' && $item->almacen && $currentAlmacen != $item->almacen->nombre)
                    <tr class="category-row">
                        <td colspan="7" style="padding: 5px 10px;">UBICACIÓN: {{ $item->almacen->nombre }}</td>
                    </tr>
                    @php $currentAlmacen = $item->almacen->nombre; @endphp
                @endif
                @php 
                    $costo = $item->producto->precio_compra ?? 0;
                    $subtotal = $item->cantidad * $costo;
                    $totalArticulos += $item->cantidad;
                    $valorTotalInventario += $subtotal;
                @endphp
                <tr>
                    <td class="text-bold">{{ $item->producto->codigo ?? 'S/N' }}</td>
                    <td>
                        {{ $item->producto->nombre ?? 'N/A' }}<br>
                        <small style="color: #888;">{{ $item->producto->categoria->nombre ?? 'Sin Categoría' }}</small>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $item->cantidad <= $item->stock_minimo ? 'badge-danger' : ($item->cantidad <= $item->stock_minimo * 1.5 ? 'badge-warning' : 'badge-success') }}">
                            {{ number_format($item->cantidad) }}
                        </span>
                    </td>
                    <td class="text-center">{{ $item->producto->unidad_medida ?? 'PZA' }}</td>
                    <td class="text-right">${{ number_format($costo, 2) }}</td>
                    <td class="text-right">${{ number_format($item->producto->precio_venta ?? 0, 2) }}</td>
                    <td class="text-right text-bold">${{ number_format($subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-box">
        <table width="100%">
            <tr class="total-row">
                <td class="label">Total Artículos:</td>
                <td class="text-right text-bold">{{ number_format($totalArticulos) }}</td>
            </tr>
            <tr class="total-row">
                <td class="label">Valor Total Costo:</td>
                <td class="text-right text-bold">${{ number_format($valorTotalInventario, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td style="color: white;">VALOR TOTAL INVENTARIO:</td>
                <td class="text-right" style="color: white;">${{ number_format($valorTotalInventario, 2) }}</td>
            </tr>
        </table>
    </div>

    <div style="clear: both; margin-top: 40px; font-size: 7px; color: #aaa;">
        * Valores calculados con base en el precio de compra registrado.<br>
        * Auditoría generada vía terminal móvil por el usuario autenticado.
    </div>

    <div class="footer">
        © {{ date('Y') }} {{ $empresa['nombre'] }} - Sistema de Gestión de Inventarios. Página <span class="page-num"></span>
    </div>

    <script type="php">
        if ( isset($pdf) ) {
            $pdf->page_script('
                $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
                $size = 7;
                $pageText = "Página " . $PAGE_NUM . " de " . $PAGE_COUNT;
                $pdf->text(520, 820, $pageText, $font, $size);
            ');
        }
    </script>
</body>
</html>
