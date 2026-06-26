<template>
  <ClientLayout>
    <div class="max-w-4xl mx-auto space-y-6 px-4 py-6">

      <!-- Header Card -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ticket</span>
                <span class="text-sm font-mono font-bold text-brand-600 dark:text-brand-400">#{{ ticket.numero || ticket.id }}</span>
              </div>
              <h2 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">{{ ticket.titulo }}</h2>
              <p v-if="ticket.folio_externo" class="mt-1 text-sm text-amber-600 dark:text-amber-400 font-medium flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                Folio: {{ ticket.folio_externo }}
              </p>
            </div>
            <div class="flex flex-col items-end gap-2 shrink-0">
              <span class="px-4 py-1.5 text-xs font-bold rounded-full uppercase tracking-wide"
                :class="{
                  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300': ticket.estado === 'resuelto',
                  'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': ticket.estado === 'abierto',
                  'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300': ticket.estado === 'en_progreso',
                  'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300': ticket.estado === 'cerrado' || ticket.estado === 'cancelado',
                }"
              >
                {{ ticket.estado === 'en_progreso' ? 'En Progreso' : ticket.estado }}
              </span>
              <span v-if="ticket.tipo_servicio === 'costo'" class="px-3 py-1 text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 rounded-full uppercase tracking-wide">💰 Con Cargo</span>
              <span v-else-if="ticket.poliza" class="px-3 py-1 text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 rounded-full uppercase tracking-wide">🛡️ Póliza</span>
            </div>
          </div>

          <div class="flex flex-wrap gap-4 mt-4 text-sm text-slate-500 dark:text-slate-400">
            <span v-if="ticket.categoria" class="flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
              {{ ticket.categoria.nombre }}
            </span>
            <span class="flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              {{ formatDate(ticket.created_at) }}
            </span>
            <span v-if="ticket.asignado" class="flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
              {{ ticket.asignado.name }}
            </span>
          </div>
        </div>
      </div>

      <!-- Description Card -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
            Descripción
          </h3>
          <p class="text-slate-600 dark:text-slate-300 whitespace-pre-line text-sm leading-relaxed">{{ ticket.descripcion || 'Sin descripción' }}</p>
        </div>
      </div>

      <!-- Photos Gallery -->
      <div v-if="allImages.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            Evidencia ({{ allImages.length }})
          </h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <div v-for="(img, i) in allImages" :key="i" class="group relative aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 cursor-pointer" @click="openLightbox(i)">
              <img :src="getImageUrl(img)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
              <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Extra Services / Sale Card -->
      <div v-if="ticket.venta" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" /></svg>
            Servicios Facturados
          </h3>
          <div class="space-y-2">
            <div v-for="item in ticket.venta.items" :key="item.id" class="flex items-center justify-between py-2 px-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ item.ventable?.nombre || item.nombre || 'Servicio' }}</p>
                <p class="text-xs text-slate-500">Cant: {{ item.cantidad }} x ${{ formatNumber(item.precio) }}</p>
              </div>
              <span class="text-sm font-bold text-slate-900 dark:text-white">${{ formatNumber(item.subtotal) }}</span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-slate-200 dark:border-slate-700">
              <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Total</span>
              <span class="text-lg font-black text-brand-600 dark:text-brand-400">${{ formatNumber(ticket.venta.total) }}</span>
            </div>
          </div>
          <p class="mt-2 text-xs text-slate-500">Estado: <span class="font-medium text-slate-700 dark:text-slate-300 capitalize">{{ ticket.venta.estado }}</span></p>
        </div>
      </div>

      <!-- Timeline Card -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Actividad
          </h3>
          
          <div v-if="ticket.comentarios && ticket.comentarios.length > 0" class="relative">
            <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-slate-200 dark:bg-slate-700"></div>
            <div v-for="(comment, idx) in ticket.comentarios" :key="comment.id" class="relative pl-14 pb-8 last:pb-0">
              <div class="absolute left-3.5 top-1 w-4 h-4 rounded-full border-2 border-white dark:border-slate-800"
                :class="comment.user ? 'bg-brand-500' : 'bg-slate-400'">
              </div>
              <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700">
                <div class="flex items-center justify-between gap-4 mb-2">
                  <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ comment.user?.name || 'Cliente' }}</span>
                  <span class="text-xs text-slate-400">{{ formatDateTime(comment.created_at) }}</span>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-wrap">{{ comment.contenido }}</p>
                <div v-if="comment.metadata?.archivos?.length > 0" class="mt-3 grid grid-cols-2 gap-2">
                  <a v-for="(f, i) in comment.metadata.archivos" :key="i" :href="'/storage/' + f" target="_blank" class="block aspect-video rounded-lg overflow-hidden border border-slate-200 dark:border-slate-600 hover:opacity-80">
                    <img :src="'/storage/' + f" class="w-full h-full object-cover" loading="lazy" />
                  </a>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-slate-400">
            <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            <p class="text-sm font-medium">No hay actividad registrada</p>
          </div>
        </div>

        <!-- Reply Form -->
        <div v-if="ticket.estado !== 'cerrado' && ticket.estado !== 'cancelado'" class="border-t border-slate-200 dark:border-slate-700 p-6">
          <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Agregar respuesta</h4>
          <form @submit.prevent="submitComment">
            <textarea v-model="commentForm.contenido" rows="3"
              class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 dark:bg-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all resize-none"
              placeholder="Escribe tu mensaje aquí..."></textarea>
            <div class="mt-3">
              <label class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 cursor-pointer hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Adjuntar imágenes</span>
                <input id="comment_archivos" type="file" multiple accept="image/png,image/jpeg,image/jpg,image/webp" class="hidden" @change="handleCommentFileChange" />
              </label>
              <div v-if="commentArchivosPreview.length > 0" class="mt-2 flex flex-wrap gap-2">
                <div v-for="(file, i) in commentArchivosPreview" :key="i" class="relative group w-16 h-16 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600">
                  <img :src="file.url" class="w-full h-full object-cover" />
                  <button type="button" @click="removeCommentArchivo(i)" class="absolute top-0.5 right-0.5 w-4 h-4 bg-red-500 text-white rounded-full flex items-center justify-center text-[8px] opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
                </div>
              </div>
            </div>
            <div class="mt-3 flex justify-end">
              <button type="submit" :disabled="commentForm.processing"
                class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl transition-all disabled:opacity-50 flex items-center gap-2">
                <svg v-if="commentForm.processing" class="w-4 h-4 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                {{ commentForm.processing ? 'Enviando...' : 'Enviar Respuesta' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Lightbox -->
      <Teleport to="body">
        <div v-if="lightboxOpen" class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4" @click="closeLightbox">
          <button class="absolute top-4 right-4 text-white/70 hover:text-white z-10" @click="closeLightbox">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
          <button v-if="lightboxIndex > 0" class="absolute left-4 text-white/70 hover:text-white z-10" @click="lightboxIndex--">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
          </button>
          <button v-if="lightboxIndex < allImages.length - 1" class="absolute right-4 text-white/70 hover:text-white z-10" @click="lightboxIndex++">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
          </button>
          <img :src="getImageUrl(allImages[lightboxIndex])" class="max-h-[90vh] max-w-[90vw] object-contain rounded-lg" @click.stop />
          <div class="absolute bottom-4 text-white/60 text-xs">{{ lightboxIndex + 1 }} / {{ allImages.length }}</div>
        </div>
      </Teleport>
    </div>
  </ClientLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useFormatters } from '@/Composables/useFormatters';
