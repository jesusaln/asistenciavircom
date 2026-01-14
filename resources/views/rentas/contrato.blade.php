<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contrato de Renta {{ $renta->numero_contrato ?? $renta->id }}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <style>
    @page { margin: 0.45in; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #2d3748; line-height: 1.3; margin: 0; padding: 0; }

    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; padding: 6px 8px; background: #f8fafc; border: 1px solid {{ $configuracion['colores']['principal'] ?? '#2b6cb0' }}; border-radius: 4px; }
    .empresa-info { flex: 1; padding-right: 10px; }
    .empresa-logo { max-width: 120px; max-height: 60px; object-fit: contain; margin-bottom: 4px; }
    .empresa-nombre { font-size: 18px; font-weight: bold; color: {{ $configuracion['colores']['principal'] ?? '#2b6cb0' }}; margin-bottom: 2px; }
    .empresa-detalles { font-size: 8px; color: #4a5568; line-height: 1.3; }
    .doc-info { text-align: right; color: {{ $configuracion['colores']['secundario'] ?? '#1a365d' }}; min-width: 180px; }
    .doc-titulo { font-size: 20px; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 1px; }
    .doc-numero { font-size: 14px; margin-bottom: 4px; font-weight: 600; }

    .section { margin-bottom: 6px; page-break-inside: avoid; }
    .box { padding: 6px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; }
    .titulo { font-weight: bold; color: {{ $configuracion['colores']['principal'] ?? '#2b6cb0' }}; margin-bottom: 4px; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid {{ $configuracion['colores']['principal'] ?? '#2b6cb0' }}; padding-bottom: 1px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

    table { width: 100%; border-collapse: collapse; margin-top: 6px; background: #fff; border-radius: 4px; overflow: hidden; }
    thead { display: table-header-group; }
    tfoot { display: table-row-group; }
    th { background: {{ $configuracion['colores']['principal'] ?? '#2b6cb0' }}; color: #fff; font-weight: bold; font-size: 8px; text-transform: uppercase; letter-spacing: .5px; padding: 4px 3px; text-align: left; }
    td { padding: 3px 4px; border-bottom: 1px solid #e2e8f0; font-size: 8px; vertical-align: top; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    .muted { color: #718096; font-size: 8px; }
    .text-right { text-align: right; }
    .badge { display: inline-block; padding: 3px 6px; border-radius: 10px; background: #ebf8ff; color: #2b6cb0; font-size: 8px; border: 1px solid #bee3f8; }

    .firma { margin-top: 6px; display: grid; grid-template-columns: 1fr 1fr; gap: 6px; padding: 6px; background: #f8fafc; border: 1px solid {{ $configuracion['colores']['principal'] ?? '#2b6cb0' }}; border-radius: 4px; }
    .firma .linea { margin-top: 14px; border-top: 1px solid {{ $configuracion['colores']['principal'] ?? '#2b6cb0' }}; padding-top: 6px; text-align: center; font-size: 9px; color: #4a5568; }
  </style>
</head>
<body>
  <div class="header">
    <div class="empresa-info">
      @if($configuracion['logo_path'] ?? false)
        <img src="{{ $configuracion['logo_path'] }}" alt="Logo" class="empresa-logo">
      @endif
      <div class="empresa-nombre">{{ $configuracion['empresa']['nombre'] }}</div>
      <div class="empresa-detalles">
        RFC: {{ $configuracion['empresa']['rfc'] }}<br>
        {{ $configuracion['empresa']['direccion'] }}<br>
        Tel: {{ $configuracion['empresa']['telefono'] }} | Email: {{ $configuracion['empresa']['email'] }}
      </div>
    </div>
    <div class="doc-info">
      <div class="doc-titulo">CONTRATO DE RENTA</div>
      <div class="doc-numero">{{ $renta->numero_contrato ?? ('R-' . $renta->id) }}</div>
      <div><strong>Fecha de emisión:</strong> {{ \App\Services\EmpresaConfiguracionService::formatearFecha(now()) }}</div>
    </div>
  </div>

  <!-- Datos clave compactos -->
  <div class="section">
    <div class="grid-2">
      <div class="box">
        <div class="titulo">Datos del Cliente</div>
        <div>
          <strong style="color: {{ $configuracion['colores']['principal'] ?? '#2b6cb0' }};">{{ $renta->cliente->nombre_razon_social ?? 'Cliente' }}</strong><br>
          @if($renta->cliente->email ?? false) <strong>Email:</strong> {{ $renta->cliente->email }}<br> @endif
          @if($renta->cliente->telefono ?? false) <strong>Tel:</strong> {{ $renta->cliente->telefono }}<br> @endif
          @if($renta->cliente->rfc ?? false) <strong>RFC:</strong> {{ $renta->cliente->rfc }}<br> @endif
          @if($renta->cliente->direccion_completa ?? false) <strong>Dirección:</strong> {{ $renta->cliente->direccion_completa }} @endif
        </div>
      </div>
      <div class="box">
        <div class="titulo">Detalles del Contrato</div>
        <table>
          <tbody>
            <tr><td class="muted">Inicio:</td><td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearFecha($renta->fecha_inicio) }}</td></tr>
            <tr><td class="muted">Fin:</td><td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearFecha($renta->fecha_fin) }}</td></tr>
            <tr><td class="muted">Día de pago:</td><td class="text-right">{{ $renta->dia_pago }}</td></tr>
            <tr><td class="muted">Duración:</td><td class="text-right">{{ $renta->meses_duracion }} meses</td></tr>
            <tr><td class="muted">Mensualidad:</td><td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda($renta->monto_mensual) }}</td></tr>
            @if($renta->deposito_garantia)
              <tr><td class="muted">Depósito garantía:</td><td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda($renta->deposito_garantia) }}</td></tr>
            @endif
            @php
              $li = $renta->lugar_instalacion; $li_str='';
              if (is_string($li)) { $li_str=$li; }
              elseif (is_array($li)) { $li_str = implode(', ', array_filter([$li['calle']??null,$li['numero_exterior']??($li['numero']??null),$li['colonia']??null,$li['codigo_postal']??null,$li['municipio']??null,$li['estado']??null])); }
            @endphp
            @if(!empty($li_str))
              <tr><td class="muted">Lugar de instalación:</td><td class="text-right">{{ $li_str }}</td></tr>
            @endif
            @if($renta->forma_pago)
              <tr><td class="muted">Forma de pago:</td><td class="text-right">{{ ucfirst($renta->forma_pago) }}</td></tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Proveedor + Bancarios -->
  <div class="section">
    <div class="grid-2">
      <div class="box">
        <div class="titulo">Datos del Proveedor</div>
        <div>
          <strong>{{ $configuracion['empresa']['razon_social'] ?: $configuracion['empresa']['nombre'] }}</strong><br>
          <span class="muted">RFC:</span> {{ $configuracion['empresa']['rfc'] }}<br>
          {{ $configuracion['empresa']['direccion'] }}<br>
          <span class="muted">Tel:</span> {{ $configuracion['empresa']['telefono'] }} | <span class="muted">Email:</span> {{ $configuracion['empresa']['email'] }}<br>
          <span class="muted">Responsable designado:</span> {{ optional(auth()->user())->name ?? '________________' }}
        </div>
      </div>
      <div class="box">
        <div class="titulo">Datos Bancarios (Transferencia)</div>
        <table>
          <tbody>
            <tr><td class="muted">Banco:</td><td class="text-right">{{ $configuracion['empresa']['banco'] ?? '—' }}</td></tr>
            <tr><td class="muted">Sucursal:</td><td class="text-right">{{ $configuracion['empresa']['sucursal'] ?? '—' }}</td></tr>
            <tr><td class="muted">Cuenta:</td><td class="text-right" style="font-family: monospace;">{{ $configuracion['empresa']['cuenta'] ?? '—' }}</td></tr>
            <tr><td class="muted">CLABE:</td><td class="text-right" style="font-family: monospace;">{{ $configuracion['empresa']['clabe'] ?? '—' }}</td></tr>
            <tr><td class="muted">Titular:</td><td class="text-right">{{ $configuracion['empresa']['titular'] ?? '—' }}</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Equipos / Accesorios -->
  <div class="section">
    <div class="titulo">Equipos / Accesorios rentados</div>
    <table>
      <thead><tr>
        <th style="width:6%">#</th>
        <th style="width:18%">Código</th>
        <th style="width:24%">Descripción</th>
        <th style="width:20%">Detalle</th>
        <th style="width:16%">Número de Serie</th>
        <th style="width:16%" class="text-right">Precio Mensual</th>
      </tr></thead>
      <tbody>
        @foreach($renta->equipos as $equipo)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $equipo->codigo ?? '-' }}</td>
            <td>{{ $equipo->nombre ?? '-' }}</td>
            <td>
              @if($equipo->marca || $equipo->modelo)
                <span class="badge">{{ trim(($equipo->marca ?? '') . ' ' . ($equipo->modelo ?? '')) }}</span>
              @endif
              @if(is_array($equipo->accesorios) && count($equipo->accesorios))
                <div class="muted">Accesorios: {{ implode(', ', $equipo->accesorios) }}</div>
              @endif
            </td>
            <td>{{ $equipo->numero_serie ?? '-' }}</td>
            <td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda($equipo->pivot->precio_mensual ?? $equipo->precio_renta_mensual ?? 0) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- Declaraciones (completo) -->
  <div class="section">
    <div class="titulo">Declaraciones</div>
    <div class="box">
      <p style="margin:0 0 6px 0;">
        <strong>CONTRATO DE RENTA DE PUNTO DE VENTA</strong> que celebran por una parte
        <strong>{{ $configuracion['empresa']['razon_social'] ?: $configuracion['empresa']['nombre'] }}</strong>,
        a quien en lo sucesivo se le denominará “<strong>EL PROVEEDOR</strong>”, con domicilio en
        <strong>{{ $configuracion['empresa']['direccion'] }}</strong>, RFC: <strong>{{ $configuracion['empresa']['rfc'] }}</strong>,
        representado para efectos de este contrato por su trabajador debidamente designado, y por la otra parte,
        <strong>{{ $renta->cliente->nombre_razon_social ?? 'EL CLIENTE' }}</strong>, quien actúa a nombre propio como persona
        física y a quien en lo sucesivo se le denominará “<strong>EL CLIENTE</strong>”, con domicilio en
        <strong>{{ $renta->cliente->direccion_completa ?? '________________' }}</strong>.
      </p>
      <p class="muted" style="margin:0 0 6px 0;">Ambas partes se reconocen capaces legalmente para contratar y obligarse, y se sujetan al tenor de las siguientes declaraciones:</p>
      <p style="margin:0 0 4px 0;"><strong>I. Declara “EL CLIENTE”:</strong></p>
      <ol style="margin:0 0 6px 14px; padding:0;">
        <li>a) Que es persona física con capacidad legal para obligarse.</li>
        <li>b) Que tiene su domicilio comercial en {{ $renta->cliente->direccion_completa ?? '________________' }}.</li>
        <li>c) Que requiere la renta de {{ $renta->equipos->count() }} equipo(s) de punto de venta para su negocio.</li>
      </ol>
      <p style="margin:0 0 4px 0;"><strong>II. Declara “EL PROVEEDOR”:</strong></p>
      <ol style="margin:0 0 6px 14px; padding:0;">
        <li>a) Que es persona física con capacidad legal para obligarse.</li>
        <li>b) Que cuenta con la experiencia y los medios para otorgar en renta equipos de punto de venta y brindar los servicios asociados de instalación y asesoría.</li>
        <li>c) Que ha designado a un trabajador de su confianza para celebrar y firmar este contrato en su representación, quien tendrá facultades para recibir pagos en efectivo y cumplir con las obligaciones derivadas del presente contrato: <strong>{{ optional(auth()->user())->name ?? '________________' }}</strong>.</li>
      </ol>
      <p style="margin:0;"><strong>III. Declaran ambas partes:</strong> Que existe la voluntad de celebrar el presente contrato de renta de punto de venta, sujetándose a las siguientes cláusulas.</p>
    </div>
  </div>

  <!-- Cláusulas (completo) -->
  <div class="section">
    <div class="titulo">Cláusulas</div>
    <div class="box">
      <p style="margin:0 0 4px 0;"><strong>PRIMERA. OBJETO.</strong> “EL PROVEEDOR” da en renta a “EL CLIENTE” equipo(s) de punto de venta (en lo sucesivo, “EL EQUIPO”), incluyendo instalación del sistema y asesoría para su uso.</p>
      <p style="margin:0 0 4px 0;"><strong>SEGUNDA. OBLIGACIONES DEL PROVEEDOR.</strong> “EL PROVEEDOR” se obliga a: (a) Entregar a “EL CLIENTE” el equipo en condiciones de funcionamiento; (b) Realizar la instalación y puesta en marcha del sistema de punto de venta; (c) Brindar asesoría básica para la operación del mismo.</p>
      <p style="margin:0 0 4px 0;"><strong>TERCERA. OBLIGACIONES DEL CLIENTE.</strong> “EL CLIENTE” se obliga a: (a) Pagar puntualmente la renta en los términos establecidos en este contrato; (b) Cuidar, conservar y utilizar el equipo de manera adecuada; (c) Responder por cualquier daño, extravío o robo del equipo, obligándose a cubrir el costo de reparación o reposición, salvo el desgaste natural por uso normal.</p>
      <p style="margin:0 0 4px 0;"><strong>CUARTA. PAGO.</strong> “EL CLIENTE” pagará a “EL PROVEEDOR” la cantidad de <strong>{{ \App\Services\EmpresaConfiguracionService::formatearMoneda($renta->monto_mensual) }}</strong> por concepto de renta mensual del(los) equipo(s). El pago será por adelantado, dentro de los primeros <strong>{{ $renta->dia_pago ? min(28,(int)$renta->dia_pago) : 5 }}</strong> días naturales de cada mes. Asimismo, “EL CLIENTE” entregará a “EL PROVEEDOR”, al momento de la firma del presente contrato, un depósito en garantía por la cantidad de <strong>{{ $renta->deposito_garantia ? \App\Services\EmpresaConfiguracionService::formatearMoneda($renta->deposito_garantia) : 'N/A' }}</strong>, el cual será devuelto al término del contrato, siempre y cuando no existan adeudos ni daños al equipo.</p>
      <p style="margin:0 0 4px 0;"><strong>QUINTA. FORMA DE PAGO.</strong> El pago podrá realizarse mediante transferencia electrónica a la cuenta señalada por “EL PROVEEDOR” (Banco: {{ $configuracion['empresa']['banco'] ?? '—' }}, Sucursal: {{ $configuracion['empresa']['sucursal'] ?? '—' }}, Cuenta: {{ $configuracion['empresa']['cuenta'] ?? '—' }}, CLABE: {{ $configuracion['empresa']['clabe'] ?? '—' }}, Titular: {{ $configuracion['empresa']['titular'] ?? ($configuracion['empresa']['razon_social'] ?: $configuracion['empresa']['nombre']) }}), o en efectivo al trabajador designado por “EL PROVEEDOR”, previa confirmación entre las partes.</p>
      <p style="margin:0 0 4px 0;"><strong>SEXTA. VIGENCIA.</strong> El presente contrato tendrá vigencia indefinida, con un período mínimo forzoso de <strong>{{ $renta->meses_duracion ?? 6 }}</strong> meses. Concluido dicho período, el contrato podrá darse por terminado mediante aviso por escrito de cualquiera de las partes con al menos 30 (treinta) días naturales de anticipación.</p>
      <p style="margin:0 0 4px 0;"><strong>SÉPTIMA. TERMINACIÓN ANTICIPADA.</strong> El contrato podrá darse por terminado de manera anticipada en los siguientes casos: (a) Por incumplimiento de cualquiera de las obligaciones establecidas en el presente contrato; (b) Por mutuo acuerdo entre las partes.</p>
      <p style="margin:0 0 4px 0;"><strong>OCTAVA. NO CESIÓN.</strong> Ninguna de las partes podrá ceder los derechos y obligaciones derivados del presente contrato a un tercero sin el consentimiento previo y por escrito de la otra parte.</p>
      <p style="margin:0 0 4px 0;"><strong>NOVENA. INVENTARIO.</strong> Se anexa al presente contrato el inventario del equipo entregado a “EL CLIENTE” (<em>Anexo 1: Inventario de Equipo Entregado</em>). Dicho inventario forma parte integral del contrato y deberá devolverse en su totalidad al término de la relación contractual.</p>
      <p style="margin:0 0 4px 0;"><strong>DÉCIMA. RECONOCIMIENTO DE PERSONALIDAD.</strong> Las partes reconocen plenamente la personalidad y derechos de la contraparte y, en el caso de “EL PROVEEDOR”, de su trabajador designado para la firma del presente contrato: <strong>{{ optional(auth()->user())->name ?? '________________' }}</strong>.</p>
      <p style="margin:0;"><strong>DÉCIMA PRIMERA. JURISDICCIÓN.</strong> Para la interpretación y cumplimiento de este contrato, así como para todo lo no previsto en el mismo, las partes se someten a la jurisdicción de los tribunales competentes de <strong>Hermosillo, Sonora</strong>, renunciando a cualquier fuero que por razón de su domicilio presente o futuro pudiera corresponderles.</p>
    </div>
  </div>

  <!-- Cronograma de pagos -->
  <div class="section">
    <div class="titulo">Cronograma de Pagos</div>
    @php
      $diaPago = (int) ($renta->dia_pago ?? 1);
      $inicio = \Carbon\Carbon::parse($renta->fecha_inicio);
      $primerVenc = $inicio->copy()->day(min($diaPago, $inicio->daysInMonth));
      if ($primerVenc->lt($inicio)) { $primerVenc = $inicio->copy()->addMonthNoOverflow()->day(min($diaPago, $inicio->copy()->addMonthNoOverflow()->daysInMonth)); }
      $fechas = []; $fechaProg = $primerVenc->copy(); $meses = (int) ($renta->meses_duracion ?? 0);
      for ($i=0;$i<$meses;$i++){ $fechas[] = $fechaProg->copy()->day(min($diaPago, $fechaProg->daysInMonth)); $fechaProg = $fechaProg->copy()->addMonthNoOverflow(); }
    @endphp
    <table>
      <thead><tr><th style="width:10%">#</th><th style="width:55%">Fecha de vencimiento</th><th style="width:35%" class="text-right">Monto</th></tr></thead>
      <tbody>
        @forelse($fechas as $idx=>$f)
          <tr><td>{{ $idx+1 }}</td><td>{{ \App\Services\EmpresaConfiguracionService::formatearFecha($f) }}</td><td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda($renta->monto_mensual) }}</td></tr>
        @empty
          <tr><td colspan="3" class="text-center muted">Sin programar.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="muted">Pagos por adelantado, conforme al día {{ $diaPago }} de cada mes.</div>
  </div>

  <!-- Domicilio convencional -->
  <div class="section">
    <div class="titulo">Domicilio Convencional (Notificaciones)</div>
    <div class="grid-2">
      <div class="box">
        <div class="titulo">Proveedor</div>
        <div class="muted">{{ $configuracion['empresa']['razon_social'] ?: $configuracion['empresa']['nombre'] }}</div>
        <div>{{ $configuracion['empresa']['direccion'] }}</div>
        <div class="muted">RFC: {{ $configuracion['empresa']['rfc'] }}</div>
      </div>
      <div class="box">
        <div class="titulo">Cliente</div>
        <div class="muted">{{ $renta->cliente->nombre_razon_social ?? 'Cliente' }}</div>
        <div>{{ $renta->cliente->direccion_completa ?? '' }}</div>
        @if($renta->cliente->rfc ?? false)
          <div class="muted">RFC: {{ $renta->cliente->rfc }}</div>
        @endif
      </div>
    </div>
  </div>

  <!-- Firmas -->
  <div class="firma">
    <div class="linea">EL PROVEEDOR (por trabajador designado)<br><strong>{{ optional(auth()->user())->name ?? '________________' }}</strong></div>
    <div class="linea">EL CLIENTE<br><strong>{{ $renta->cliente->nombre_razon_social ?? 'Cliente' }}</strong></div>
  </div>
</body>
</html>
