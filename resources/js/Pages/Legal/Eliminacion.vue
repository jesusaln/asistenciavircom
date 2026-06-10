<template>
  <Head title="Eliminación de Datos de Usuario" />
  <div class="min-h-screen bg-[var(--ui-surface)] py-12 px-6 sm:px-8">
    <div class="max-w-4xl mx-auto bg-white p-10 rounded-[2.5rem] shadow-xl border border-slate-200/50">
      <div class="flex items-center gap-4 mb-8">
        <div class="w-10 h-10 bg-slate-900 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-slate-900/20">
          <FontAwesomeIcon icon="user-slash" class="text-xl" />
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight uppercase">Eliminación de Datos</h1>
      </div>

      <div class="prose prose-slate max-w-none space-y-6 text-slate-500 font-medium">
        <p class="text-slate-400 text-xs font-black uppercase tracking-wide">Última actualización: 17 de marzo, 2026</p>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">Instrucciones para la Eliminación de Datos de Usuario</h2>
          <p>En {{ empresa.nombre }}, valoramos su privacidad y le proporcionamos el control total sobre sus datos personales. De acuerdo con las políticas de Meta y Facebook, a continuación detallamos los pasos para solicitar la eliminación de su información relacionada con nuestra aplicación.</p>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">1. Tipos de Datos que Eliminamos</h2>
          <p>Al solicitar la eliminación, procederemos a borrar de nuestros registros:</p>
          <ul class="list-disc ml-6 space-y-1">
            <li>Nombre y datos de contacto (teléfono, email).</li>
            <li>Historial de interacciones por WhatsApp.</li>
            <li>Identificadores únicos vinculados a su cuenta de Facebook/Meta si los hubiera.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">2. Cómo Solicitar la Eliminación</h2>
          <p>Usted puede ejercer su derecho de eliminación de las siguientes maneras:</p>
          <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 space-y-3">
            <p><strong>A. Por Correo Electrónico:</strong> Envíe una solicitud a <span class="text-slate-900">{{ empresa.email }}</span> con el asunto "Solicitud de Eliminación de Datos", incluyendo su nombre y el número de teléfono que desea desvincular.</p>
            <p><strong>B. Vía WhatsApp:</strong> Responda a cualquiera de nuestros mensajes con la palabra "ELIMINAR MI INFO". Un asesor confirmará su solicitud en un plazo no mayor a 48 horas.</p>
          </div>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">3. Plazo de Respuesta</h2>
          <p>Una vez recibida la solicitud, procesaremos la eliminación definitiva de sus datos de nuestra base de datos activa en un plazo máximo de 72 horas hábiles.</p>
        </section>

        <section>
          <h2 class="text-lg font-black text-slate-900 uppercase tracking-wider mb-2">4. Aplicaciones de Facebook (Meta)</h2>
          <p>Si desea eliminar los permisos de nuestra aplicación directamente desde su cuenta de Facebook:</p>
          <ol class="list-decimal ml-6 space-y-1">
            <li>Vaya a la Configuración y privacidad de su cuenta de Facebook.</li>
            <li>Haga clic en Configuración.</li>
            <li>Busque "Apps y sitios web".</li>
            <li>Busque "{{ empresa.nombre }}" (o Marketing Climas del Desierto) y haga clic en Eliminar.</li>
          </ol>
        </section>
      </div>

      <div class="mt-12 pt-8 border-t border-slate-100 flex justify-center">
        <a href="/" class="text-xs font-black text-brand-500 uppercase tracking-wide hover:underline">← Volver al sitio principal</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faUserSlash } from '@fortawesome/free-solid-svg-icons';

library.add(faUserSlash);

defineProps({
    empresa: Object
});
</script>
