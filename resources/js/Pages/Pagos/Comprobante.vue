<!-- /resources/js/Pages/Pagos/Comprobante.vue -->
<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  historial: { type: Object, required: true },
  pago: { type: Object, required: true },
  prestamo: { type: Object, required: true },
  cliente: { type: Object, required: true },
  empresa: { type: Object, required: true },
  fecha_actual: { type: String, required: true },
  monto_letras: { type: String, required: true },
  /** Enlace firmado (temporal) para descargar el mismo PDF desde el servidor */
  url_descarga_pdf: { type: String, default: '' },
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const formatearMoneda = (num) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(num)
}

const formatearFecha = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  })
}

const labelMetodoPago = (raw) => {
  const m = (raw || 'otro').replace(/_/g, ' ')
  return m.charAt(0).toUpperCase() + m.slice(1)
}

/** Solo dígitos; si son 10 dígitos MX se antepone 52 (celular). */
const telefonoWhatsAppDigits = (tel) => {
  if (!tel) return ''
  const d = String(tel).replace(/\D/g, '')
  if (d.length === 10) return `52${d}`
  if (d.length === 12 && d.startsWith('52')) return d
  if (d.length === 13 && d.startsWith('521')) return d
  if (d.length >= 10 && d.length <= 15) return d
  return ''
}

const mensajeWhatsApp = computed(() => {
  const folio = `REC-${String(props.historial.id).padStart(6, '0')}`
  const empresaNombre = props.empresa?.razon_social || 'Empresa'
  const lines = [
    '*Comprobante de abono a préstamo*',
    '',
    `Folio: ${folio}`,
    `Cliente: ${props.cliente?.nombre_razon_social || '—'}`,
    `Monto: ${formatearMoneda(props.historial.monto_pagado)}`,
    `Fecha: ${formatearFecha(props.historial.fecha_pago)}`,
    `Método: ${labelMetodoPago(props.historial.metodo_pago)}`,
    `Préstamo: #${props.prestamo.folio || props.prestamo.id} · Cuota ${props.pago.numero_pago}`,
    '',
    `_${empresaNombre}_`,
  ]
  if (props.url_descarga_pdf) {
    lines.push('', '📄 *Descarga el PDF del comprobante aquí:*', props.url_descarga_pdf)
  }
  return lines.join('\n')
})

/** WhatsApp Web con mensaje + enlace de descarga firmado. */
const urlWhatsAppWeb = computed(() => {
  const text = encodeURIComponent(mensajeWhatsApp.value)
  const phone = telefonoWhatsAppDigits(props.cliente?.telefono)
  if (phone) {
    return `https://web.whatsapp.com/send?phone=${phone}&text=${text}`
  }
  return `https://web.whatsapp.com/send?text=${text}`
})

const generarPDF = () => {
  const contenido = generarContenido()
  const win = window.open('', '_blank')
  win.document.write(contenido)
  win.document.close()
  win.onload = () => {
    setTimeout(() => {
        win.focus()
        win.print()
    }, 300)
  }
}

