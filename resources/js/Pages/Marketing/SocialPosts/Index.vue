<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import axios from 'axios'

const props = defineProps({
    posts: Object,
})

const notyf = new Notyf()
const loading = ref(false)
const productos = ref([])
const kits = ref([])
const status = ref({ facebook_ready: false, instagram_ready: false })
const selected = ref({ producto_id: null, plataforma: 'facebook', mensaje: '' })
const showModal = ref(false)

async function loadProductos() {
    try {
        const res = await axios.get('/marketing/social-posts/productos')
        productos.value = res.data.productos
        kits.value = res.data.kits
    } catch (e) {
        notyf.error('Error al cargar productos')
    }
}

async function loadStatus() {
    try {
        const res = await axios.get('/marketing/social-posts/status')
        status.value = res.data
    } catch (e) {}
}

async function publish() {
    if (!selected.value.producto_id) {
        notyf.error('Selecciona un producto')
        return
    }
    loading.value = true
    try {
        const res = await axios.post('/marketing/social-posts/publish', selected.value)
        if (res.data.success) {
            notyf.success('Publicado exitosamente')
            showModal.value = false
            router.reload()
        }
    } catch (e) {
        const data = e.response?.data
        console.error('Publish error:', data)
        const msg = data?.error || data?.message || (data?.errors ? Object.values(data.errors).flat().join(', ') : 'Error al publicar')
        notyf.error(msg)
    } finally {
        loading.value = false
    }
}

async function deletePost(id) {
    if (!confirm('¿Eliminar esta publicación?')) return
    try {
        await axios.delete(`/marketing/social-posts/${id}`)
        notyf.success('Publicación eliminada')
        router.reload()
    } catch (e) {
        notyf.error('Error al eliminar')
    }
}

function formatPrecio(p) {
    return p > 0 ? '$' + p.toLocaleString('es-MX') : 'Consultar'
}

onMounted(() => {
    loadStatus()
})
</script>

