<template>
  <Head title="Aviso de Privacidad" />
  <div class="min-h-screen bg-slate-50 py-12 px-6 sm:px-8">
    <div class="max-w-4xl mx-auto bg-white p-10 rounded-[2.5rem] shadow-xl border border-slate-200/50">
      <div class="flex items-center gap-4 mb-8">
        <div class="w-12 h-12 bg-amber-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-amber-500/20">
          <FontAwesomeIcon icon="shield-alt" class="text-xl" />
        </div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight uppercase">Aviso de Privacidad</h1>
      </div>

      <div class="prose prose-slate max-w-none space-y-6 text-slate-600 font-medium">
        <p class="text-slate-400 text-xs font-black uppercase tracking-widest">Última actualización: 17 de marzo, 2026</p>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">1. Identidad y Domicilio del Responsable</h2>
          <p>{{ empresa.nombre }} (en adelante, "La Empresa"), con domicilio en {{ empresa.direccion }} {{ empresa.ciudad }}, {{ empresa.estado }}, es responsable del tratamiento de sus datos personales.</p>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">2. Datos Personales que Recabamos</h2>
          <p>Para las finalidades señaladas en este aviso, podemos recabar sus datos de distintas formas: cuando usted nos los proporciona directamente (nombre, teléfono, correo electrónico, domicilio de servicio) o a través de nuestro sitio web.</p>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">3. Finalidades del Tratamiento</h2>
          <p>Sus datos personales serán utilizados para:</p>
          <ul class="list-disc ml-6 space-y-1">
            <li>Proveer los servicios de climatización y mantenimiento solicitados.</li>
            <li>Envío de recordatorios de servicio por WhatsApp o SMS.</li>
            <li>Notificar sobre cambios en nuestros servicios.</li>
            <li>Atención a clientes y seguimiento de garantías.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">4. Transferencia de Datos</h2>
          <p>Le informamos que sus datos personales no son compartidos con terceros ajenos a la operación técnica necesaria para la prestación del servicio (como proveedores de API de WhatsApp aprobados por Meta), salvo por requerimiento legal de autoridad competente.</p>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">5. Derechos ARCO</h2>
          <p>Usted tiene derecho a conocer qué datos personales tenemos de usted, para qué los utilizamos y las condiciones del uso que les damos (Acceso). Asimismo, es su derecho solicitar la corrección de su información personal en caso de que esté desactualizada, sea inexacta o incompleta (Rectificación); que la eliminemos de nuestros registros o bases de datos cuando considere que la misma no está siendo utilizada conforme a los principios, deberes y obligaciones previstas en la normativa (Cancelación); así como oponerse al uso de sus datos personales para fines específicos (Oposición).</p>
        </section>
      </div>

      <div class="mt-12 pt-8 border-t border-slate-100 flex justify-center">
        <a href="/" class="text-xs font-black text-amber-500 uppercase tracking-widest hover:underline">← Volver al sitio principal</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faShieldAlt } from '@fortawesome/free-solid-svg-icons';

library.add(faShieldAlt);

defineProps({
    empresa: Object
});
</script>