const generarContenido = () => {
  const folio = `REC-${String(props.historial.id).padStart(6, '0')}`
  const empresaNombre = props.empresa?.razon_social || 'CLIMAS DEL DESIERTO'
  const empresaRFC = props.empresa?.rfc || ''
  const empresaDireccion = props.empresa?.direccion_completa || ''
  
  return `
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de Pago ${folio}</title>
    <style>
        @page { size: letter; margin: 10mm 12mm; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; line-height: 1.35; margin: 0; padding: 0; background: #fff; font-size: 12px; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; position: relative; }
        .header { background: #0f172a; color: #fff; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; }
        .header .folio { text-align: right; }
        .header .folio-num { font-size: 16px; font-weight: 800; color: #38bdf8; }
        
        .section { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; }
        .section-title { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; display: flex; align-items: center; }
        .section-title::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; margin-left: 10px; }
        
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 16px; }
        .data-label { font-size: 9px; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 2px; }
        .data-value { font-size: 13px; font-weight: 600; color: #1e293b; }
        
        .amount-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; text-align: center; margin-top: 10px; }
        .amount-val { font-size: 26px; font-weight: 900; color: #0f172a; margin-bottom: 4px; }
        .amount-text { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; line-height: 1.25; }
        
        .footer { padding: 10px 14px 12px; text-align: center; font-size: 8px; color: #94a3b8; line-height: 1.35; }
        .signature-wrap { margin-top: 10px; padding: 0 16px 8px; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .signature-wrap img { display: block; margin: 0 auto 0; max-height: 72px; max-width: 240px; object-fit: contain; }
        .sig-line { width: 240px; border-top: 1px solid #94a3b8; margin: 0 auto; padding-top: 6px; font-size: 9px; font-weight: 700; color: #475569; text-transform: uppercase; }
        
        .stamp { position: absolute; top: 88px; right: 16px; border: 3px double #34d399; color: #34d399; font-size: 14px; font-weight: 900; padding: 6px 12px; transform: rotate(-12deg); border-radius: 6px; opacity: 0.55; }

        @media print {
            .no-print { display: none; }
            body { background: none; }
            .container { border: none; }
            .stamp { opacity: 0.45; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Comprobante de Abono</h1>
                <div style="font-size: 10px; opacity: 0.8; margin-top: 5px;">${empresaNombre} | RFC: ${empresaRFC}</div>
            </div>
            <div class="folio">
                <div style="font-size: 10px; opacity: 0.8;">FOLIO DE RECEPCIÓN</div>
                <div class="folio-num">${folio}</div>
            </div>
        </div>

        <div class="stamp">PAGADO</div>

        <div class="section">
            <div class="section-title">Datos del Cliente</div>
            <div class="grid">
                <div>
                    <div class="data-label">Cliente / Deudor</div>
                    <div class="data-value">${props.cliente?.nombre_razon_social}</div>
                </div>
                <div>
                    <div class="data-label">RFC / Identificación</div>
                    <div class="data-value">${props.cliente?.rfc || 'XAXX010101000'}</div>
                </div>
            </div>
            <div style="margin-top: 8px;">
                <div class="data-label">Domicilio</div>
                <div class="data-value" style="font-size: 12px;">${props.cliente?.direccion_completa || 'Sin domicilio registrado'}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Detalle del Movimiento</div>
            <div class="grid">
                <div>
                    <div class="data-label">Préstamo Relacionado</div>
                    <div class="data-value">Folio: ${props.prestamo.folio || props.prestamo.id}</div>
                </div>
                <div>
                    <div class="data-label">Número de Cuota</div>
                    <div class="data-value">Abono a la cuota #${props.pago.numero_pago} de ${props.prestamo.numero_pagos}</div>
                </div>
                <div>
                    <div class="data-label">Fecha de Operación</div>
                    <div class="data-value">${formatearFecha(props.historial.fecha_pago)}</div>
                </div>
                <div>
                    <div class="data-label">Método de Pago</div>
                    <div class="data-value" style="text-transform: capitalize;">${(props.historial.metodo_pago || 'otro').replace('_', ' ')}</div>
                </div>
            </div>
            
            <div class="amount-box">
                <div class="amount-val">${formatearMoneda(props.historial.monto_pagado)}</div>
                <div class="amount-text">${props.monto_letras}</div>
            </div>
        </div>

        <div class="section" style="border-bottom: none;">
            <div class="section-title">Estado del Crédito</div>
            <div class="grid" style="grid-template-columns: 1fr 1fr 1fr;">
                <div style="text-align: center;">
                    <div class="data-label">Amortizado</div>
                    <div class="data-value" style="color: #059669;">${formatearMoneda(props.prestamo.monto_pagado)}</div>
                </div>
                <div style="text-align: center;">
                    <div class="data-label">Saldo Pendiente</div>
                    <div class="data-value" style="color: #dc2626;">${formatearMoneda(props.prestamo.monto_pendiente)}</div>
                </div>
                <div style="text-align: center;">
                    <div class="data-label">Progreso</div>
                    <div class="data-value">${props.prestamo.progreso}%</div>
                </div>
            </div>
        </div>

        <div class="signature-wrap">
            ${props.empresa?.firma_digital
              ? `<img src="${props.empresa.firma_digital}" alt="Firma" />
                 <div class="sig-line">Firma autorizada — ${(props.empresa?.razon_social || props.empresa?.nombre_empresa || 'Empresa').replace(/</g, '')}</div>`
              : `<div class="sig-line">Sello y firma del cajero</div>`
            }
        </div>

        <div class="footer">
            Este comprobante es un acuse de recibo de fondos y no constituye una liberación total del crédito hasta su liquidación absoluta.<br>
            ${empresaDireccion} | Tel: ${props.empresa?.telefono || ''} | Generado: ${new Date().toLocaleString()}
        </div>
    </div>
</body>
</html>
  `
}
</script>

