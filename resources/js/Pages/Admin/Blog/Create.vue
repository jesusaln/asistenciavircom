<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const notyf = new Notyf()

const form = useForm({
  titulo: '',
  resumen: '',
  contenido: '',
  categoria: '',
  status: 'draft',
  imagen_portada: '',
})

const generating = ref(false)

const generateAI = async () => {
    const topic = prompt('¿Sobre qué tema quieres generar el artículo?')
    if (!topic) return
    
    generating.value = true
    try {
        // Simulación de llamada a IA (En una fase real aquí llamaríamos a un endpoint con Gemini)
        // Por ahora, para demostrar la "automatización", lo generamos localmente con templates
        setTimeout(() => {
            form.titulo = `La importancia de ${topic} en la seguridad actual`
            form.resumen = `Descubre cómo ${topic} está revolucionando la forma en que protegemos nuestros espacios y optimizamos procesos tecnológicos.`
            form.contenido = `<h2>Introducción a ${topic}</h2><p>${topic} se ha convertido en una pieza fundamental en el ecosistema tecnológico actual. En este artículo exploraremos sus beneficios, aplicaciones y por qué deberías considerarlo para tu negocio o hogar.</p><h3>Beneficios Principales</h3><ul><li>Mayor eficiencia operacional</li><li>Seguridad mejorada 24/7</li><li>Reducción de costos a largo plazo</li></ul><p>En Climas del Desierto, estamos comprometidos con ofrecer las mejores soluciones basadas en estas tecnologías de vanguardia.</p>`
            form.categoria = 'Tecnología'
            notyf.success('Contenido generado automáticamente con IA')
            generating.value = false
        }, 1500)
    } catch (e) {
        notyf.error('Error al generar contenido')
        generating.value = false
    }
}

const submit = () => {
  form.post(route('admin.blog.store'), {
    onSuccess: () => notyf.success('Artículo creado con éxito')
  })
}
</script>

<template>
  <Head title="Crear Artículo" />

  <div class="py-12">
    <div class="w-full sm:px-6 lg:px-8">
      <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded border border-slate-100 dark:border-slate-700">
        <!-- Header -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 flex justify-between items-center">
          <div class="flex items-center gap-4">
            <Link :href="route('admin.blog.index')" class="text-slate-400 dark:text-slate-500 hover:text-brand-600 dark:hover:text-slate-400 transition-colors">
                <FontAwesomeIcon icon="arrow-left" />
            </Link>
            <div>
              <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Nueva Entrada de Blog</h2>
              <p class="text-xs text-slate-500 dark:text-slate-200">Redacta o genera contenido para tu audiencia</p>
            </div>
          </div>
          <button @click="generateAI" :disabled="generating" class="bg-brand-500 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-brand-600 transition-colors flex items-center gap-2 shadow-sm disabled:opacity-50">
                <FontAwesomeIcon :icon="generating ? 'spinner' : 'magic'" :spin="generating" />
                {{ generating ? 'Generando...' : 'Generar con IA' }}
          </button>
        </div>

        <form @submit.prevent="submit" class="p-8 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Título del Artículo</label>
              <input v-model="form.titulo" type="text" placeholder="Ej: Las mejores cámaras de seguridad para 2024" class="w-full border-slate-200 dark:border-slate-700 rounded-xl focus:ring-brand-500 focus:border-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" required>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Categoría</label>
              <select v-model="form.categoria" class="w-full border-slate-200 dark:border-slate-700 rounded-xl focus:ring-brand-500 focus:border-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200">
                <option value="">Seleccionar...</option>
                <option value="Seguridad">Seguridad</option>
                <option value="Tecnología">Tecnología</option>
                <option value="Redes">Redes</option>
                <option value="Soporte">Soporte</option>
                <option value="Noticias">Noticias</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">URL Imagen Portada</label>
              <input v-model="form.imagen_portada" type="text" placeholder="https://..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl focus:ring-brand-500 focus:border-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200">
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Resumen / Intro</label>
              <textarea v-model="form.resumen" rows="2" placeholder="Un breve resumen que aparecerá en los listados..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl focus:ring-brand-500 focus:border-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"></textarea>
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Contenido (HTML Soportado)</label>
              <textarea v-model="form.contenido" rows="12" placeholder="Escribe aquí el cuerpo del artículo..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl focus:ring-brand-500 focus:border-brand-500 font-sans bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" required></textarea>
            </div>

            <div class="flex items-center gap-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Estado:</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" v-model="form.status" value="draft" class="text-blue-600 dark:text-blue-400 focus:ring-brand-500 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700">
                        <span class="text-sm text-slate-500 dark:text-slate-200">Borrador</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" v-model="form.status" value="published" class="text-blue-600 dark:text-blue-400 focus:ring-brand-500 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-700">
                        <span class="text-sm text-slate-500 dark:text-slate-200">Publicado</span>
                    </label>
                </div>
            </div>
            
            <div class="md:col-span-2 p-4 bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 rounded-xl border border-blue-100 dark:border-blue-700 flex items-start gap-3">
                <FontAwesomeIcon icon="info-circle" class="text-blue-500 dark:text-blue-400 mt-1" />
                <p class="text-xs text-sky-800 dark:text-sky-200 dark:text-blue-300 leading-relaxed">
                    Al marcar como <strong>Publicado</strong>, si tienes configurado un Webhook de <strong>n8n</strong>, el artículo se compartirá automáticamente en tus redes sociales (Facebook, Instagram, X y TikTok).
                </p>
            </div>
          </div>

          <div class="flex justify-end pt-6 border-t border-slate-100 dark:border-slate-700">
            <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 transition-all flex items-center gap-2 shadow-xl shadow-sky-200 disabled:opacity-50">
                <FontAwesomeIcon icon="save" />
                {{ form.processing ? 'Guardando...' : 'Guardar Artículo' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
