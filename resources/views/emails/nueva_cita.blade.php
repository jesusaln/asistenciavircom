<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Cita Agendada</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #FF6B35, #e85d26); padding: 30px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 800;">🔔 Nueva Cita Agendada</h1>
                            <p style="color: rgba(255,255,255,0.85); margin: 8px 0 0; font-size: 14px;">Un cliente acaba de solicitar una visita técnica</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <!-- Cliente Info -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 4px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8;">Datos del Cliente</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 12px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <strong style="color: #475569; font-size: 13px;">👤 Nombre:</strong>
                                                    <span style="color: #1e293b; font-size: 15px; font-weight: 700; margin-left: 8px;">{{ $datos['nombre'] }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <strong style="color: #475569; font-size: 13px;">📱 Teléfono:</strong>
                                                    <a href="tel:{{ $datos['telefono'] }}" style="color: #FF6B35; font-size: 15px; font-weight: 700; margin-left: 8px; text-decoration: none;">{{ $datos['telefono'] }}</a>
                                                </td>
                                            </tr>
                                            @if(!empty($datos['email']))
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <strong style="color: #475569; font-size: 13px;">✉️ Email:</strong>
                                                    <span style="color: #1e293b; font-size: 15px; margin-left: 8px;">{{ $datos['email'] }}</span>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Servicio Info -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fff7ed; border: 1px solid #fed7aa; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 4px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #c2410c;">Servicio Solicitado</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 12px;">
                                        <p style="margin: 0; color: #1e293b; font-size: 18px; font-weight: 800;">{{ $datos['servicio'] }}</p>
                                        @if(!empty($datos['fecha']))
                                        <p style="margin: 8px 0 0; color: #64748b; font-size: 14px;">
                                            📅 Fecha preferida: <strong>{{ $datos['fecha'] }}</strong>
                                            @if(!empty($datos['hora']))
                                             a las <strong>{{ $datos['hora'] }}</strong>
                                            @endif
                                        </p>
                                        @endif
                                        @if(!empty($datos['descripcion']))
                                        <p style="margin: 12px 0 0; color: #64748b; font-size: 14px;">
                                            💬 {{ $datos['descripcion'] }}
                                        </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 16px 0;">
                                        <a href="https://wa.me/52{{ $datos['telefono'] }}" target="_blank" style="display: inline-block; background-color: #25D366; color: #ffffff; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 14px; text-decoration: none;">
                                            💬 Llamar / WhatsApp al Cliente
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- IDs -->
                            @if(!empty($datos['prospecto_id']) || !empty($datos['cliente_id']) || !empty($datos['cita_id']))
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                                <tr>
                                    <td style="color: #94a3b8; font-size: 11px; text-align: center;">
                                        @if(!empty($datos['prospecto_id']))Prospecto #{{ $datos['prospecto_id'] }} · @endif
                                        @if(!empty($datos['cliente_id']))Cliente #{{ $datos['cliente_id'] }} · @endif
                                        @if(!empty($datos['cita_id']))Cita #{{ $datos['cita_id'] }}@endif
                                    </td>
                                </tr>
                            </table>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 24px 40px; text-align: center;">
                            <p style="color: #94a3b8; margin: 0; font-size: 12px;">
                                Este correo fue generado automáticamente por el sistema de <strong style="color: #ffffff;">Climas del Desierto</strong>.
                            </p>
                            <p style="color: #64748b; margin: 8px 0 0; font-size: 11px;">
                                {{ now()->format('d/m/Y H:i') }} hrs
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
