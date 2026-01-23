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
       height: 3mm;
       padding-top: 0.2mm;
       /* border-bottom: 1px solid var(--border); */
     }
    .h-inner {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 8mm;
      align-items: center;
    }
    .brand {
      font-size: 15px;
      font-weight: 700;
      letter-spacing: .3px;
      color: var(--accent);
      text-transform: uppercase;
    }
    .folio {
      padding: 6px 10px;
      border: 1px solid var(--border);
      border-radius: 4px;
      font-weight: 700;
    }
    .h-meta {
      margin-top: 0.2mm;
      color: var(--muted);
      font-size: 8px;
    }

    footer {
      position: fixed;
      bottom: 0; left: 0; right: 0;
      height: 5mm;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 1mm;
      color: var(--muted);
      font-size: 4px;
    }
    .foot-left { max-width: 70%; }
    .foot-right { text-align: right; }

    /* Empuje del contenido para no solaparse con header/footer */
     .page-wrap { padding: 5mm 0 4mm; }

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

  <div class="pagare-page min-h-screen bg-white dark:bg-slate-900">
    <div class="w-full px-6 py-8">
      <!-- Header mejorado -->
      <div class="mb-8">
        <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-2xl p-6 text-white">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold tracking-tight mb-2">Pagaré Digital</h1>
              <p class="text-gray-300 text-lg">Documento legal de obligación financiera</p>
              <div class="flex items-center gap-4 mt-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-300 border border-green-400/30">
                  ✅ Documento Válido
                </span>
                <span class="text-sm text-gray-300">
                  Referencia: {{ formatearNumeroContrato() }}
                </span>
              </div>
            </div>
            <div class="hidden md:flex items-center gap-3">
              <div class="text-center">
                <div class="text-2xl font-bold">{{ prestamo.numero_pagos }}</div>
                <div class="text-xs text-gray-300 uppercase tracking-wide">Pagos</div>
              </div>
              <div class="w-px h-12 bg-gray-600"></div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-400">{{ formatearMoneda(prestamo.monto_prestado) }}</div>
                <div class="text-xs text-gray-300 uppercase tracking-wide">Monto Total</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Selector de tamaño de papel y acciones -->
        <div class="flex flex-wrap items-center justify-between gap-4 mt-4">
          <div class="flex items-center gap-3">
            <!-- Selector de tamaño de papel -->
            <div class="flex items-center gap-2">
              <label class="text-sm font-medium text-gray-700">Tamaño de papel:</label>
              <select
                v-model="tamanoSeleccionado"
                @change="generarPDF(tamanoSeleccionado)"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
              >
                <option value="carta">Carta (8.5" × 11")</option>
                <option value="oficio">Oficio (8.5" × 13")</option>
                <option value="a4">A4 (210mm × 297mm)</option>
              </select>
            </div>

            <div class="w-px h-8 bg-gray-300"></div>

            <button
              @click="generarPDF(tamanoSeleccionado)"
              class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
              </svg>
              Generar PDF
            </button>
            <button
              @click="validarDatos"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900 transition-colors"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Validar Datos
            </button>
          </div>

          <div class="flex items-center gap-2">
            <Link
              :href="`/prestamos/${prestamo.id}`"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900 transition-colors"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
              Ver Préstamo
            </Link>
            <Link
              href="/prestamos"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl text-gray-700 bg-white dark:bg-slate-900 hover:bg-white dark:bg-slate-900 transition-colors"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Volver a Préstamos
            </Link>
          </div>
        </div>
      </div>

      <!-- Vista previa mejorada -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="px-8 py-6 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 dark:border-slate-800">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Vista Previa del Documento</h2>
              <p class="text-gray-600 dark:text-gray-300">
                Versión preliminar del pagaré oficial • El PDF final incluye formato A4 profesional
              </p>
            </div>
            <div class="hidden md:flex items-center gap-2">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                Documento Legal
              </span>
              <span class="text-sm text-gray-500 dark:text-gray-400">Versión {{ DOCUMENTO_INFO.version }}</span>
            </div>
          </div>
        </div>

        <div class="p-8">
          <div class="max-w-[900px] mx-auto">
            <!-- Encabezado del documento -->
            <div class="text-center mb-8">
              <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-2">{{ DOCUMENTO_INFO.titulo }}</h1>
              <p class="text-gray-600 dark:text-gray-300">{{ DOCUMENTO_INFO.subtitulo }}</p>
              <div class="flex items-center justify-center gap-2 mt-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ empresa.direccion }}</span>
                <span class="text-gray-300">•</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ formatearFechaFlex(fecha_actual) }}</span>
              </div>
            </div>

            <!-- Información del contrato -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
              <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800">
                <div class="flex items-center mb-3">
                  <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">Referencia</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Número de contrato</div>
                  </div>
                </div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ formatearNumeroContrato() }}</div>
              </div>

              <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800">
                <div class="flex items-center mb-3">
                  <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 9l6-6m0 0v6m0-6h-6"></path>
                    </svg>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">Fecha de Inicio</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Primer pago</div>
                  </div>
                </div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ fechaPrimerPago }}</div>
              </div>

              <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800">
                <div class="flex items-center mb-3">
                  <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">Lugar de Pago</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Jurisdicción</div>
                  </div>
                </div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ empresa.direccion }}</div>
              </div>
            </div>

            <!-- Resumen financiero mejorado -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
              <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200 text-center">
                <div class="text-green-600 mb-2">
                  <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                  </svg>
                </div>
                <div class="text-xs text-green-700 uppercase tracking-wide font-semibold mb-1">Monto Total</div>
                <div class="text-2xl font-extrabold text-green-800">{{ formatearMoneda(prestamo.monto_prestado) }}</div>
                <div class="text-xs text-green-600">{{ monto_letras }}</div>
              </div>

              <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200 text-center">
                <div class="text-blue-600 mb-2">
                  <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                  </svg>
                </div>
                <div class="text-xs text-blue-700 uppercase tracking-wide font-semibold mb-1">Tasa Mensual</div>
                <div class="text-2xl font-extrabold text-blue-800">{{ tasa_mensual.toFixed(2) }}%</div>
                <div class="text-xs text-blue-600">Interés ordinario</div>
              </div>

              <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200 text-center">
                <div class="text-purple-600 mb-2">
                  <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="text-xs text-purple-700 uppercase tracking-wide font-semibold mb-1">Pago Mensual</div>
                <div class="text-2xl font-extrabold text-purple-800">{{ formatearMoneda(prestamo.pago_periodico) }}</div>
                <div class="text-xs text-purple-600">{{ pago_mensual_letras }}</div>
              </div>

              <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-6 border border-orange-200 text-center">
                <div class="text-orange-600 mb-2">
                  <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                  </svg>
                </div>
                <div class="text-xs text-orange-700 uppercase tracking-wide font-semibold mb-1">Total de Pagos</div>
                <div class="text-2xl font-extrabold text-orange-800">{{ prestamo.numero_pagos }}</div>
                <div class="text-xs text-orange-600">Mensualidades</div>
              </div>
            </div>

            <!-- Información de las partes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
              <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  Información del Deudor
                </h3>
                <div class="space-y-3">
                  <div>
                    <div class="text-sm font-medium text-gray-700">Nombre completo</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ cliente.nombre_razon_social }}</div>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-700">Domicilio</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ cliente.direccion_completa || 'Domicilio no especificado' }}</div>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-700">Fecha de Vencimiento</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ fechaVencimiento }}</div>
                  </div>
                </div>
              </div>

              <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                  </svg>
                  Información del Acreedor
                </h3>
                <div class="space-y-3">
                  <div>
                    <div class="text-sm font-medium text-gray-700">Beneficiario</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ empresa.nombre }}</div>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-700">Lugar de Pago</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ empresa.direccion }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Texto legal mejorado -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                Compromiso de Pago
              </h3>
              <p class="text-gray-800 dark:text-gray-100 leading-relaxed mb-4">
                Yo, <strong>{{ cliente.nombre_razon_social }}</strong>, con domicilio en <strong>{{ cliente.direccion_completa || 'Domicilio no especificado' }}</strong>,
                me obligo incondicionalmente a pagar a la orden de <strong>{{ empresa.nombre }}</strong> la cantidad
                de <strong>{{ formatearMoneda(prestamo.monto_prestado) }}</strong> (${{ monto_letras }}), más intereses ordinarios a razón de <strong>{{ tasa_mensual.toFixed(2) }}%</strong> mensual,
                pagaderos mensualmente junto con cada exhibición de capital.
              </p>
              <p class="text-gray-800 dark:text-gray-100 leading-relaxed">
                Los pagos se realizarán mensualmente a partir del <strong>{{ fechaPrimerPago }}</strong>,
                hasta completar un total de <strong>{{ prestamo.numero_pagos }}</strong> pagos,
                conforme al plan de pagos establecido.
              </p>
            </div>

            <!-- Firmas visuales mejoradas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
              <div class="text-center p-6 bg-white dark:bg-slate-900 rounded-xl border-2 border-dashed border-gray-300">
                <div class="w-3/4 mx-auto h-16 border-b-2 border-gray-700 mb-4"></div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ cliente.nombre_razon_social }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-300">Deudor(a) — Firma autógrafa</div>
              </div>
              <div class="text-center p-6 bg-white dark:bg-slate-900 rounded-xl border-2 border-dashed border-gray-300">
                <div class="w-3/4 mx-auto h-16 border-b-2 border-gray-700 mb-4"></div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ empresa.nombre_comercial }}  {{ empresa.nombre }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-300">Acreedor — Representante legal</div>
              </div>
            </div>

            <!-- Información adicional -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 text-center">
              <div class="flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-blue-900">Información Importante</h3>
              </div>
              <p class="text-blue-800 mb-4">
                Este documento constituye un título ejecutivo que faculta al tenedor legítimo para exigir el pago
                por la vía ejecutiva correspondiente. El incumplimiento generará intereses moratorios y podrá
                resultar en el vencimiento anticipado de la obligación.
              </p>
              <div class="flex items-center justify-center gap-4 text-sm text-blue-700">
                <span>✓ Documento Legal Válido</span>
                <span>✓ Protegido por Legislación Mexicana</span>
                <span>✓ Confidencial y Privado</span>
              </div>
            </div>

            <!-- Footer de la vista previa -->
            <div class="text-center mt-8 pt-6 border-t border-gray-200 dark:border-slate-800">
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Esta es una vista previa • El documento PDF oficial incluye formato A4 con encabezado y pie de página profesionales
              </p>
              <p class="text-xs text-gray-400 mt-2">
                Generado el {{ formatearFechaCompleta(new Date()) }} • Versión {{ DOCUMENTO_INFO.version }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Navegación inferior mejorada -->
      <div class="flex justify-center mt-8">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 p-6">
          <div class="flex flex-col items-center gap-4">
            <!-- Selector de tamaño para el botón inferior -->
            <div class="flex items-center gap-3">
              <label class="text-sm font-medium text-gray-700">Tamaño:</label>
              <select
                v-model="tamanoSeleccionado"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
              >
                <option value="carta">📄 Carta (8.5" × 11")</option>
                <option value="oficio">📃 Oficio (8.5" × 13")</option>
                <option value="a4">📋 A4 (210mm × 297mm)</option>
              </select>
            </div>

            <button
              @click="generarPDF(tamanoSeleccionado)"
              class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white text-lg font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
            >
              <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
              </svg>
              Generar Documento PDF Oficial
            </button>
            <div class="text-center text-gray-600 dark:text-gray-300">
              <div class="font-medium">Documento profesional listo para impresión</div>
              <div class="text-sm text-gray-500 dark:text-gray-400">Tamaño seleccionado: {{ tamanosPapel[tamanoSeleccionado]?.nombre }} • Diseño ejecutivo</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>

