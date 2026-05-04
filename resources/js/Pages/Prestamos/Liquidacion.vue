<!-- /resources/js/Pages/Prestamos/Liquidacion.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  prestamo: { type: Object, required: true },
  cliente: { type: Object, required: true },
  empresa: { type: Object, required: true },
  fecha_actual: { type: String, required: true },
  fecha_liquidacion: { type: String, required: true },
  monto_total_letras: { type: String, required: true }
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
  const folio = `LIQ-${String(props.prestamo.id).padStart(6, '0')}`
  const empresaNombre = props.empresa?.razon_social || 'CLIMAS DEL DESIERTO'
  const empresaRFC = props.empresa?.rfc || ''
  const empresaDireccion = props.empresa?.direccion_completa || ''
  const clienteNombre = props.cliente?.nombre_razon_social || 'NO ESPECIFICADO'
  const clienteRFC = props.cliente?.rfc || ''
  
  return `
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Constancia de Liquidación ${folio}</title>
    <style>
        @page { size: letter; margin: 15mm 20mm; }
        body { font-family: 'Times New Roman', Times, serif; color: #0f172a; line-height: 1.4; padding: 0; margin: 0; background: #fff; text-align: justify; font-size: 13px; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #0f172a; padding-bottom: 15px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 3px; color: #0f172a; }
        .header p { margin: 5px 0 0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        
        .date-line { text-align: right; margin-bottom: 20px; }
        .subject { margin-bottom: 20px; font-weight: 900; }
        
        .main-text { margin-bottom: 15px; }
        .main-text p { margin-top: 0; margin-bottom: 10px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin: 20px 0; border: 1px solid #e2e8f0; position: relative; z-index: 1; }
        .data-table td { padding: 8px 12px; border: 1px solid #e2e8f0; background: transparent !important; }
        .data-table .label { font-weight: 900; width: 35%; text-transform: uppercase; font-size: 10px; }
        
        .footer { margin-top: 50px; text-align: center; }
        .signature-box { margin-bottom: 30px; display: inline-block; border-top: 1px solid #0f172a; padding: 10px 40px; min-width: 250px; }
        .signature-box p { margin: 0; font-weight: 800; text-transform: uppercase; font-size: 11px; }
        
        /* Marca de agua al frente pero muy translúcida para que no tape el texto pero sí se vea sobre la tabla */
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 130px; color: #64748b; font-weight: 900; z-index: 10; pointer-events: none; opacity: 0.12; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="watermark">LIQUIDADO</div>
    
    <div class="header">
        <h1>Constancia de Liquidación</h1>
        <p>${empresaNombre} | Crédito No. ${props.prestamo.folio || props.prestamo.id}</p>
    </div>

    <div class="date-line">
        Hermosillo, Sonora, México a <strong>${formatearFecha(props.fecha_actual)}</strong>
    </div>

    <div class="subject">
        ASUNTO: CARTA DE NO ADEUDO Y LIBERACIÓN DE OBLIGACIONES
    </div>

    <div class="main-text">
        <p>A QUIEN CORRESPONDA:</p>
        
        <p>Por la presente, <strong>${empresaNombre}</strong>, con Registro Federal de Contribuyentes <strong>${empresaRFC}</strong>, hace constar formalmente que el Sr.(a)/Empresa: <strong>${clienteNombre}</strong>${clienteRFC ? ` (RFC: <strong>${clienteRFC}</strong>)` : ''}, ha cumplido satisfactoriamente con la totalidad de sus obligaciones financieras derivadas del contrato de préstamo celebrado con esta institución.</p>

        <p>Se certifica que, a la fecha de emisión del presente documento, el crédito con folio <strong>${props.prestamo.folio || props.prestamo.id}</strong> se encuentra <strong>LIQUIDADO EN SU TOTALIDAD</strong>, habiendo cubierto íntegramente la cantidad de <strong>${formatearMoneda(props.prestamo.monto_total_pagar)}</strong> (${props.monto_total_letras}), incluyendo capital, intereses ordinarios y cualquier otro accesorio pactado.</p>
    </div>

    <table class="data-table">
        <tr>
            <td class="label">Folio del Crédito</td>
            <td>${props.prestamo.folio || props.prestamo.id}</td>
        </tr>
        <tr>
            <td class="label">Fecha de Inicio</td>
            <td>${formatearFecha(props.prestamo.fecha_inicio)}</td>
        </tr>
        <tr>
            <td class="label">Fecha de Liquidación</td>
            <td>${formatearFecha(props.fecha_liquidacion)}</td>
        </tr>
        <tr>
            <td class="label">Monto Total Liquidado</td>
            <td><strong>${formatearMoneda(props.prestamo.monto_total_pagar)}</strong></td>
        </tr>
        <tr>
            <td class="label">Estado del Crédito</td>
            <td><strong>CERRADO Y LIBERADO</strong></td>
        </tr>
    </table>

    <div class="main-text">
        <p>En virtud de lo anterior, se otorga a el(la) <strong>${clienteNombre}</strong> la más amplia liberación que en derecho proceda respecto a las obligaciones pecuniarias derivadas del mencionado crédito, no reservándose la empresa acción o derecho alguno que ejercitar en un futuro por este concepto.</p>
    </div>

    <div class="footer">
        <div style="position: relative; display: inline-block; margin-top: 60px;">
            ${props.empresa?.firma_digital ? 
                `<img src="${props.empresa.firma_digital}" style="position: absolute; bottom: 35px; left: 50%; transform: translateX(-50%); max-height: 100px; z-index: 5; pointer-events: none;" />` : 
                ''
            }
            <div class="signature-box" style="position: relative; z-index: 10; margin-bottom: 0;">
                <p>Representante Legal</p>
                <p>${empresaNombre}</p>
            </div>
        </div>
        
        <p style="font-size: 10px; color: #64748b; margin-top: 40px;">
            Este documento no requiere sellos adicionales si la firma autógrafa está presente.<br>
            ${empresaDireccion} | Folio de Validación: LIQ-${String(props.prestamo.id).padStart(8, '0')}
        </p>
    </div>
</body>
</html>
  `
}
</script>

