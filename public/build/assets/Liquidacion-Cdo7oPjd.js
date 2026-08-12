import{g as _,o as C,e as b,d as t,j as x,p as $,t as f,k as h,w as q,q as E,l as D}from"./vue-core-DQ9HmJv9.js";import{A as L}from"./AppLayout-COVrmEQf.js";import{N as I}from"./vendor-XdTCjOfo.js";import"./utils-C1cdQfIJ.js";import"./icons-Dg70kTOZ.js";import"./charts-CXqM3Iku.js";import"./_plugin-vue_export-helper-DlAUqK2U.js";import"./Swal-CBcbti4V.js";import"./ToastContainer-Bp95keZs.js";import"./Modal-B_cJWuA3.js";import"./useDarkMode-DXRbZsUc.js";const O={class:"min-h-screen bg-[var(--ui-surface)] py-12 px-4"},A={class:"max-w-4xl mx-auto"},N={class:"bg-indigo-950 border border-indigo-400/20 rounded-3xl p-10 shadow-2xl overflow-hidden relative group"},z={class:"relative z-10"},S={class:"grid grid-cols-1 md:grid-cols-3 gap-6 mb-12"},R={class:"bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10"},F={class:"text-white font-bold"},j={class:"bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10"},T={class:"text-white font-bold"},M={class:"flex flex-wrap items-center gap-6"},W=Object.assign({layout:L},{__name:"Liquidacion",props:{prestamo:{type:Object,required:!0},cliente:{type:Object,required:!0},empresa:{type:Object,required:!0},fecha_actual:{type:String,required:!0},fecha_liquidacion:{type:String,required:!0},monto_total_letras:{type:String,required:!0}},setup(i){const o=i;new I({duration:4e3,position:{x:"right",y:"top"}});const r=a=>new Intl.NumberFormat("es-MX",{style:"currency",currency:"MXN"}).format(a),n=a=>a?new Date(a).toLocaleDateString("es-MX",{day:"2-digit",month:"long",year:"numeric"}):"N/A",v=()=>{const a=w(),e=window.open("","_blank");e.document.write(a),e.document.close(),e.onload=()=>{setTimeout(()=>{e.focus(),e.print()},300)}},w=()=>{var l,c,p,m,g,u;const a=`LIQ-${String(o.prestamo.id).padStart(6,"0")}`,e=((l=o.empresa)==null?void 0:l.razon_social)||"CLIMAS DEL DESIERTO",y=((c=o.empresa)==null?void 0:c.rfc)||"",k=((p=o.empresa)==null?void 0:p.direccion_completa)||"",s=((m=o.cliente)==null?void 0:m.nombre_razon_social)||"NO ESPECIFICADO",d=((g=o.cliente)==null?void 0:g.rfc)||"";return`
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Constancia de Liquidación ${a}</title>
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
        <p>${e} | Crédito No. ${o.prestamo.folio||o.prestamo.id}</p>
    </div>

    <div class="date-line">
        Hermosillo, Sonora, México a <strong>${n(o.fecha_actual)}</strong>
    </div>

    <div class="subject">
        ASUNTO: CARTA DE NO ADEUDO Y LIBERACIÓN DE OBLIGACIONES
    </div>

    <div class="main-text">
        <p>A QUIEN CORRESPONDA:</p>
        
        <p>Por la presente, <strong>${e}</strong>, con Registro Federal de Contribuyentes <strong>${y}</strong>, hace constar formalmente que el Sr.(a)/Empresa: <strong>${s}</strong>${d?` (RFC: <strong>${d}</strong>)`:""}, ha cumplido satisfactoriamente con la totalidad de sus obligaciones financieras derivadas del contrato de préstamo celebrado con esta institución.</p>

        <p>Se certifica que, a la fecha de emisión del presente documento, el crédito con folio <strong>${o.prestamo.folio||o.prestamo.id}</strong> se encuentra <strong>LIQUIDADO EN SU TOTALIDAD</strong>, habiendo cubierto íntegramente la cantidad de <strong>${r(o.prestamo.monto_total_pagar)}</strong> (${o.monto_total_letras}), incluyendo capital, intereses ordinarios y cualquier otro accesorio pactado.</p>
    </div>

    <table class="data-table">
        <tr>
            <td class="label">Folio del Crédito</td>
            <td>${o.prestamo.folio||o.prestamo.id}</td>
        </tr>
        <tr>
            <td class="label">Fecha de Inicio</td>
            <td>${n(o.prestamo.fecha_inicio)}</td>
        </tr>
        <tr>
            <td class="label">Fecha de Liquidación</td>
            <td>${n(o.fecha_liquidacion)}</td>
        </tr>
        <tr>
            <td class="label">Monto Total Liquidado</td>
            <td><strong>${r(o.prestamo.monto_total_pagar)}</strong></td>
        </tr>
        <tr>
            <td class="label">Estado del Crédito</td>
            <td><strong>CERRADO Y LIBERADO</strong></td>
        </tr>
    </table>

    <div class="main-text">
        <p>En virtud de lo anterior, se otorga a el(la) <strong>${s}</strong> la más amplia liberación que en derecho proceda respecto a las obligaciones pecuniarias derivadas del mencionado crédito, no reservándose la empresa acción o derecho alguno que ejercitar en un futuro por este concepto.</p>
    </div>

    <div class="footer">
        <div style="position: relative; display: inline-block; margin-top: 60px;">
            ${(u=o.empresa)!=null&&u.firma_digital?`<img src="${o.empresa.firma_digital}" style="position: absolute; bottom: 35px; left: 50%; transform: translateX(-50%); max-height: 100px; z-index: 5; pointer-events: none;" />`:""}
            <div class="signature-box" style="position: relative; z-index: 10; margin-bottom: 0;">
                <p>Representante Legal</p>
                <p>${e}</p>
            </div>
        </div>
        
        <p style="font-size: 10px; color: #64748b; margin-top: 40px;">
            Este documento no requiere sellos adicionales si la firma autógrafa está presente.<br>
            ${k} | Folio de Validación: LIQ-${String(o.prestamo.id).padStart(8,"0")}
        </p>
    </div>
</body>
</html>
  `};return(a,e)=>(C(),_(D,null,[b(x($),{title:"Constancia de Liquidación"}),t("div",O,[t("div",A,[t("div",N,[e[6]||(e[6]=t("div",{class:"absolute -top-48 -right-48 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl transition-all group-hover:bg-indigo-500/30"},null,-1)),t("div",z,[e[5]||(e[5]=t("div",{class:"flex flex-col md:flex-row justify-between items-start md:items-center gap-8 mb-12"},[t("div",null,[t("div",{class:"inline-flex items-center px-4 py-2 bg-brand-500/10 text-emerald-400 text-[10px] font-black uppercase tracking-wide rounded-full border border-emerald-500/20 mb-6"}," Crédito Finalizado Exitosamente "),t("h1",{class:"text-5xl font-black text-white tracking-tighter mb-4"},"Constancia de Liquidación"),t("p",{class:"text-indigo-200/60 text-lg font-medium max-w-2xl"},"Este documento formal certifica la liberación de toda obligación financiera del cliente con la empresa.")])],-1)),t("div",S,[t("div",R,[e[0]||(e[0]=t("p",{class:"text-[10px] font-black text-indigo-400 uppercase tracking-wide mb-2"},"Cliente",-1)),t("p",F,f(i.cliente.nombre_razon_social),1)]),t("div",j,[e[1]||(e[1]=t("p",{class:"text-[10px] font-black text-indigo-400 uppercase tracking-wide mb-2"},"Liquidación",-1)),t("p",T,"$"+f(r(i.prestamo.monto_total_pagar)),1)]),e[2]||(e[2]=t("div",{class:"bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10"},[t("p",{class:"text-[10px] font-black text-indigo-400 uppercase tracking-wide mb-2"},"Ahorro de Intereses"),t("p",{class:"text-emerald-400 font-bold"},"$0.00 (Liquidado)")],-1))]),t("div",M,[t("button",{onClick:v,class:"inline-flex items-center px-10 py-5 bg-white text-indigo-900 text-xs font-black uppercase tracking-wide rounded-2xl hover:bg-indigo-50 hover:scale-[1.02] active:scale-95 transition-all shadow-2xl shadow-indigo-500/20"},[...e[3]||(e[3]=[t("svg",{class:"w-4 h-4 mr-3",fill:"none",stroke:"currentColor",viewBox:"0 0 24 24"},[t("path",{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2",d:"M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"})],-1),h(" Imprimir Constancia Oficial ",-1)])]),b(x(E),{href:`/prestamos/${i.prestamo.id}`,class:"px-8 py-5 bg-indigo-900/50 border border-indigo-400/20 text-indigo-100 text-xs font-black uppercase tracking-wide rounded-2xl hover:bg-indigo-900 transition-all"},{default:q(()=>[...e[4]||(e[4]=[h(" Regresar al Préstamo ",-1)])]),_:1},8,["href"])])])])])])],64))}});export{W as default};