import { useForm } from '@inertiajs/vue3';
import ClientLayout from './Layout/ClientLayout.vue';
const { formatDateTime } = useFormatters();

const props = defineProps({
  ticket: Object,
});

const commentForm = useForm({ contenido: '', archivos: [] });
const commentArchivosPreview = ref([]);

const handleCommentFileChange = (e) => {
  commentForm.archivos = Array.from(e.target.files);
  commentArchivosPreview.value = Array.from(e.target.files).map(f => ({
    name: f.name,
    size: f.size,
    url: URL.createObjectURL(f),
  }));
};

const removeCommentArchivo = (index) => {
  const newFiles = Array.from(commentForm.archivos).filter((_, i) => i !== index);
  const dt = new DataTransfer();
  newFiles.forEach(f => dt.items.add(f));
  commentForm.archivos = dt.files;
  commentArchivosPreview.value = commentArchivosPreview.value.filter((_, i) => i !== index);
  const fileInput = document.getElementById('comment_archivos');
  if (fileInput) fileInput.files = dt.files;
};

const submitComment = () => {
    commentForm.post(route('portal.tickets.comments.store', props.ticket.id), {
        onSuccess: () => {
            commentForm.reset();
            commentArchivosPreview.value = [];
        },
        preserveScroll: true,
    });
};

// Collect all images from ticket + comments
const allImages = computed(() => {
  const imgs = [];
  if (props.ticket.archivos && Array.isArray(props.ticket.archivos)) {
    props.ticket.archivos.forEach(f => imgs.push(f));
  }
  if (props.ticket.comentarios) {
    props.ticket.comentarios.forEach(c => {
      if (c.metadata?.archivos) {
        c.metadata.archivos.forEach(f => imgs.push(f));
      }
    });
  }
  return imgs;
});

const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

const openLightbox = (index) => {
  lightboxIndex.value = index;
  lightboxOpen.value = true;
};

const closeLightbox = () => {
  lightboxOpen.value = false;
};

const getImageUrl = (path) => {
  if (!path) return '';
  if (path.startsWith('http')) return path;
  if (path.startsWith('/storage/')) return path;
  return '/storage/' + path;
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' }) : '';

const formatNumber = (n) => {
  if (n === null || n === undefined) return '0.00';
  return Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>
