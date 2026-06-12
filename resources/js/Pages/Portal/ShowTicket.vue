<template>
  <ClientLayout>
    <div class="w-full space-y-6">
      
      <!-- Encabezado Ticket -->
      <div class="bg-white shadow overflow-hidden sm:rounded">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-start">
          <div>
            <h3 class="text-lg leading-6 font-medium text-slate-900">
              #{{ ticket.numero }} - {{ ticket.titulo }}
            </h3>
            <p v-if="ticket.folio_externo" class="mt-1 text-sm font-bold text-amber-600 dark:text-amber-400">
              📋 Folio del cliente: {{ ticket.folio_externo }}
            </p>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">
              Categoría: {{ ticket.categoria?.nombre || 'General' }}
            </p>
          </div>
          <span
            class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full"
            :class="{
              'bg-emerald-100 text-emerald-800 dark:text-emerald-200': ticket.estado === 'resuelto',
              'bg-sky-100 text-sky-800 dark:text-sky-200': ticket.estado === 'abierto',
              'bg-brand-100 text-brand-800 dark:text-amber-200': ticket.estado === 'en_progreso',
              'bg-slate-100 text-slate-800': ticket.estado === 'cerrado',
            }"
          >
            {{ ticket.estado }}
          </span>
        </div>
        <div class="border-t border-slate-200 px-4 py-5 sm:px-6">
           <div class="prose max-w-none text-slate-700 whitespace-pre-line">
              {{ ticket.descripcion }}
           </div>
           <!-- Fotos del ticket -->
           <div v-if="ticket.archivos?.length > 0" class="mt-4 grid grid-cols-3 gap-2">
             <a v-for="(f, i) in ticket.archivos" :key="i" :href="'/storage/' + f" target="_blank" class="block aspect-square rounded-lg overflow-hidden border border-slate-200 hover:opacity-80 transition-opacity">
               <img :src="'/storage/' + f" class="w-full h-full object-cover" />
             </a>
           </div>
        </div>
      </div>

      <!-- Timeline / Comentarios -->
      <div class="bg-white shadow sm:rounded">
          <div class="px-4 py-5 sm:p-6">
              <h4 class="text-base font-medium text-slate-900 mb-4">Actividad</h4>
              
              <ul role="list" class="-mb-8">
                  <li v-for="(comment, commentIdx) in ticket.comentarios" :key="comment.id">
                      <div class="relative pb-8">
                          <span v-if="commentIdx !== ticket.comentarios.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true" />
                          <div class="relative flex space-x-3">
                              <div>
                                  <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white"
                                    :class="comment.es_interno ? 'bg-slate-400' : 'bg-brand-500'">
                                       <!-- Icono simple -->
                                       <span class="text-white text-xs font-bold">
                                         {{ comment.user_id ? 'S' : 'C' }}
                                       </span>
                                  </span>
                              </div>
                              <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                  <div>
                                      <p class="text-sm text-slate-500">
                                          <span class="font-medium text-slate-900">
                                              {{ comment.user ? comment.user.name : 'Tú' }}
                                          </span>
                                      </p>
                                       <div class="mt-1 text-sm text-slate-700 whitespace-pre-wrap">
                                         {{ comment.comentario }}
                                       </div>
                                       <div v-if="comment.metadata?.archivos?.length > 0" class="mt-2 grid grid-cols-3 gap-2">
                                         <a v-for="(f, i) in comment.metadata.archivos" :key="i" :href="'/storage/' + f" target="_blank" class="block aspect-square rounded-lg overflow-hidden border border-slate-200 hover:opacity-80 transition-opacity">
                                           <img :src="'/storage/' + f" class="w-full h-full object-cover" />
                                         </a>
                                       </div>
                                  </div>
                                  <div class="text-right text-sm whitespace-nowrap text-slate-500">
                                      {{ formatDateTime(comment.created_at) }}
                                  </div>
                              </div>
                          </div>
                      </div>
                  </li>
                  <li v-if="!ticket.comentarios || ticket.comentarios.length === 0" class="text-center text-slate-500 py-4">
                      No hay comentarios aún.
                  </li>
              </ul>
              
              <!-- Formulario de respuesta -->
              <div class="mt-8 border-t border-slate-200 pt-6" v-if="ticket.estado !== 'cerrado'">
                 <h4 class="text-sm font-medium text-slate-900 mb-2">Agregar respuesta</h4>
                 <form @submit.prevent="submitComment">
                    <div class="mt-1">
                      <textarea
                        id="comentario"
                        name="comentario"
                        rows="3"
                        v-model="commentForm.contenido"
                        class="shadow-sm focus:ring-brand-500 focus:border-brand-500 block w-full sm:text-sm border-slate-300 rounded-xl"
                        placeholder="Escribe tu mensaje aquí..."
                        required
                      ></textarea>
                    </div>
                    <div class="mt-3 flex justify-end">
                      <button
                        type="submit"
                        :disabled="commentForm.processing"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50"
                      >
                        Enviar Respuesta
                      </button>
                    </div>
                 </form>
              </div>
          </div>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { useForm } from '@inertiajs/vue3';
import ClientLayout from './Layout/ClientLayout.vue';
const { formatDateTime } = useFormatters();

const props = defineProps({
  ticket: Object,
});

const commentForm = useForm({
  contenido: '',
});

const submitComment = () => {
    commentForm.post(route('portal.tickets.comments.store', props.ticket.id), {
        onSuccess: () => commentForm.reset(),
        preserveScroll: true,
    });
};
</script>