<template>
  <Head title="Comprobante de Pago" />

  <div class="min-h-screen bg-[var(--ui-surface)] py-12 px-4 selection:bg-brand-500/30">
    <div class="max-w-4xl mx-auto">
        <div class="bg-slate-900 border border-white/10 rounded-3xl p-8 shadow-2xl overflow-hidden relative group">
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl transition-all group-hover:bg-blue-600/20"></div>
            
            <div class="relative flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12">
                <div>
                     <div class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wide bg-brand-500/10 text-emerald-400 border border-emerald-500/20 mb-4">
                        Transacción Confirmada
                    </div>
                    <h1 class="text-4xl font-black text-white tracking-tighter mb-2">Comprobante de Abono</h1>
                    <p class="text-slate-400 font-medium">Visualización y descarga oficial del recibo de pago.</p>
                </div>
                
                <div class="flex flex-col items-stretch sm:items-end gap-2">
                <div class="flex flex-wrap items-center gap-3 justify-end">
                    <a
                      :href="urlWhatsAppWeb"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#25D366] hover:bg-[#20bd5a] text-white text-xs font-black uppercase tracking-wide rounded-2xl shadow-xl shadow-emerald-900/30 transition-all active:scale-95"
                      title="Abre WhatsApp Web con el resumen y el enlace para descargar el PDF"
                    >
                      <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.123 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                      </svg>
                      WhatsApp
                    </a>
                    <a
                      v-if="url_descarga_pdf"
                      :href="url_descarga_pdf"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center gap-2 px-6 py-4 bg-slate-700 hover:bg-slate-600 text-white text-xs font-black uppercase tracking-wide rounded-2xl border border-white/10"
                    >
                      Descargar PDF
                    </a>
                    <button type="button" class="px-8 py-4 bg-blue-600 hover:bg-slate-500 text-white text-xs font-black uppercase tracking-wide rounded-2xl shadow-xl shadow-blue-600/20 transition-all active:scale-95" @click="generarPDF">
                        Imprimir Recibo
                    </button>
                    <Link :href="`/pagos/${pago.id}`" class="px-6 py-4 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-black uppercase tracking-wide rounded-2xl transition-all">
                        Volver
                    </Link>
                </div>
                <p v-if="url_descarga_pdf" class="text-[10px] text-slate-500 max-w-xl sm:text-right leading-relaxed">
                  El mensaje de WhatsApp incluye un enlace seguro (válido varios días) para que descarguen el mismo PDF desde el navegador, sin entrar al sistema.
                </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div class="bg-slate-950/50 rounded-2xl p-6 border border-white/5">
                    <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-wide mb-4">Resumen de Pago</h3>
                    <div class="space-y-6">
                        <div class="flex justify-between">
                            <span class="text-xs text-slate-400">Monto Recibido</span>
                            <span class="text-xl font-black text-emerald-400 tracking-tighter">{{ formatearMoneda(historial.monto_pagado) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-slate-400">Fecha</span>
                            <span class="text-sm font-bold text-white">{{ formatearFecha(historial.fecha_pago) }}</span>
                        </div>
                         <div class="flex justify-between">
                            <span class="text-xs text-slate-400">Método</span>
                            <span class="text-sm font-bold text-white capitalize">{{ (historial.metodo_pago || 'otro').replace('_', ' ') }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-950/50 rounded-2xl p-6 border border-white/5">
                    <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-wide mb-4">Referencia del Crédito</h3>
                    <div class="space-y-6">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-400">Cliente</span>
                            <span class="text-white font-bold">{{ cliente.nombre_razon_social }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-400">Préstamo</span>
                            <span class="text-white font-bold">#{{ prestamo.folio || prestamo.id }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-400">Cuota</span>
                            <span class="text-white font-bold">Abono a Cuota #{{ pago.numero_pago }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-600/5 border border-blue-500/10 rounded-2xl p-4 flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center text-blue-400">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <div class="text-[10px] font-black text-blue-400 uppercase tracking-wide">Saldo Actualizado</div>
                    <div class="text-xs text-slate-400 font-medium">El saldo pendiente actual es de <span class="text-white font-black">{{ formatearMoneda(prestamo.monto_pendiente) }}</span>.</div>
                </div>
            </div>

            <div v-if="empresa?.firma_digital" class="mt-8 pt-8 border-t border-white/10 flex flex-col items-center text-center">
                <img :src="empresa.firma_digital" alt="Firma de la empresa" class="max-h-28 max-w-xs object-contain mb-1" />
                <div class="w-56 border-t border-white/25 pt-2 mt-1">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wide leading-snug">
                        Firma autorizada — {{ empresa.razon_social || empresa.nombre_empresa }}
                    </p>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>
