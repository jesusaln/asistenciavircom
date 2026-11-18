<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Propuesta de Póliza TI — Asistencia Vircom</title>
  <style>
    body { margin: 0; padding: 0; background-color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; }
    table { border-collapse: collapse; }
    .wrapper { width: 100%; table-layout: fixed; background-color: #0f172a; padding: 30px 0; }
    .main-table { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 640px; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
    .header { background: linear-gradient(135deg, #0284c7 0%, #0369a1 50%, #0f172a 100%); padding: 36px 30px; text-align: center; color: #ffffff; }
    .badge { display: inline-block; background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 50px; padding: 6px 16px; font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: #fde047; margin-bottom: 12px; }
    .title { font-size: 24px; font-weight: 900; margin: 0 0 8px 0; color: #ffffff; letter-spacing: -0.5px; }
    .subtitle { font-size: 13px; color: #e0f2fe; margin: 0; line-height: 1.5; font-weight: 500; }
    .content-body { padding: 32px 30px; }
    .greeting { font-size: 16px; color: #0f172a; font-weight: 700; margin-bottom: 12px; }
    .intro-text { font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 24px; }
    .asset-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 24px; }
    .asset-title { font-size: 12px; font-weight: 900; color: #0369a1; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 14px; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; }
    .item-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
    .item-label { font-weight: 600; color: #1e293b; }
    .item-value { font-weight: 700; color: #0284c7; }
    .pricing-card { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #22c55e; border-radius: 16px; padding: 24px; text-align: center; margin-bottom: 26px; }
    .pricing-label { font-size: 12px; font-weight: 800; color: #166534; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px; }
    .pricing-main { font-size: 34px; font-weight: 900; color: #15803d; margin: 0; }
    .pricing-period { font-size: 14px; color: #166534; font-weight: 600; }
    .annual-box { margin-top: 12px; padding-top: 12px; border-top: 1px dashed #86efac; font-size: 13px; color: #166534; }
    .annual-price { font-weight: 800; color: #15803d; font-size: 15px; }
    .benefits-box { background: #f8fafc; border-radius: 14px; padding: 20px; margin-bottom: 24px; }
    .benefit-item { font-size: 13px; color: #334155; line-height: 1.6; margin-bottom: 8px; }
    .cta-btn { display: inline-block; background: #0284c7; color: #ffffff !important; text-decoration: none; font-weight: 800; font-size: 15px; padding: 14px 32px; border-radius: 12px; box-shadow: 0 8px 16px rgba(2, 132, 199, 0.35); text-transform: uppercase; letter-spacing: 0.5px; }
    .footer { background: #0f172a; padding: 24px 30px; text-align: center; color: #94a3b8; font-size: 12px; line-height: 1.6; }
    .footer strong { color: #f8fafc; }
  </style>
</head>
<body>
  <div class="wrapper">
    <table class="main-table" align="center" cellpadding="0" cellspacing="0">
      <!-- HEADER -->
      <tr>
        <td class="header">
          <div class="badge">Propuesta Personalizada TI</div>
          <h1 class="title">Póliza de Soporte y Mantenimiento TI</h1>
          <p class="subtitle">Continuidad Operativa, Seguridad y Respaldo Continuo en Hermosillo, Sonora</p>
        </td>
      </tr>

      <!-- BODY -->
      <tr>
        <td class="content-body">
          <div class="greeting">Estimado(a) {{ $data['cliente_nombre'] ?? 'Cliente' }},</div>
          <p class="intro-text">
            Con base en el diagnóstico y la infraestructura técnica conversada, hemos diseñado la siguiente <strong>propuesta de póliza integral</strong> para respaldar y blindar la operación de su empresa:
          </p>

          <!-- DESGLOSE DE EQUIPOS -->
          <div class="asset-card">
            <div class="asset-title">📋 Infraestructura Incluida en la Póliza</div>
            <table width="100%" cellpadding="6" cellspacing="0">
              @if(!empty($data['computadoras']) && $data['computadoras'] > 0)
              <tr>
                <td class="item-label">💻 Computadoras / Laptops (PC/Mac):</td>
                <td align="right" class="item-value">
                  {{ $data['computadoras'] }} equipos 
                  @if(!empty($data['precio_unitario_pc']))
                    <span style="font-size: 11px; color: #64748b; font-weight: normal;">(${{ number_format($data['precio_unitario_pc'], 2) }} c/u)</span>
                  @endif
                </td>
              </tr>
              @endif
              @if(!empty($data['servidores_cantidad']) && $data['servidores_cantidad'] > 0)
              <tr>
                <td class="item-label">🖥️ Servidores Dedicados / SQL / AD:</td>
                <td align="right" class="item-value">{{ $data['servidores_cantidad'] }} servidor(es) (Monitoreo + Backup)</td>
              </tr>
              @elseif(!empty($data['tiene_servidor']))
              <tr>
                <td class="item-label">🖥️ Servidor Local / BD SQL:</td>
                <td align="right" class="item-value">1 servidor (Monitoreo + Backup)</td>
              </tr>
              @endif
              @if(!empty($data['tipo_red']) && $data['tipo_red'] !== 'ninguna')
              <tr>
                <td class="item-label">🌐 Infraestructura de Redes y Conectividad:</td>
                <td align="right" class="item-value">{{ $data['tipo_red'] === 'empresarial' ? 'Red Empresarial (PoE + APs + VLANs)' : 'Red Básica PyME (Módem + Switch)' }}</td>
              </tr>
              @endif
              @if(!empty($data['tiene_contpaqi']))
              <tr>
                <td class="item-label">📊 Sistemas ContPAQi / Facturación / ERP:</td>
                <td align="right" class="item-value">Soporte Especializado y Bases SQL</td>
              </tr>
              @endif
              @if(!empty($data['camaras']) && $data['camaras'] > 0)
              <tr>
                <td class="item-label">📹 Cámaras de Seguridad CCTV:</td>
                <td align="right" class="item-value">{{ $data['camaras'] }} cámaras + Grabador DVR/NVR</td>
              </tr>
              @endif
              @if(!empty($data['tiene_alarma']))
              <tr>
                <td class="item-label">🚨 Sistema de Alarma:</td>
                <td align="right" class="item-value">Mantenimiento de Sensores, Sirena y Batería</td>
              </tr>
              @endif
              @if(!empty($data['relojes_checadores']) && $data['relojes_checadores'] > 0)
              <tr>
                <td class="item-label">⏰ Relojes Checadores Biométricos:</td>
                <td align="right" class="item-value">{{ $data['relojes_checadores'] }} equipos</td>
              </tr>
              @endif
              @if(!empty($data['controles_acceso']) && $data['controles_acceso'] > 0)
              <tr>
                <td class="item-label">🚪 Controles de Acceso (Chapas/Torniquetes):</td>
                <td align="right" class="item-value">{{ $data['controles_acceso'] }} puntos de acceso</td>
              </tr>
              @endif
              <tr>
                <td class="item-label">🚗 Visitas Presenciales en Sitio:</td>
                <td align="right" class="item-value">{{ $data['visitas_mensuales'] ?? 1 }} visita(s)/mes incluidas (Hermosillo)</td>
              </tr>
              <tr>
                <td class="item-label">⏱️ Tiempo de Respuesta Garantizado (SLA):</td>
                <td align="right" class="item-value">{{ $data['sla_tiempo'] ?? 'Menos de 45 minutos' }}</td>
              </tr>
              @if(!empty($data['es_paquete_integral']))
              <tr>
                <td class="item-label" style="color: #15803d; font-weight: 800;">⭐ Paquete Solución Integral 360°:</td>
                <td align="right" class="item-value" style="color: #15803d;">Bonificación 10% Aplicada</td>
              </tr>
              @endif
              @if(!empty($data['descuento_monto']) && $data['descuento_monto'] > 0)
              <tr>
                <td class="item-label" style="color: #0284c7; font-weight: 800;">🏷️ Descuento Especial Comercial:</td>
                <td align="right" class="item-value" style="color: #0284c7;">-${{ number_format($data['descuento_monto'], 2) }} MXN</td>
              </tr>
              @endif
            </table>
          </div>

          <!-- TARJETA DE PRECIO -->
          <div class="pricing-card">
            <div class="pricing-label">Inversión Mensual de la Póliza</div>
            <div class="pricing-main">${{ number_format($data['total_mensual'] ?? 0, 2) }} <span class="pricing-period">MXN + IVA</span></div>
            <div style="font-size: 12px; color: #15803d; margin-top: 4px;">Facturación 100% deducible de impuestos (Gasto operativo)</div>
            
            <div class="annual-box">
              ⭐ <strong>Opción Pago Anual (10% de Ahorro):</strong><br>
              <span class="annual-price">${{ number_format($data['total_anual'] ?? 0, 2) }} MXN + IVA</span> / año
            </div>
          </div>

          <!-- BENEFICIOS CLAVE -->
          <div class="benefits-box">
            <div style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 10px; text-transform: uppercase;">
              🛡️ Beneficios Incluidos con Asistencia Vircom:
            </div>
            <div class="benefit-item">✅ <strong>Mesa de Ayuda y Soporte Remoto Ilimitado</strong> de Lunes a Sábado.</div>
            <div class="benefit-item">✅ <strong>Mantenimiento Preventivo Periódico</strong> (limpieza de polvo, pasta térmica y optimización).</div>
            <div class="benefit-item">✅ <strong>Respaldo Automatizado en la Nube</strong> de archivos contables y bases de datos.</div>
            <div class="benefit-item">✅ <strong>Antivirus y Protección de Endpoints</strong> administrado centralmente.</div>
            <div class="benefit-item">✅ <strong>Préstamo de equipo de respaldo temporal</strong> sin costo en caso de falla mayor.</div>
          </div>

          <!-- BOTÓN DE CONFIRMACIÓN -->
          <div style="text-align: center; margin: 32px 0 16px 0;">
            <a href="https://wa.me/526622036840?text={{ urlencode('Hola Asistencia Vircom, recibí la propuesta de póliza por correo para ' . ($data['cliente_nombre'] ?? 'mi empresa') . ' y deseo confirmarla/agendar inicio.') }}" class="cta-btn" target="_blank">
              Aceptar Propuesta / Agendar Inicio
            </a>
          </div>

          @if(!empty($data['notas']))
          <div style="font-size: 12px; color: #64748b; background: #f1f5f9; padding: 12px 16px; border-radius: 8px; margin-top: 20px;">
            <strong>Notas y Observaciones:</strong> {{ $data['notas'] }}
          </div>
          @endif
        </td>
      </tr>

      <!-- FOOTER -->
      <tr>
        <td class="footer">
          <strong>Asistencia Vircom — Soluciones de Tecnología y Soporte TI</strong><br>
          Hermosillo, Sonora, México<br>
          📞 Tel: (662) 203-6840 | 📲 WhatsApp: (662) 315-0789<br>
          🌐 <a href="https://asistenciavircom.com" style="color: #38bdf8; text-decoration: none;">asistenciavircom.com</a>
        </td>
      </tr>
    </table>
  </div>
</body>
</html>
