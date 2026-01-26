<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const props = defineProps({
  posts: Array,
  totalUnsubscribed: Number,
})

const notyf = new Notyf()
const sendingTest = ref(null) // ID del post que se está probando
const sendingNewsletter = ref(null)

const deletePost = (post) => {
  if (confirm('¿Estás seguro de eliminar este artículo?')) {
    router.delete(route('admin.blog.destroy', post.id), {
      onSuccess: () => notyf.success('Artículo eliminado correctamente')
    })
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

const sendNewsletter = (post) => {
  if (confirm(`¿Estás seguro de enviar "${post.titulo}" a los clientes?`)) {
    sendingNewsletter.value = post.id
    router.post(route('admin.blog.send-newsletter', post.id), {}, {
      onSuccess: () => {
        notyf.success('Proceso de envío iniciado')
        sendingNewsletter.value = null
      },
      onError: () => {
        sendingNewsletter.value = null
      }
    })
  }
}

const sendTest = (post) => {
  sendingTest.value = post.id
  router.post(route('admin.blog.send-test', post.id), {}, {
    onSuccess: () => {
      notyf.success('Correo de prueba enviado a tu Gmail')
      sendingTest.value = null
    },
    onError: (err) => {
      notyf.error('Error al enviar: ' + (err.message || 'Verifica los logs'))
      sendingTest.value = null
    }
  })
}
</script>

<template>
  <Head title="Gestión de Blog" />

  <div class="p-6 min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-500">
    <div class="max-w-full lg:max-w-7xl mx-auto">
      <!-- Header Area -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
        <div class="flex items-center gap-5">
           <div class="w-14 h-14 rounded-[1.25rem] bg-[var(--color-primary)] text-white flex items-center justify-center text-2xl shadow-xl shadow-[var(--color-primary)]/20">
             <FontAwesomeIcon icon="blog" />
           </div>
           <div>
             <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">Gestión de Blog</h1>
             <p class="text-slate-500 dark:text-slate-400 font-medium text-sm mt-1">Comparte noticias y conocimiento con tus clientes.</p>
           </div>
        </div>
        <div class="flex items-center gap-3">
          <Link :href="route('admin.blog.create')" class="bg-[var(--color-primary)] text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-[var(--color-primary)]/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-3">
              <FontAwesomeIcon icon="plus" />
              Nueva Entrada
          </Link>
        </div>
      </div>

      <!-- Quick Stats Bar -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div v-for="stat in [
          { label: 'Total Posts', value: posts.length, icon: 'newspaper', color: 'blue' },
          { label: 'Publicados', value: posts.filter(p => p.status === 'published').length, icon: 'check-circle', color: 'emerald' },
          { label: 'Borradores', value: posts.filter(p => p.status === 'draft').length, icon: 'edit', color: 'amber' },
          { label: 'Vistas Netas', value: posts.reduce((acc, p) => acc + (p.visitas || 0), 0), icon: 'eye', color: 'purple' },
          { label: 'Bajas Newsletter', value: totalUnsubscribed, icon: 'user-minus', color: 'red', link: 'admin.blog.unsubscribed' }
        ]" :key="stat.label" 
        >
          <Link v-if="stat.link" :href="route(stat.link)" class="bg-white dark:bg-slate-900 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-6 w-full hover:border-red-500 transition-all">
            <div :class="`w-12 h-12 rounded-2xl bg-${stat.color}-500/10 text-${stat.color}-500 flex items-center justify-center text-xl`">
                <FontAwesomeIcon :icon="stat.icon" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ stat.label }}</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stat.value }}</p>
            </div>
          </Link>
          <div v-else class="bg-white dark:bg-slate-900 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-6">
            <div :class="`w-12 h-12 rounded-2xl bg-${stat.color}-500/10 text-${stat.color}-500 flex items-center justify-center text-xl`">
                <FontAwesomeIcon :icon="stat.icon" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ stat.label }}</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stat.value }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Table Container -->
      <div class="bg-white dark:bg-slate-900 rounded-[3rem] border border-slate-100 dark:border-slate-800 shadow-xl overflow-hidden backdrop-blur-3xl transition-colors">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Contenido del Artículo</th>
                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Estado & Canal</th>
                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Impacto</th>
                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-right">Control</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
              <tr v-for="post in posts" :key="post.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all duration-300">
                <td class="px-8 py-7">
                  <div class="flex items-center gap-5">
                    <div class="w-20 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 overflow-hidden flex-shrink-0 shadow-sm transition-transform group-hover:scale-105">
                      <img v-if="post.imagen_portada_url" :src="post.imagen_portada_url" class="w-full h-full object-cover">
                      <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                        <FontAwesomeIcon icon="image" size="lg" />
                      </div>
                    </div>
                    <div>
                      <h3 class="font-bold text-slate-900 dark:text-white group-hover:text-[var(--color-primary)] transition-colors line-clamp-1 text-lg leading-tight">{{ post.titulo }}</h3>
                      <div class="flex items-center gap-3 mt-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ post.categoria || 'Sin Categoría' }}</span>
                        <span class="w-1 h-1 rounded-full bg-slate-200 dark:bg-slate-700"></span>
                        <span class="text-[10px] font-medium text-slate-400 italic">Publicado: {{ formatDate(post.publicado_at || post.created_at) }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                
                <td class="px-8 py-7">
                  <div class="flex flex-col gap-3">
                    <span :class="[
                      'px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border w-fit',
                      post.status === 'published' 
                        ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' 
                        : 'bg-slate-500/10 text-slate-500 border-slate-500/20'
                    ]">
                      {{ post.status === 'published' ? 'En Vivo' : 'Borrador' }}
                    </span>
                    
                    <!-- Stats de Newsletter -->
                    <div v-if="post.newsletter_stats && post.newsletter_stats.enviados > 0" class="flex flex-col gap-1.5 border-t border-slate-50 dark:border-slate-800 pt-2 mt-1">
                        <Link :href="route('admin.blog.stats', post.id)" class="hover:opacity-80 transition-all">
                            <div class="flex items-center justify-between gap-4">
                               <span class="text-[9px] font-black uppercase text-slate-400">Abiertos:</span>
                               <span class="text-[10px] font-bold text-indigo-500">{{ post.newsletter_stats.abiertos }} / {{ post.newsletter_stats.enviados }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                               <span class="text-[9px] font-black uppercase text-slate-400">Clics:</span>
                               <span class="text-[10px] font-bold text-emerald-500">{{ post.newsletter_stats.clics }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-t border-slate-50 dark:border-slate-800 pt-1 mt-1">
                               <span class="text-[9px] font-black uppercase text-orange-500 flex items-center gap-1">
                                   <FontAwesomeIcon icon="fire" />
                                   INTERÉS:
                               </span>
                               <span class="text-[10px] font-black text-orange-600">{{ post.newsletter_stats.interes }}</span>
                            </div>
                        </Link>
                    </div>
                    
                    <div v-else class="flex items-center gap-2">
                       <div :class="['w-2 h-2 rounded-full', post.newsletter_enviado_at ? 'bg-indigo-500 shadow-[0_0_8px_indigo]' : 'bg-slate-300']"></div>
                       <span class="text-[10px] font-black uppercase tracking-widest" :class="post.newsletter_enviado_at ? 'text-indigo-500' : 'text-slate-400'">
                         {{ post.newsletter_enviado_at ? 'Canal Mail: Ok' : 'Pendiente Mail' }}
                       </span>
                    </div>
                  </div>
                </td>

                <td class="px-8 py-7">
                   <div class="flex items-center gap-3">
                      <div class="text-center">
                        <p class="text-xl font-black text-slate-900 dark:text-white leading-none">{{ post.visitas }}</p>
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mt-1">Interés</p>
                      </div>
                      <div class="w-px h-8 bg-slate-100 dark:bg-slate-800"></div>
                      <div class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:text-[var(--color-primary)] transition-colors">
                        <FontAwesomeIcon :icon="post.visitas > 100 ? 'fire' : 'chart-line'" />
                      </div>
                   </div>
                </td>

                <td class="px-8 py-7">
                  <div class="flex items-center justify-end gap-2">
                    <!-- Test Email -->
                    <button @click="sendTest(post)" 
                            :disabled="sendingTest === post.id"
                            title="Enviar Prueba de Newsletter"
                            class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 hover:bg-amber-500 hover:text-white transition-all transform hover:scale-110 active:scale-90 flex items-center justify-center border border-amber-500/20 disabled:opacity-50">
                      <FontAwesomeIcon :icon="sendingTest === post.id ? 'spinner' : 'vial'" :spin="sendingTest === post.id" />
                    </button>
                    
                    <!-- Send Newsletter -->
                    <button v-if="post.status === 'published' && !post.newsletter_enviado_at" 
                            @click="sendNewsletter(post)"
                            title="Enviar a Clientes"
                            class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-500 hover:bg-indigo-500 hover:text-white transition-all transform hover:scale-110 active:scale-90 flex items-center justify-center border border-indigo-500/20">
                      <FontAwesomeIcon icon="paper-plane" />
                    </button>

                    <div class="w-px h-6 bg-slate-100 dark:bg-slate-800 mx-1"></div>

                    <!-- Edit -->
                    <Link :href="route('admin.blog.edit', post.id)" 
                          class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-500 hover:bg-blue-500 hover:text-white transition-all transform hover:scale-110 active:scale-90 flex items-center justify-center border border-blue-500/20">
                      <FontAwesomeIcon icon="edit" />
                    </Link>

                    <!-- Delete -->
                    <button @click="deletePost(post)" 
                            class="w-10 h-10 rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all transform hover:scale-110 active:scale-90 flex items-center justify-center border border-red-500/20">
                      <FontAwesomeIcon icon="trash" />
                    </button>

                    <!-- View Public -->
                    <a v-if="post.status === 'published'" 
                       :href="route('public.blog.show', post.slug)" 
                       target="_blank"
                       class="w-10 h-10 rounded-xl bg-slate-500/10 text-slate-500 hover:bg-slate-900 hover:text-white transition-all transform hover:scale-110 active:scale-90 flex items-center justify-center border border-slate-500/20">
                      <FontAwesomeIcon icon="external-link-alt" />
                    </a>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="posts.length === 0">
                <td colspan="4" class="px-8 py-32 text-center">
                  <div class="max-w-xs mx-auto text-center">
                    <div class="w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-[2.5rem] flex items-center justify-center mx-auto mb-8 text-slate-200 dark:text-slate-700">
                      <FontAwesomeIcon icon="inbox" size="3x" />
                    </div>
                    <h4 class="text-xl font-black text-slate-900 dark:text-white mb-2">Sin Artículos</h4>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-sm mb-6">Empieza a crear contenido estratégico para tus clientes hoy mismo.</p>
                    <Link :href="route('admin.blog.create')" class="text-[var(--color-primary)] font-black uppercase tracking-widest text-xs hover:underline">
                        Crear Primera Entrada →
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
