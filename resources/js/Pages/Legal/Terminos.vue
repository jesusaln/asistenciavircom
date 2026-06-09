<template>
  <Head title="Condiciones del Servicio" />
  <div class="min-h-screen bg-slate-50 py-12 px-6 sm:px-8">
    <div class="max-w-4xl mx-auto bg-white p-10 rounded-[2.5rem] shadow-xl border border-slate-200/50">
      <div class="flex items-center gap-4 mb-8">
        <div class="w-12 h-12 bg-rose-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-rose-500/20">
          <FontAwesomeIcon icon="file-signature" class="text-xl" />
        </div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight uppercase">Condiciones del Servicio</h1>
      </div>

      <div class="prose prose-slate max-w-none space-y-6 text-slate-600 font-medium">
        <p class="text-slate-400 text-xs font-black uppercase tracking-widest">Última actualización: 17 de marzo, 2026</p>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">1. Aceptación de las Condiciones</h2>
          <p>Al acceder y utilizar el sitio web {{ empresa.nombre }} (climasdeldesierto.com), usted acepta quedar obligado por las presentes Condiciones del Servicio y el Aviso de Privacidad.</p>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">2. Uso de los Servicios</h2>
          <p>Usted se compromete a utilizar nuestros servicios únicamente para fines lícitos y de forma que no infrinja los derechos de otros, ni restrinja o inhiba el uso de los servicios por parte de terceros.</p>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">3. Notificaciones por WhatsApp / SMS</h2>
          <p>Nuestra plataforma utiliza la API de WhatsApp para el envío de recordatorios de mantenimiento, promociones y atención a clientes. Al proporcionar su número de teléfono y aceptar el envío de estas notificaciones (opt-in), usted consiente en recibir estos mensajes.</p>
          <p>Usted puede darse de baja de estas notificaciones en cualquier momento respondiendo "CANCELAR" o de acuerdo a las directivas que se le envíen.</p>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">4. Limitación de Responsabilidad</h2>
          <p>{{ empresa.nombre }} no será responsable de ningún daño directo, indirecto, incidental o consecuente derivado del uso o la imposibilidad de uso de nuestros servicios web o de las aplicaciones de terceros con las que nos integramos (como Meta / WhatsApp).</p>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">5. Modificaciones</h2>
          <p>Nos reservamos el derecho de modificar o sustituir estas Condiciones en cualquier momento previa actualización en este mismo sitio web.</p>
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
import { faFileSignature } from '@fortawesome/free-solid-svg-icons';

library.add(faFileSignature);

defineProps({
    empresa: Object
});
</script>
