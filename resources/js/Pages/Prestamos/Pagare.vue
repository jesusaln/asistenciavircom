<!-- /resources/js/Pages/Prestamos/Pagare.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

/* =========================
    Props
========================= */
const props = defineProps({
  prestamo: { type: Object, required: true },
  cliente: { type: Object, required: true },
  empresa: { type: Object, required: true },
  fecha_actual: { type: String, required: true }, // ISO o legible es-MX
  monto_letras: { type: String, required: true },
  tasa_mensual: { type: Number, required: true },
  pago_mensual_letras: { type: String, required: true }
})

/* =========================
    Branding (Información empresarial desde props)
 ========================= */
const EMPRESA = computed(() => ({
  nombre: props.empresa?.nombre || 'EMPRESA NO ESPECIFICADA',
  nombreCorto: props.empresa?.nombre_comercial || 'EMP',
  rfc: props.empresa?.rfc || 'RFC NO ESPECIFICADO',
  domicilio: props.empresa?.direccion || 'Domicilio no especificado',
  lugarPago: props.empresa?.direccion || 'Lugar de pago no especificado',
  telefono: props.empresa?.telefono || 'Teléfono no especificado',
  email: props.empresa?.email || 'Email no especificado'
}))

/* =========================
   Información adicional del documento
========================= */
const DOCUMENTO_INFO = {
  titulo: 'PAGARÉ',
  subtitulo: 'Título Ejecutivo de Crédito',
  descripcion: 'Documento legal que constituye obligación financiera exigible por la vía ejecutiva mercantil.',
  version: '3.0',
  fechaActualizacion: '2024'
}

/* =========================
   Notificaciones
========================= */
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

