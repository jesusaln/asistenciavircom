<html>
  <body style="font-family: Arial, sans-serif; color:#0f172a;">
    <h2>Alerta de mantenimiento</h2>
    <p>
      Vehículo: {{ optional($datos['carro'])->marca }} {{ optional($datos['carro'])->modelo }}<br>
      Tipo: {{ $datos['mantenimiento']->tipo }}<br>
      Prioridad: {{ $datos['mantenimiento']->prioridad }}<br>
      Próximo por fecha: {{ optional($datos['fecha_proximo'])->format('d/m/Y') }}<br>
      Días restantes: {{ $datos['dias_restantes'] ?? 'N/A' }}<br>
      Km restantes: {{ $datos['km_restantes'] ?? 'N/A' }}
    </p>
    <p>Tipo de alerta: <strong>{{ $datos['tipo_alerta'] }}</strong></p>
    <p>Por favor programe/realice el servicio a la brevedad.</p>
  </body>
</html>