<template>
    <Head title="Redes Sociales" />
    <AppLayout title="Redes Sociales">
        <template #header>
            <button @click="showModal = true; loadProductos()"
                class="btn btn-primary text-xs font-black uppercase tracking-wide">
                <FontAwesomeIcon icon="plus" class="mr-1" />
                Nueva Publicación
            </button>
        </template>

        <div class="card p-6">
            <div v-if="!status.facebook_ready && !status.instagram_ready" class="p-4 mb-6 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-sm">
                <FontAwesomeIcon icon="triangle-exclamation" class="mr-2" />
                Meta no está configurado. Agrega <code class="px-1 py-0.5 bg-black/20 rounded">META_PAGE_ID</code> y
                <code class="px-1 py-0.5 bg-black/20 rounded">META_PAGE_ACCESS_TOKEN</code> al .env para activar las publicaciones.
            </div>
            <div v-else class="flex gap-3 mb-6">
                <span class="badge" :class="status.facebook_ready ? 'badge-success' : 'badge-danger'">
                    Facebook {{ status.facebook_ready ? '✓' : '✗' }}
                </span>
                <span class="badge" :class="status.instagram_ready ? 'badge-success' : 'badge-danger'">
                    Instagram {{ status.instagram_ready ? '✓' : '✗' }}
                </span>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Plataforma</th>
                        <th>Estado</th>
                        <th>Publicado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="post in posts.data" :key="post.id">
                        <td class="flex items-center gap-3">
                            <img v-if="post.producto?.imagen" :src="'/storage/' + post.producto.imagen"
                                class="w-10 h-10 rounded-xl object-cover border border-white/10" />
                            <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-white/30" v-else>
                                <FontAwesomeIcon icon="box" />
                            </div>
                            <span class="font-bold">{{ post.producto?.nombre || '—' }}</span>
                        </td>
                        <td>
                            <span class="badge" :class="post.plataforma === 'facebook' ? 'badge-info' : 'badge-warning'">
                                <FontAwesomeIcon :icon="['fab', post.plataforma === 'facebook' ? 'facebook' : 'instagram']" class="mr-1" />
                                {{ post.plataforma }}
                            </span>
                        </td>
                        <td>
                            <span class="badge" :class="post.estado === 'publicado' ? 'badge-success' : 'badge-danger'">
                                {{ post.estado }}
                            </span>
                        </td>
                        <td class="text-white/50 text-xs">{{ post.published_at ? new Date(post.published_at).toLocaleString() : '—' }}</td>
                        <td>
                            <button @click="deletePost(post.id)" class="text-rose-400 hover:text-rose-300 text-xs"
                                title="Eliminar publicación">
                                <FontAwesomeIcon icon="trash-can" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="posts.data?.length === 0">
                        <td colspan="5" class="text-center text-white/30 py-12">
                            <FontAwesomeIcon icon="newspaper" class="text-3xl mb-3 opacity-30" />
                            <p>Sin publicaciones aún</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="posts.last_page > 1" class="flex justify-center gap-2 mt-6">
                <Link v-for="p in posts.last_page" :key="p" :href="posts.path + '?page=' + p"
                    class="px-3 py-1 rounded-lg text-xs font-bold"
                    :class="p === posts.current_page ? 'bg-brand-500 text-black' : 'bg-white/5 text-white/50 hover:text-white'">
                    {{ p }}
                </Link>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
                @click.self="showModal = false">
                <div class="w-full max-w-lg mx-4 bg-[var(--ui-surface)] border border-white/10 rounded-3xl p-6 shadow-2xl">
                    <h3 class="text-lg font-black mb-4">Nueva Publicación</h3>

                    <label class="block text-xs font-bold text-white/50 mb-1">Producto/Kits</label>
                    <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto mb-4 p-2 bg-black/20 rounded-2xl">
                        <template v-for="item in [...productos, ...kits]" :key="item.id">
                            <div @click="selected.producto_id = item.id"
                                class="p-2 rounded-xl border-2 cursor-pointer transition-all text-center"
                                :class="selected.producto_id === item.id ? 'border-brand-500 bg-brand-500/10' : 'border-white/5 bg-white/[0.02] hover:border-white/20'">
                                <img v-if="item.imagen" :src="'/storage/' + item.imagen"
                                    class="w-full h-16 object-cover rounded-lg mb-1" />
                                <p class="text-[10px] font-bold truncate">{{ item.nombre }}</p>
                                <p class="text-[9px] text-white/40">{{ formatPrecio(item.precio_con_iva) }}</p>
                                <span v-if="item.tipo_producto === 'kit'" class="text-[8px] text-amber-400 font-black uppercase">Kit</span>
                            </div>
                        </template>
                        <div v-if="productos.length === 0 && kits.length === 0" class="col-span-2 text-center text-white/30 py-6">
                            No hay productos activos en catálogo web
                        </div>
                    </div>

                    <label class="block text-xs font-bold text-white/50 mb-1">Plataforma</label>
                    <div class="flex gap-2 mb-4">
                        <button @click="selected.plataforma = 'facebook'"
                            class="flex-1 py-2 rounded-xl text-xs font-bold border-2 transition-all"
                            :class="selected.plataforma === 'facebook' ? 'border-sky-500 bg-sky-500/10 text-sky-400' : 'border-white/10 text-white/40'">
                            <FontAwesomeIcon :icon="['fab', 'facebook']" class="mr-1" /> Facebook
                        </button>
                        <button @click="selected.plataforma = 'instagram'"
                            class="flex-1 py-2 rounded-xl text-xs font-bold border-2 transition-all"
                            :class="selected.plataforma === 'instagram' ? 'border-pink-500 bg-pink-500/10 text-pink-400' : 'border-white/10 text-white/40'">
                            <FontAwesomeIcon :icon="['fab', 'instagram']" class="mr-1" /> Instagram
                        </button>
                    </div>

                    <label class="block text-xs font-bold text-white/50 mb-1">Mensaje</label>
                    <textarea v-model="selected.mensaje" rows="3" placeholder="🔥 ¡Nuevo producto disponible!"
                        class="w-full bg-black/20 border border-white/10 rounded-xl p-3 text-sm text-white placeholder:text-white/20 mb-4"></textarea>

                    <div class="flex gap-2">
                        <button @click="showModal = false"
                            class="flex-1 py-2.5 rounded-xl bg-white/5 text-white/50 text-xs font-bold hover:bg-white/10">
                            Cancelar
                        </button>
                        <button @click="publish" :disabled="loading"
                            class="flex-1 py-2.5 rounded-xl bg-brand-500 text-black text-xs font-bold hover:bg-brand-400 disabled:opacity-40">
                            <FontAwesomeIcon icon="spinner" v-if="loading" class="animate-spin mr-1" />
                            Publicar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
