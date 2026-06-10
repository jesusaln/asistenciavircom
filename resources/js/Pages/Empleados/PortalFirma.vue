<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  contrato: Object
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const aceptarContrato = () => {
  router.post(`/mis-contratos/${props.contrato.id}/aceptar`, {}, {
    onSuccess: () => {
      notyf.success('Documento firmado con éxito')
    }
  })
}

</script>

<template>
  <Head :title="`Firma de Documento - ${contrato.titulo}`" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 py-12 px-4">
    <div class="max-w-4xl mx-auto">
      
      <!-- Card Documento -->
      <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-white/5 overflow-hidden">
        
        <!-- Header -->
        <div class="px-10 py-8 border-b border-slate-50 dark:border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-br from-slate-50/50 to-white dark:from-slate-800/50 dark:to-slate-800">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-blue-500 mb-2 block">Portal de Firma Electrónica</span>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white">{{ contrato.titulo }}</h1>
            </div>
            <div v-if="contrato.estado === 'firmado'" class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-2xl border border-emerald-100 dark:border-emerald-500/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                <span class="text-sm font-black uppercase tracking-tighter">Firmado el {{ contrato.signed_at }}</span>
            </div>
        </div>

        <!-- Contenido del Contrato -->
        <div class="p-10 md:p-16">
            <div class="prose prose-slate dark:prose-invert max-w-none">
                <div class="whitespace-pre-wrap font-serif text-lg leading-relaxed text-slate-700 dark:text-slate-300 bg-slate-50/50 dark:bg-slate-900/30 p-8 md:p-12 rounded-3xl border border-slate-100 dark:border-white/5">
                    {{ contrato.contenido }}
                </div>
            </div>

            <!-- Sección de Firma -->
            <div v-if="contrato.estado !== 'firmado'" class="mt-12 p-8 md:p-12 bg-blue-50 dark:bg-blue-900/20 rounded-[2rem] border-2 border-dashed border-blue-200 dark:border-blue-500/20">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="flex-1">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mb-2">Confirmación de Aceptación</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
                            Al hacer clic en el botón de abajo, manifiesto mi conformidad con los términos y condiciones expresados en este documento. Esta acción constituye una firma electrónica con validez legal bajo la Ley Federal del Trabajo.
                        </p>
                    </div>
                    <button 
                        @click="aceptarContrato"
                        class="w-full md:w-auto px-10 py-5 bg-blue-600 text-white font-black rounded-2xl shadow-xl shadow-blue-600/20 hover:bg-blue-700 hover:scale-105 active:scale-95 transition-all duration-200"
                    >
                        Firmar y Aceptar Documento
                    </button>
                </div>
            </div>

            <div v-else class="mt-12 text-center border-t border-slate-100 dark:border-white/5 pt-12">
                <div class="inline-block p-6 bg-slate-50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-white/10 text-left max-w-2xl">
                    <h4 class="text-[10px] font-black uppercase text-blue-500 tracking-widest mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        Sello Digital de Integridad (NOM-151)
                    </h4>
                    <p class="text-[9px] font-mono text-slate-400 break-all leading-relaxed">
                        <span class="text-slate-500 font-black">HASH SHA-256:</span><br/>
                        {{ contrato.hash_documento || 'No disponible' }}
                    </p>
                    <p class="text-[9px] font-bold text-slate-400 mt-3">
                        <span class="text-slate-500 font-black uppercase tracking-tighter">Certificación:</span> Documento íntegro y no alterado.
                    </p>
                </div>
                <p class="text-[10px] text-slate-400 font-bold italic mt-8">Este documento ha sido firmado electrónicamente y sellado digitalmente.</p>
            </div>
        </div>
      </div>

      <div class="mt-8 text-center">
        <Link href="/dashboard" class="text-sm font-black text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">Volver al Dashboard</Link>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* Estilos para el texto del contrato para que parezca un documento formal */
.prose {
    line-height: 1.8;
}
</style>