/* =========================
   Utilidades de formato
========================= */
const formatearMoneda = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(safe)
}

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  try {
    const time = new Date(date).getTime()
    if (Number.isNaN(time)) return 'Fecha inválida'
    return new Date(time).toLocaleDateString('es-MX', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch {
    return 'Fecha inválida'
  }
}

const formatearFechaCompleta = (date) => {
  if (!date) return 'Fecha no disponible'
  try {
    const time = new Date(date).getTime()
    if (Number.isNaN(time)) return 'Fecha inválida'
    return new Date(time).toLocaleString('es-MX', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return 'Fecha inválida'
  }
}

// =============== Utilidades de fecha robustas ===============
// Acepta Date, ISO, YYYY-MM-DD y DD/MM/YYYY
const parseFechaFlexible = (input) => {
  if (!input) return null
  if (input instanceof Date) return isNaN(input.getTime()) ? null : input
  if (typeof input === 'string') {
    const s = input.trim()
    const m = s.match(/^([0-3]?\d)\/([0-1]?\d)\/(\d{4})$/)
    if (m) {
      const d = parseInt(m[1], 10)
      const mo = parseInt(m[2], 10) - 1
      const y = parseInt(m[3], 10)
      const dt = new Date(y, mo, d)
      if (dt && dt.getFullYear() === y && dt.getMonth() === mo && dt.getDate() === d) return dt
      return null
    }
    const t = Date.parse(s)
    if (!Number.isNaN(t)) return new Date(t)
  }
  return null
}

const formatearFechaFlex = (date) => {
  const dt = parseFechaFlexible(date)
  if (!dt) return 'Fecha inválida'
  try {
    return dt.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return 'Fecha inválida'
  }
}

const formatearFechaCompletaFlex = (date) => {
  const dt = parseFechaFlexible(date)
  if (!dt) return 'Fecha inválida'
  try {
    return dt.toLocaleString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  } catch {
    return 'Fecha inválida'
  }
}

/* =========================
   Funciones auxiliares
========================= */
const obtenerInicialesCliente = () => {
  if (!props.cliente?.nombre_razon_social) return 'CL'
  const palabras = props.cliente.nombre_razon_social.trim().split(' ')
  if (palabras.length >= 2) {
    return (palabras[0][0] + palabras[1][0]).toUpperCase()
  }
  return palabras[0].substring(0, 2).toUpperCase()
}

const formatearNumeroContrato = () => {
  return `CDD-${new Date().getFullYear()}-${String(props.prestamo?.id ?? '').padStart(4, '0')}`
}

/* =========================
   Computados
========================= */
const fechaPrimerPago = computed(() => {
  if (!props.prestamo?.fecha_primer_pago) return 'Fecha no disponible'
  return formatearFechaFlex(props.prestamo.fecha_primer_pago)
})

const fechaVencimiento = computed(() => {
  // Vencimiento por número de pagos desde fecha_inicio
  if (!props.prestamo?.fecha_inicio || !props.prestamo?.numero_pagos) return 'Fecha no disponible'
  const fecha = new Date(props.prestamo.fecha_inicio)
  fecha.setMonth(fecha.getMonth() + Number(props.prestamo.numero_pagos || 0))
  return formatearFechaFlex(fecha)
})

const resumenFinanciero = computed(() => {
  return {
    montoTotal: formatearMoneda(props.prestamo?.monto_prestado || 0),
    pagoMensual: formatearMoneda(props.prestamo?.pago_periodico || 0),
    numeroPagos: props.prestamo?.numero_pagos || 0,
    tasaInteres: `${(props.tasa_mensual || 0).toFixed(2)}%`,
    fechaInicio: formatearFechaFlex(props.prestamo?.fecha_inicio),
    primerPago: fechaPrimerPago.value
  }
})

/* =========================
   Validación previa
========================= */
const validarDatos = () => {
  const faltantes = []
  if (!props.cliente?.nombre_razon_social) faltantes.push('Nombre/Razón social del cliente')
  if (!props.prestamo?.monto_prestado) faltantes.push('Monto del préstamo')
  if (!props.prestamo?.pago_periodico) faltantes.push('Pago mensual')
  if (!props.prestamo?.numero_pagos) faltantes.push('Número de pagos')
  if (!props.prestamo?.fecha_inicio) faltantes.push('Fecha de inicio')
  if (!props.prestamo?.fecha_primer_pago) faltantes.push('Fecha del primer pago')
  if (faltantes.length) {
    notyf.open({ type: 'warning', message: `Faltan datos: ${faltantes.join(', ')}` })
  }
}

/* =========================
  Opciones de tamaño de papel
========================= */
const tamanosPapel = {
 carta: { nombre: 'Carta', dimensiones: '216mm 279mm', margin: '4mm 6mm 4mm 6mm' },
 oficio: { nombre: 'Oficio', dimensiones: '216mm 330mm', margin: '4mm 6mm 4mm 6mm' },
 a4: { nombre: 'A4', dimensiones: '210mm 297mm', margin: '4mm 6mm 4mm 6mm' }
}

const tamanoSeleccionado = ref('carta')

/* =========================
   Generación de PDF (print)
========================= */
const generarPDF = (tamano = 'carta') => {
  try {
    validarDatos()

    const contenidoPDF = generarContenidoPagare(tamano)
    const win = window.open('', '_blank', 'width=900,height=1100,scrollbars=yes,resizable=yes')
    if (!win) {
      notyf.error('Habilita las ventanas emergentes para generar el PDF.')
      return
    }

    // Crear el documento HTML completo
    win.document.open()
    win.document.write(contenidoPDF)
    win.document.close()

    // Agregar estilos adicionales para impresión
    win.document.head.insertAdjacentHTML('beforeend', `
      <style>
        /* Ocultar elementos del navegador */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

        /* Asegurar que el contenido se vea correctamente */
        body { margin: 0; padding: 0; }

        /* Evitar problemas de about:blank */
        html { background: white !important; }
      </style>
    `)

    // Esperar a que el contenido cargue completamente
    win.onload = function() {
      setTimeout(() => {
        win.focus()
        win.print()
      }, 300)
    }

    // Fallback por si no carga el evento onload
    setTimeout(() => {
      if (!win.closed) {
        win.focus()
        win.print()
      }
    }, 1000)

  } catch (error) {
    console.error('Error generando PDF:', error)
    notyf.error('Error al generar el pagaré PDF')
  }
}

const generarContenidoPagare = (tamano = 'carta') => {
  // ========= Datos seguros =========
  const folio = `PAG-${String(props.prestamo?.id ?? '').padStart(6, '0')}`
  const fechaHoy = formatearFechaFlex(props.fecha_actual)
  const empresaNombre = props.empresa?.nombre || 'ACREEDOR NO ESPECIFICADO'
  const empresaComercial = props.empresa?.nombre_comercial ? ` • ${props.empresa?.nombre_comercial}` : ''
  const empresaDomicilio = props.empresa?.direccion || 'Domicilio del acreedor no especificado'
  const empresaRFC = props.empresa?.rfc || 'RFC no especificado'
  const clienteNombre = props.cliente?.nombre_razon_social || 'DEUDOR NO ESPECIFICADO'
  const clienteDom = props.cliente?.direccion_completa || 'Domicilio del deudor no especificado'
  const clienteRFC = props.cliente?.rfc || ''
  const monto = formatearMoneda(props.prestamo?.monto_prestado)
  const pagoMensual = formatearMoneda(props.prestamo?.pago_periodico)
  const numeroPagos = props.prestamo?.numero_pagos ?? 'N/D'
  const tasa = (props.tasa_mensual ?? 0).toFixed(2)
  const primerPago = fechaPrimerPago.value
  const vencimiento = fechaVencimiento.value
  const refContrato = `Contrato de Préstamo No. ${props.prestamo?.id ?? 'N/A'}`
  const notas = (props.prestamo?.notas || props.prestamo?.observaciones || '').toString().trim()
  const notasHtml = notas ? `<div class="notas-title">Notas</div><div class="notas-text">${notas.replace(/\n/g, '<br>')}</div>` : ''

  // ========= Colores / tamaño =========
  const size = tamanosPapel[tamano]?.dimensiones || '216mm 279mm'
  const margin = tamanosPapel[tamano]?.margin || '2mm 3mm 2mm 3mm'
  const ACCENT = '#0B3D2E'   // verde oscuro ejecutivo
  const BORDER = '#0F172A'   // gris azulado profundo
  const MUTED = '#334155'    // texto secundario

  // ========= HTML/CSS =========
  return `
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>${folio} • Pagaré</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    /* ======== PÁGINA ======== */
    @page { size: ${size}; margin: ${margin}; }
    :root {
      --accent: ${ACCENT};
      --border: ${BORDER};
      --muted: ${MUTED};
      --ink: #111827;
      --bg-soft: #F8FAFC;
    }
    * { box-sizing: border-box; }
    html, body {
      background: #fff;
      color: var(--ink);
      font-family: "Times New Roman", Times, serif;
      font-size: 10px;
      line-height: 1.3;
    }
    body { margin: 0; }

    /* ======== HEADER / FOOTER FIJOS ======== */
     header {
       position: fixed;
       top: 0; left: 0; right: 0;
       height: 20mm;
       padding-top: 5mm;
       border-bottom: 1px solid #eee;
     }
    .h-inner {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 8mm;
      align-items: center;
    }
    .brand {
      font-size: 14px;
      font-weight: 700;
      letter-spacing: .3px;
      color: var(--accent);
      text-transform: uppercase;
    }
    .folio {
      padding: 6px 12px;
      border: 2px solid var(--border);
      border-radius: 4px;
      font-weight: 800;
      font-size: 12px;
    }
    .h-meta {
      margin-top: 1mm;
      color: var(--muted);
      font-size: 8px;
      max-width: 140mm;
    }

    footer {
      position: fixed;
      bottom: 0; left: 0; right: 0;
      height: 18mm;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      padding: 2mm 1mm;
      color: var(--muted);
      font-size: 7px;
    }
    .foot-left { max-width: 70%; text-align: left; }
    .foot-right { text-align: right; }

    /* Empuje del contenido para no solaparse con header/footer */
     .page-wrap { padding: 22mm 0 20mm; }

    /* ======== TÍTULO Y LUGAR/FECHA ======== */
     .title {
       text-align: center;
       margin: 2mm 0 1mm;
       font-weight: 800;
       font-size: 17px;
       letter-spacing: .7px;
     }
     .subtitle {
       text-align: center;
       color: var(--muted);
       margin-bottom: 0.5mm;
       font-size: 10.5px;
     }
     .place-date {
       text-align: center;
       color: var(--muted);
       margin-bottom: 4mm;
       font-size: 8.5px;
     }

    /* ======== BLOQUES ======== */
     .block {
       border: 1px solid var(--border);
       border-radius: 5px;
       padding: 5mm;
       margin-bottom: 5mm;
       background: #fff;
     }
    .block.soft {
      background: var(--bg-soft);
      border-color: #CBD5E1;
    }
    .block-title {
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .4px;
      color: var(--accent);
      margin-bottom: 2mm;
      font-size: 11px;
    }

    /* ======== GRID 2 COLS ======== */
     .grid-2 {
       display: grid;
       grid-template-columns: 1fr 1fr;
       gap: 5mm;
     }

    /* ======== TABLE RESUMEN ======== */
     table {
       width: 100%;
       border-collapse: collapse;
       font-size: 9.5px;
     }
     th, td {
       padding: 5px 7px;
       border: 1px solid #CBD5E1;
       vertical-align: top;
     }
    th {
      background: #EFF6FF;
      text-align: left;
      font-weight: 700;
      color: #0F172A;
    }
    td.label {
      width: 35%;
      color: var(--muted);
      font-weight: 600;
    }
    .kpi {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3mm;
      margin-top: 1mm;
    }
    .pill {
      border: 1px solid var(--border);
      border-radius: 4px;
      padding: 3mm;
      background: #fff;
      text-align: center;
    }
    .pill .small { color: var(--muted); font-size: 8px; }
    .pill .big { font-size: 12px; font-weight: 800; margin-top: 0.5mm; }

    /* ======== CLÁUSULAS ======== */
    .clauses ol { margin: 0; padding-left: 4mm; }
    .clauses li { margin: 2mm 0; text-align: justify; }

    /* ======== FIRMAS ======== */
     .signs {
       display: grid;
       grid-template-columns: 1fr 1fr;
       gap: 10mm;
       margin-top: 6mm;
       page-break-inside: avoid;
     }
     .sign {
       text-align: center;
       padding: 8mm 5mm 5mm;
       border: 1px dashed #94A3B8;
       border-radius: 5px;
       background: #FFFFFF;
     }
    .line {
      height: 20px;
      border-bottom: 1px solid var(--border);
      margin: 0 auto 2mm;
      width: 75%;
    }
    .sign .name { font-weight: 700; }
    .sign .role { color: var(--muted); font-size: 9px; }

    /* ======== NOTAS (en footer) ======== */
    .notas-title {
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .4px;
      margin-bottom: 2mm;
      color: var(--accent);
    }
    .notas-text {
      font-size: 9.5px;
      color: var(--ink);
      line-height: 1.35;
    }

    /* ======== MEDIA PRINT ======== */
    @media print {
      a { color: inherit; text-decoration: none; }
      .no-print { display: none !important; }
      html, body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>
<body>
  <!-- HEADER -->
  <header>
    <div class="h-inner" style="padding:0 2mm;">
      <div>
        <div class="brand">${empresaNombre}${empresaComercial}</div>
        <div class="h-meta">${empresaDomicilio}</div>
      </div>
      <div class="folio">${folio}</div>
    </div>
  </header>

  <!-- FOOTER -->
  <footer>
    <div class="foot-left">
      ${notasHtml}
    </div>
    <div class="foot-right">
      <div>Generado: ${formatearFechaCompleta(new Date())}</div>
      <div>${refContrato}</div>
    </div>
  </footer>

  <!-- CONTENIDO -->
  <div class="page-wrap">
    <div class="title">PAGARÉ</div>
    <div class="subtitle">Título Ejecutivo de Crédito</div>
    <div class="place-date">Hermosillo, Sonora, México • ${fechaHoy}</div>

    <!-- PROMESA DE PAGO -->
    <div class="block">
      <div class="block-title">Promesa de pago</div>
      <p style="text-align:justify;">
        Por este medio, yo <strong>${clienteNombre}</strong>, con domicilio en <strong>${clienteDom}</strong>${clienteRFC ? ` (RFC: <strong>${clienteRFC}</strong>)` : ''},
        me obligo incondicionalmente a pagar a la orden de <strong>${empresaNombre}</strong> (RFC: <strong>${empresaRFC}</strong>),
        en <strong>${empresaDomicilio}</strong>, la cantidad de <strong>${monto}</strong> (${props.monto_letras}),
        más intereses ordinarios a razón de <strong>${tasa}% mensual</strong>, pagaderos mensualmente junto con cada exhibición de capital.
      </p>
    </div>

    <!-- PARTES -->
    <div class="grid-2">
      <div class="block soft">
        <div class="block-title">Deudor</div>
        <table>
          <tr><td class="label">Nombre</td><td>${clienteNombre}</td></tr>
          <tr><td class="label">Domicilio</td><td>${clienteDom}</td></tr>
          ${clienteRFC ? `<tr><td class="label">RFC</td><td>${clienteRFC}</td></tr>` : ''}
        </table>
      </div>
      <div class="block soft">
        <div class="block-title">Acreedor</div>
        <table>
          <tr><td class="label">Beneficiario</td><td>${empresaNombre}${empresaComercial ? ` (${props.empresa?.nombre_comercial})` : ''}</td></tr>
          <tr><td class="label">Lugar de pago</td><td>${empresaDomicilio}</td></tr>
          <tr><td class="label">RFC</td><td>${empresaRFC}</td></tr>
        </table>
      </div>
    </div>

    <!-- RESUMEN FINANCIERO -->
    <div class="block">
      <div class="block-title">Resumen financiero</div>
      <table>
        <tr>
          <th>Monto del préstamo</th>
          <th>Pago mensual</th>
          <th>Tasa mensual</th>
          <th>Número de pagos</th>
          <th>Primer pago</th>
          <th>Vencimiento</th>
        </tr>
        <tr>
          <td>${monto} <br><span style="color:var(--muted);">${props.monto_letras}</span></td>
          <td>${pagoMensual} <br><span style="color:var(--muted);">${props.pago_mensual_letras}</span></td>
          <td>${tasa}%</td>
          <td>${numeroPagos}</td>
          <td>${primerPago}</td>
          <td>${vencimiento}</td>
        </tr>
      </table>

      <div class="kpi">
        <div class="pill">
          <div class="small">Referencia</div>
          <div class="big">${folio}</div>
        </div>
        <div class="pill">
          <div class="small">Contrato</div>
          <div class="big">${refContrato}</div>
        </div>
      </div>
    </div>

    <!-- CLÁUSULAS -->
    <div class="block">
      <div class="block-title">Cláusulas</div>
      <div class="clauses">
        <ol>
          <li><strong>Intereses moratorios.</strong> En caso de incumplimiento, se causarán intereses moratorios al doble de la tasa ordinaria sobre saldos vencidos hasta su total liquidación.</li>
          <li><strong>Vencimiento anticipado.</strong> La falta de dos pagos consecutivos o tres no consecutivos facultará al acreedor a dar por vencidas todas las obligaciones pendientes.</li>
          <li><strong>Gastos y costas.</strong> Todos los gastos de cobranza, honorarios y costas judiciales o extrajudiciales correrán a cargo del deudor.</li>
          <li><strong>Jurisdicción.</strong> Para la interpretación y cumplimiento del presente pagaré, las partes se someten a los tribunales competentes de Hermosillo, Sonora, renunciando al fuero que por razón de su domicilio presente o futuro pudiera corresponderles.</li>
          <li><strong>Cesión.</strong> Este título es negociable mediante endoso sin necesidad de notificar al deudor.</li>
          <li><strong>Naturaleza del título.</strong> El presente documento constituye título ejecutivo de conformidad con la Ley General de Títulos y Operaciones de Crédito. Requiere firma autógrafa del deudor.</li>
        </ol>
      </div>
    </div>

    <!-- FIRMAS -->
    <div class="signs">
      <div class="sign">
        <div class="line"></div>
        <div class="name">${clienteNombre}</div>
        <div class="role">Deudor • Firma autógrafa</div>
      </div>
      <div class="sign">
        <div class="line"></div>
        <div class="name">${empresaNombre}</div>
        <div class="role">Beneficiario (Acreedor) • Representante legal</div>
      </div>
    </div>
  </div>
</body>
</html>
`
}

</script>

<template>
  <Head title="Pagaré del Préstamo" />

  <div class="pagare-page min-h-screen bg-white dark:bg-slate-950 transition-colors duration-300">
    <div class="w-full px-6 py-8">
      <!-- Header Premium -->
      <div class="mb-8">
        <div class="bg-gradient-to-br from-slate-900 to-slate-950 rounded-2xl p-8 border border-slate-800 shadow-2xl relative overflow-hidden">
          <!-- Decoración de fondo -->
          <div class="absolute top-0 right-0 w-64 h-64 bg-blue-600/5 rounded-full blur-3xl -mr-32 -mt-32"></div>
          
          <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-8">
            <div>
              <div class="flex items-center gap-3 mb-4">
                 <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/20">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                 </div>
                 <h1 class="text-3xl font-black text-white tracking-tight">Instrumento Jurídico</h1>
              </div>
              <p class="text-slate-400 text-lg font-medium max-w-xl">Pagaré Digital constitutivo de obligación financiera exigible por la vía ejecutiva mercantil.</p>
              
              <div class="flex flex-wrap items-center gap-4 mt-6">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                  Documento Certificado
                </span>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">
                  Folio: {{ formatearNumeroContrato() }}
                </span>
              </div>
            </div>

            <div class="flex items-stretch gap-4">
               <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl p-5 border border-slate-800 flex flex-col justify-center min-w-[140px]">
                  <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Amortizaciones</p>
                  <p class="text-2xl font-black text-white">{{ prestamo.numero_pagos }} cuotas</p>
               </div>
               <div class="bg-blue-600 rounded-2xl p-5 shadow-lg shadow-blue-600/20 flex flex-col justify-center min-w-[180px]">
                  <p class="text-[10px] font-black text-blue-100/60 uppercase tracking-widest mb-1">Vencimiento Total</p>
                  <p class="text-xl font-black text-white tracking-tight">${{ formatearMoneda(prestamo.monto_prestado) }}</p>
               </div>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-6 mt-6">
          <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-3 bg-white dark:bg-slate-900 p-2 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
                <label class="text-xs font-black text-gray-500 dark:text-slate-500 uppercase tracking-widest pl-2">Papel:</label>
                <select
                  v-model="tamanoSeleccionado"
                  class="bg-transparent border-none focus:ring-0 text-sm font-bold text-gray-700 dark:text-slate-300 cursor-pointer"
                >
                  <option value="carta">Carta (US Legal)</option>
                  <option value="oficio">Oficio (Folio)</option>
                  <option value="a4">A4 (ISO)</option>
                </select>
            </div>

            <button
               @click="generarPDF(tamanoSeleccionado)"
               class="inline-flex items-center px-8 py-4 bg-blue-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-blue-700 hover:scale-[1.02] active:scale-95 shadow-xl shadow-blue-600/20 transition-all duration-300"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
              Imprimir Documento
            </button>
          </div>

          <div class="flex items-center gap-3">
             <Link
              :href="`/prestamos/${prestamo.id}`"
              class="px-6 py-4 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 text-gray-700 dark:text-slate-300 text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-gray-50 dark:hover:bg-slate-800 transition-all"
            >
              Cerrar Vista
            </Link>
          </div>
        </div>
      </div>

      <!-- Vista previa mejorada -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-800/60 overflow-hidden transition-all duration-300 mt-12">
        <div class="px-8 py-8 bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-950 border-b border-gray-200 dark:border-slate-800/60">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-2 tracking-tight">Vista Previa del Título</h2>
              <p class="text-gray-500 dark:text-slate-400 font-medium text-sm">
                Versión digital para validación de datos previo a impresión oficial.
              </p>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                Título de Crédito
              </span>
              <span class="text-xs font-black text-slate-500 uppercase tracking-widest leading-none">V{{ DOCUMENTO_INFO.version }}</span>
            </div>
          </div>
        </div>

        <div class="p-8 lg:p-12">
          <div class="max-w-[850px] mx-auto">
            <!-- Encabezado del documento -->
            <div class="text-center mb-16">
              <div class="inline-flex items-center justify-center w-20 h-20 bg-emerald-500/10 rounded-3xl mb-8 border border-emerald-500/20 shadow-inner">
                <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <h1 class="text-5xl font-black text-gray-900 dark:text-white mb-3 tracking-tighter uppercase">{{ DOCUMENTO_INFO.titulo }}</h1>
              <p class="text-slate-400 dark:text-slate-500 font-black uppercase tracking-[0.3em] text-[10px]">{{ DOCUMENTO_INFO.subtitulo }}</p>
              
              <div class="flex items-center justify-center flex-wrap gap-4 mt-8">
                <div class="px-4 py-2 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-100 dark:border-slate-800 text-sm font-bold text-slate-600 dark:text-slate-400">
                   {{ empresa.direccion }}
                </div>
                <div class="w-1.5 h-1.5 bg-blue-600 rounded-full animate-pulse"></div>
                <div class="px-4 py-2 bg-slate-100 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 text-sm font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">
                   {{ formatearFechaFlex(fecha_actual) }}
                </div>
              </div>
            </div>

            <!-- Información del contrato -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
              <div class="bg-slate-50 dark:bg-slate-950/50 rounded-2xl p-6 border border-slate-100 dark:border-slate-800/60 transition-all hover:bg-white dark:hover:bg-slate-950 hover:shadow-xl hover:shadow-black/5 group">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4">Referencia Única</p>
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/20 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                  </div>
                  <div class="text-xl font-black text-slate-900 dark:text-white tracking-tight">{{ formatearNumeroContrato() }}</div>
                </div>
              </div>

              <div class="bg-slate-50 dark:bg-slate-950/50 rounded-2xl p-6 border border-slate-100 dark:border-slate-800/60 transition-all hover:bg-white dark:hover:bg-slate-950 hover:shadow-xl hover:shadow-black/5 group">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4">Apertura Legal</p>
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 bg-emerald-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-emerald-600/20 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 9l6-6m0 0v6m0-6h-6"></path></svg>
                  </div>
                  <div class="text-xl font-black text-slate-900 dark:text-white tracking-tight">{{ fechaPrimerPago }}</div>
                </div>
              </div>

              <div class="bg-slate-50 dark:bg-slate-950/50 rounded-2xl p-6 border border-slate-100 dark:border-slate-800/60 transition-all hover:bg-white dark:hover:bg-slate-950 hover:shadow-xl hover:shadow-black/5 group">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4">Sede de Pago</p>
                <p class="text-sm font-black text-slate-900 dark:text-white leading-tight break-words uppercase tracking-tight">{{ empresa.direccion }}</p>
              </div>
            </div>

            <!-- Resumen financiero mejorado -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
              <div class="bg-blue-600 rounded-3xl p-6 text-white shadow-2xl shadow-blue-600/20 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>
                <p class="text-[10px] font-black text-blue-100/60 uppercase tracking-widest mb-1 relative z-10">Monto del Crédito</p>
                <div class="text-2xl font-black tracking-tight mb-2 relative z-10">{{ formatearMoneda(prestamo.monto_prestado) }}</div>
                <div class="text-[9px] font-bold text-blue-100/80 italic leading-tight uppercase tracking-tight relative z-10 line-clamp-2">{{ monto_letras }}</div>
              </div>

              <div class="bg-slate-900 rounded-3xl p-6 text-white border border-slate-800 relative group">
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Carga Ordinaria</p>
                <div class="text-2xl font-black tracking-tight mb-2 group-hover:text-blue-400 transition-colors">{{ tasa_mensual.toFixed(2) }}%</div>
                <div class="text-[9px] font-bold text-slate-500 italic uppercase">Tasa Mensual Fija</div>
              </div>

              <div class="bg-slate-50 dark:bg-slate-950 rounded-3xl p-6 border border-slate-100 dark:border-slate-800">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Amortización</p>
                <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ formatearMoneda(prestamo.pago_periodico) }}</div>
                <div class="text-[9px] font-bold text-slate-400 italic uppercase">{{ (prestamo.frecuencia_pago || 'Mensual').toUpperCase() }}</div>
              </div>

              <div class="bg-slate-50 dark:bg-slate-950 rounded-3xl p-6 border border-slate-100 dark:border-slate-800">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Plazo Pactado</p>
                <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ prestamo.numero_pagos }} cuotas</div>
                <div class="text-[9px] font-bold text-slate-400 italic uppercase">Vencimiento: {{ fechaVencimiento }}</div>
              </div>
            </div>

            <!-- Texto legal interactivo -->
            <div class="bg-amber-500/5 dark:bg-amber-500/5 border border-amber-500/20 rounded-3xl p-8 mb-12 relative overflow-hidden">
               <div class="absolute top-0 right-0 p-4 opacity-10">
                  <svg class="w-24 h-24 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A10.003 10.003 0 0012 3c1.268 0 2.39.234 3.41.659m-4.74 11.57a2 2 0 104 0 2 2 0 00-4 0z"></path></svg>
               </div>
               <h3 class="text-sm font-black text-amber-700 dark:text-amber-500 uppercase tracking-[0.2em] mb-4">Declaración de Obligación</h3>
               <div class="space-y-4 text-slate-700 dark:text-slate-300 text-sm font-medium leading-relaxed max-w-2xl">
                  <p>
                    Yo, <span class="font-black text-slate-900 dark:text-white underline decoration-amber-500/30 decoration-2 underline-offset-4">{{ cliente.nombre_razon_social }}</span>, 
                    con domicilio legal plenamente constituido, me obligo de manera incondicional a liquidar a la orden de 
                    <span class="font-black text-slate-900 dark:text-white">{{ empresa.nombre }}</span> la suma exacta de 
                    <span class="font-black text-slate-900 dark:text-white">${{ formatearMoneda(prestamo.monto_prestado) }}</span>, 
                    más el rendimiento ordinario pactado.
                  </p>
                  <p class="text-xs text-slate-500 dark:text-slate-500 uppercase tracking-wider font-bold">
                    Este instrumento faculta la ejecución inmediata bajo la legislación mercantil vigente.
                  </p>
               </div>
            </div>

            <!-- Notas / Observaciones en Preview -->
            <div v-if="prestamo.notas || prestamo.observaciones" class="mb-12">
               <div class="flex items-center gap-3 mb-4">
                  <div class="w-1.5 h-6 bg-slate-300 dark:bg-slate-700 rounded-full"></div>
                  <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Notas y Observaciones Especiales</h3>
               </div>
               <div class="bg-slate-50 dark:bg-slate-950/30 rounded-2xl p-6 border border-slate-100 dark:border-slate-800/50 text-slate-600 dark:text-slate-400 text-sm italic leading-relaxed">
                  {{ prestamo.notas || prestamo.observaciones }}
               </div>
            </div>

            <!-- Firmas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
              <div class="text-center p-10 bg-slate-50 dark:bg-slate-950/50 rounded-3xl border border-dashed border-slate-300 dark:border-slate-800">
                <div class="w-40 mx-auto h-20 border-b-2 border-slate-900 dark:border-white mb-6 flex items-end justify-center">
                   <span class="text-[10px] text-slate-400 uppercase tracking-widest pb-2">Espacio para Firma</span>
                </div>
                <div class="text-lg font-black text-slate-900 dark:text-white tracking-tight">{{ cliente.nombre_razon_social }}</div>
                <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-1">Sujeto Obligado / Deudor</div>
              </div>
              <div class="text-center p-10 bg-slate-50 dark:bg-slate-950/50 rounded-3xl border border-dashed border-slate-300 dark:border-slate-800">
                <div class="w-40 mx-auto h-20 border-b-2 border-slate-900 dark:border-white mb-6 flex items-end justify-center">
                   <span class="text-[10px] text-slate-400 uppercase tracking-widest pb-2">Sello Acreedor</span>
                </div>
                <div class="text-lg font-black text-slate-900 dark:text-white tracking-tight">{{ empresa.nombre_comercial || empresa.nombre }}</div>
                <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mt-1">Beneficiario / Tenedor</div>
              </div>
            </div>

            <!-- Accion Final -->
            <div class="flex flex-col items-center justify-center gap-8 pt-12 border-t border-slate-100 dark:border-slate-800">
               <button
                @click="generarPDF(tamanoSeleccionado)"
                class="inline-flex items-center px-12 py-6 bg-blue-600 text-white text-sm font-black uppercase tracking-[0.2em] rounded-3xl hover:bg-blue-700 hover:scale-[1.05] active:scale-95 shadow-2xl shadow-blue-600/30 transition-all duration-300"
              >
                <svg class="w-6 h-6 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Exportar Formato PDF Oficial
              </button>
              
              <div class="text-center">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mb-2 leading-none">Generación Masiva Certificada</p>
                <div class="flex items-center justify-center gap-4 text-[10px] font-bold text-slate-500">
                   <span>ALTA DEFINICIÓN</span>
                   <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                   <span>VECTORIAL</span>
                   <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                   <span>LISTO PARA FIRMA</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>

