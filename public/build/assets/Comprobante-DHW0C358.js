import{C as $,g as c,o as p,e as C,d as t,j as z,p as E,i as u,k as m,w as M,q as N,t as r,l as R}from"./vue-core-CVM6bgH_.js";import{A as O}from"./AppLayout-BydQq2zP.js";import{N as q}from"./vendor-CmAdhKzL.js";import"./utils-C1cdQfIJ.js";import"./icons-Dg70kTOZ.js";import"./charts-CqYeoq9j.js";import"./_plugin-vue_export-helper-DlAUqK2U.js";import"./Swal-NX-Qpw8F.js";import"./ToastContainer-BeGZydpT.js";import"./Modal-CcrTn42X.js";import"./useDarkMode-CiivEy3X.js";const W={class:"min-h-screen bg-[var(--ui-surface)] py-12 px-4 selection:bg-brand-500/30"},I={class:"max-w-4xl mx-auto"},L={class:"bg-slate-900 border border-white/10 rounded-3xl p-8 shadow-2xl overflow-hidden relative group"},T={class:"relative flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12"},V={class:"flex flex-col items-stretch sm:items-end gap-2"},X={class:"flex flex-wrap items-center gap-3 justify-end"},B=["href"],G=["href"],U={key:0,class:"text-[10px] text-slate-500 max-w-xl sm:text-right leading-relaxed"},Y={class:"grid grid-cols-1 md:grid-cols-2 gap-8 mb-8"},H={class:"bg-slate-950/50 rounded-2xl p-6 border border-white/5"},J={class:"space-y-6"},K={class:"flex justify-between"},Q={class:"text-xl font-black text-emerald-400 tracking-tighter"},Z={class:"flex justify-between"},ee={class:"text-sm font-bold text-white"},te={class:"flex justify-between"},ae={class:"text-sm font-bold text-white capitalize"},oe={class:"bg-slate-950/50 rounded-2xl p-6 border border-white/5"},ie={class:"space-y-6"},se={class:"flex justify-between text-xs"},re={class:"text-white font-bold"},le={class:"flex justify-between text-xs"},ne={class:"text-white font-bold"},de={class:"flex justify-between text-xs"},ce={class:"text-white font-bold"},pe={class:"bg-blue-600/5 border border-blue-500/10 rounded-2xl p-4 flex items-center gap-4"},me={class:"text-xs text-slate-400 font-medium"},xe={class:"text-white font-black"},ue={key:0,class:"mt-8 pt-8 border-t border-white/10 flex flex-col items-center text-center"},ge=["src"],fe={class:"w-56 border-t border-white/25 pt-2 mt-1"},be={class:"text-[10px] font-bold text-slate-500 uppercase tracking-wide leading-snug"},Ae=Object.assign({layout:O},{__name:"Comprobante",props:{historial:{type:Object,required:!0},pago:{type:Object,required:!0},prestamo:{type:Object,required:!0},cliente:{type:Object,required:!0},empresa:{type:Object,required:!0},fecha_actual:{type:String,required:!0},monto_letras:{type:String,required:!0},url_descarga_pdf:{type:String,default:""}},setup(i){const a=i;new q({duration:4e3,position:{x:"right",y:"top"}});const l=o=>new Intl.NumberFormat("es-MX",{style:"currency",currency:"MXN"}).format(o),x=o=>o?new Date(o).toLocaleDateString("es-MX",{day:"2-digit",month:"long",year:"numeric"}):"N/A",j=o=>{const e=(o||"otro").replace(/_/g," ");return e.charAt(0).toUpperCase()+e.slice(1)},D=o=>{if(!o)return"";const e=String(o).replace(/\D/g,"");return e.length===10?`52${e}`:e.length===12&&e.startsWith("52")||e.length===13&&e.startsWith("521")||e.length>=10&&e.length<=15?e:""},A=$(()=>{var d,n;const o=`REC-${String(a.historial.id).padStart(6,"0")}`,e=((d=a.empresa)==null?void 0:d.razon_social)||"Empresa",s=["*Comprobante de abono a préstamo*","",`Folio: ${o}`,`Cliente: ${((n=a.cliente)==null?void 0:n.nombre_razon_social)||"—"}`,`Monto: ${l(a.historial.monto_pagado)}`,`Fecha: ${x(a.historial.fecha_pago)}`,`Método: ${j(a.historial.metodo_pago)}`,`Préstamo: #${a.prestamo.folio||a.prestamo.id} · Cuota ${a.pago.numero_pago}`,"",`_${e}_`];return a.url_descarga_pdf&&s.push("","📄 *Descarga el PDF del comprobante aquí:*",a.url_descarga_pdf),s.join(`
`)}),F=$(()=>{var s;const o=encodeURIComponent(A.value),e=D((s=a.cliente)==null?void 0:s.telefono);return e?`https://web.whatsapp.com/send?phone=${e}&text=${o}`:`https://web.whatsapp.com/send?text=${o}`}),P=()=>{const o=S(),e=window.open("","_blank");e.document.write(o),e.document.close(),e.onload=()=>{setTimeout(()=>{e.focus(),e.print()},300)}},S=()=>{var n,g,f,b,v,h,w,y,_,k;const o=`REC-${String(a.historial.id).padStart(6,"0")}`,e=((n=a.empresa)==null?void 0:n.razon_social)||"CLIMAS DEL DESIERTO",s=((g=a.empresa)==null?void 0:g.rfc)||"",d=((f=a.empresa)==null?void 0:f.direccion_completa)||"";return`
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de Pago ${o}</title>
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
                <div style="font-size: 10px; opacity: 0.8; margin-top: 5px;">${e} | RFC: ${s}</div>
            </div>
            <div class="folio">
                <div style="font-size: 10px; opacity: 0.8;">FOLIO DE RECEPCIÓN</div>
                <div class="folio-num">${o}</div>
            </div>
        </div>

        <div class="stamp">PAGADO</div>

        <div class="section">
            <div class="section-title">Datos del Cliente</div>
            <div class="grid">
                <div>
                    <div class="data-label">Cliente / Deudor</div>
                    <div class="data-value">${(b=a.cliente)==null?void 0:b.nombre_razon_social}</div>
                </div>
                <div>
                    <div class="data-label">RFC / Identificación</div>
                    <div class="data-value">${((v=a.cliente)==null?void 0:v.rfc)||"XAXX010101000"}</div>
                </div>
            </div>
            <div style="margin-top: 8px;">
                <div class="data-label">Domicilio</div>
                <div class="data-value" style="font-size: 12px;">${((h=a.cliente)==null?void 0:h.direccion_completa)||"Sin domicilio registrado"}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Detalle del Movimiento</div>
            <div class="grid">
                <div>
                    <div class="data-label">Préstamo Relacionado</div>
                    <div class="data-value">Folio: ${a.prestamo.folio||a.prestamo.id}</div>
                </div>
                <div>
                    <div class="data-label">Número de Cuota</div>
                    <div class="data-value">Abono a la cuota #${a.pago.numero_pago} de ${a.prestamo.numero_pagos}</div>
                </div>
                <div>
                    <div class="data-label">Fecha de Operación</div>
                    <div class="data-value">${x(a.historial.fecha_pago)}</div>
                </div>
                <div>
                    <div class="data-label">Método de Pago</div>
                    <div class="data-value" style="text-transform: capitalize;">${(a.historial.metodo_pago||"otro").replace("_"," ")}</div>
                </div>
            </div>
            
            <div class="amount-box">
                <div class="amount-val">${l(a.historial.monto_pagado)}</div>
                <div class="amount-text">${a.monto_letras}</div>
            </div>
        </div>

        <div class="section" style="border-bottom: none;">
            <div class="section-title">Estado del Crédito</div>
            <div class="grid" style="grid-template-columns: 1fr 1fr 1fr;">
                <div style="text-align: center;">
                    <div class="data-label">Amortizado</div>
                    <div class="data-value" style="color: #059669;">${l(a.prestamo.monto_pagado)}</div>
                </div>
                <div style="text-align: center;">
                    <div class="data-label">Saldo Pendiente</div>
                    <div class="data-value" style="color: #dc2626;">${l(a.prestamo.monto_pendiente)}</div>
                </div>
                <div style="text-align: center;">
                    <div class="data-label">Progreso</div>
                    <div class="data-value">${a.prestamo.progreso}%</div>
                </div>
            </div>
        </div>

        <div class="signature-wrap">
            ${(w=a.empresa)!=null&&w.firma_digital?`<img src="${a.empresa.firma_digital}" alt="Firma" />
                 <div class="sig-line">Firma autorizada — ${(((y=a.empresa)==null?void 0:y.razon_social)||((_=a.empresa)==null?void 0:_.nombre_empresa)||"Empresa").replace(/</g,"")}</div>`:'<div class="sig-line">Sello y firma del cajero</div>'}
        </div>

        <div class="footer">
            Este comprobante es un acuse de recibo de fondos y no constituye una liberación total del crédito hasta su liquidación absoluta.<br>
            ${d} | Tel: ${((k=a.empresa)==null?void 0:k.telefono)||""} | Generado: ${new Date().toLocaleString()}
        </div>
    </div>
</body>
</html>
  `};return(o,e)=>{var s;return p(),c(R,null,[C(z(E),{title:"Comprobante de Pago"}),t("div",W,[t("div",I,[t("div",L,[e[15]||(e[15]=t("div",{class:"absolute -top-24 -right-24 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl transition-all group-hover:bg-blue-600/20"},null,-1)),t("div",T,[e[2]||(e[2]=t("div",null,[t("div",{class:"inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wide bg-brand-500/10 text-emerald-400 border border-emerald-500/20 mb-4"}," Transacción Confirmada "),t("h1",{class:"text-4xl font-black text-white tracking-tighter mb-2"},"Comprobante de Abono"),t("p",{class:"text-slate-400 font-medium"},"Visualización y descarga oficial del recibo de pago.")],-1)),t("div",V,[t("div",X,[t("a",{href:F.value,target:"_blank",rel:"noopener noreferrer",class:"inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#25D366] hover:bg-[#20bd5a] text-white text-xs font-black uppercase tracking-wide rounded-2xl shadow-xl shadow-emerald-900/30 transition-all active:scale-95",title:"Abre WhatsApp Web con el resumen y el enlace para descargar el PDF"},[...e[0]||(e[0]=[t("svg",{class:"w-4 h-4 shrink-0",viewBox:"0 0 24 24",fill:"currentColor","aria-hidden":"true"},[t("path",{d:"M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.123 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"})],-1),m(" WhatsApp ",-1)])],8,B),i.url_descarga_pdf?(p(),c("a",{key:0,href:i.url_descarga_pdf,target:"_blank",rel:"noopener noreferrer",class:"inline-flex items-center gap-2 px-6 py-4 bg-slate-700 hover:bg-slate-600 text-white text-xs font-black uppercase tracking-wide rounded-2xl border border-white/10"}," Descargar PDF ",8,G)):u("",!0),t("button",{type:"button",class:"px-8 py-4 bg-blue-600 hover:bg-slate-500 text-white text-xs font-black uppercase tracking-wide rounded-2xl shadow-xl shadow-blue-600/20 transition-all active:scale-95",onClick:P}," Imprimir Recibo "),C(z(N),{href:`/pagos/${i.pago.id}`,class:"px-6 py-4 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-black uppercase tracking-wide rounded-2xl transition-all"},{default:M(()=>[...e[1]||(e[1]=[m(" Volver ",-1)])]),_:1},8,["href"])]),i.url_descarga_pdf?(p(),c("p",U," El mensaje de WhatsApp incluye un enlace seguro (válido varios días) para que descarguen el mismo PDF desde el navegador, sin entrar al sistema. ")):u("",!0)])]),t("div",Y,[t("div",H,[e[6]||(e[6]=t("h3",{class:"text-[10px] font-black text-slate-500 uppercase tracking-wide mb-4"},"Resumen de Pago",-1)),t("div",J,[t("div",K,[e[3]||(e[3]=t("span",{class:"text-xs text-slate-400"},"Monto Recibido",-1)),t("span",Q,r(l(i.historial.monto_pagado)),1)]),t("div",Z,[e[4]||(e[4]=t("span",{class:"text-xs text-slate-400"},"Fecha",-1)),t("span",ee,r(x(i.historial.fecha_pago)),1)]),t("div",te,[e[5]||(e[5]=t("span",{class:"text-xs text-slate-400"},"Método",-1)),t("span",ae,r((i.historial.metodo_pago||"otro").replace("_"," ")),1)])])]),t("div",oe,[e[10]||(e[10]=t("h3",{class:"text-[10px] font-black text-slate-500 uppercase tracking-wide mb-4"},"Referencia del Crédito",-1)),t("div",ie,[t("div",se,[e[7]||(e[7]=t("span",{class:"text-slate-400"},"Cliente",-1)),t("span",re,r(i.cliente.nombre_razon_social),1)]),t("div",le,[e[8]||(e[8]=t("span",{class:"text-slate-400"},"Préstamo",-1)),t("span",ne,"#"+r(i.prestamo.folio||i.prestamo.id),1)]),t("div",de,[e[9]||(e[9]=t("span",{class:"text-slate-400"},"Cuota",-1)),t("span",ce,"Abono a Cuota #"+r(i.pago.numero_pago),1)])])])]),t("div",pe,[e[14]||(e[14]=t("div",{class:"w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center text-blue-400"},[t("svg",{class:"w-10 h-10",fill:"none",stroke:"currentColor",viewBox:"0 0 24 24"},[t("path",{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2",d:"M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"})])],-1)),t("div",null,[e[13]||(e[13]=t("div",{class:"text-[10px] font-black text-blue-400 uppercase tracking-wide"},"Saldo Actualizado",-1)),t("div",me,[e[11]||(e[11]=m("El saldo pendiente actual es de ",-1)),t("span",xe,r(l(i.prestamo.monto_pendiente)),1),e[12]||(e[12]=m(".",-1))])])]),(s=i.empresa)!=null&&s.firma_digital?(p(),c("div",ue,[t("img",{src:i.empresa.firma_digital,alt:"Firma de la empresa",class:"max-h-28 max-w-xs object-contain mb-1"},null,8,ge),t("div",fe,[t("p",be," Firma autorizada — "+r(i.empresa.razon_social||i.empresa.nombre_empresa),1)])])):u("",!0)])])])],64)}}});export{Ae as default};