<template>
  <Head title="Constancia de Liquidación" />

  <div class="min-h-screen bg-slate-950 py-12 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-indigo-950 border border-indigo-400/20 rounded-3xl p-10 shadow-2xl overflow-hidden relative group">
            <div class="absolute -top-48 -right-48 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl transition-all group-hover:bg-indigo-500/30"></div>
            
            <div class="relative z-10">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8 mb-12">
                    <div>
                        <div class="inline-flex items-center px-4 py-2 bg-emerald-500/10 text-emerald-400 text-[10px] font-black uppercase tracking-widest rounded-full border border-emerald-500/20 mb-6">
                            Crédito Finalizado Exitosamente
                        </div>
                        <h1 class="text-5xl font-black text-white tracking-tighter mb-4">Constancia de Liquidación</h1>
                        <p class="text-indigo-200/60 text-lg font-medium max-w-2xl">Este documento formal certifica la liberación de toda obligación financiera del cliente con la empresa.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                   <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                       <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2">Cliente</p>
                       <p class="text-white font-bold">{{ cliente.nombre_razon_social }}</p>
                   </div>
                   <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                       <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2">Liquidación</p>
                       <p class="text-white font-bold">${{ formatearMoneda(prestamo.monto_total_pagar) }}</p>
                   </div>
                   <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                       <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2">Ahorro de Intereses</p>
                       <p class="text-emerald-400 font-bold">$0.00 (Liquidado)</p>
                   </div>
                </div>

                <div class="flex flex-wrap items-center gap-6">
                    <button @click="generarPDF" class="inline-flex items-center px-10 py-5 bg-white text-indigo-900 text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-indigo-50 hover:scale-[1.02] active:scale-95 transition-all shadow-2xl shadow-indigo-500/20">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Imprimir Constancia Oficial
                    </button>
                    
                    <Link :href="`/prestamos/${prestamo.id}`" class="px-8 py-5 bg-indigo-900/50 border border-indigo-400/20 text-indigo-100 text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-indigo-900 transition-all">
                        Regresar al Préstamo
                    </Link>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>
