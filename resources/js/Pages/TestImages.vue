<template>
    <div class="min-h-screen bg-gray-100 py-12">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-6">Test de Carga de Imágenes</h1>

                <!-- Información del servidor -->
                <div class="mb-8 p-4 bg-gray-100 rounded">
                    <h2 class="text-lg font-semibold mb-2">Información del Servidor</h2>
                    <p><strong>Host:</strong> {{ serverInfo.host }}:{{ serverInfo.port }}</p>
                    <p><strong>Origin:</strong> {{ serverInfo.origin || 'No origin' }}</p>
                    <p><strong>Scheme:</strong> {{ serverInfo.scheme }}</p>
                </div>

                <!-- Lista de imágenes disponibles -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold mb-4">Imágenes Disponibles ({{ images.length }})</h2>

                    <div v-if="images.length === 0" class="text-gray-500">
                        No hay imágenes disponibles
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="image in images" :key="image.filename"
                             class="border rounded-lg p-4 bg-white">
                            <div class="mb-3">
                                <img :src="image.url"
                                     :alt="image.filename"
                                     class="w-full h-32 object-cover rounded border"
                                     @error="handleImageError"
                                     @load="handleImageLoad">
                            </div>
                            <h3 class="font-medium text-sm">{{ image.filename }}</h3>
                            <p class="text-xs text-gray-600">Tamaño: {{ formatFileSize(image.size) }}</p>
                            <p class="text-xs text-gray-600">Tipo: {{ image.mime_type }}</p>
                            <p class="text-xs" :class="image.exists ? 'text-green-600' : 'text-red-600'">
                                {{ image.exists ? '✅ Existe' : '❌ No existe' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- URLs alternativas -->
                <div class="mb-8 p-4 bg-blue-50 rounded">
                    <h2 class="text-lg font-semibold mb-2">URLs de Prueba</h2>
                    <div class="space-y-2 text-sm">
                        <div>
                            <strong>API Lista:</strong>
                            <a :href="apiListUrl" target="_blank" class="text-blue-600 hover:underline ml-2">
                                {{ apiListUrl }}
                            </a>
                        </div>
                        <div>
                            <strong>Imagen Nueva:</strong>
                            <a :href="newImageUrl" target="_blank" class="text-blue-600 hover:underline ml-2">
                                {{ newImageUrl }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Logs de eventos -->
                <div class="p-4 bg-gray-100 rounded">
                    <h2 class="text-lg font-semibold mb-2">Logs de Eventos</h2>
                    <div class="max-h-32 overflow-y-auto text-sm font-mono">
                        <div v-for="(log, index) in eventLogs" :key="index" class="mb-1">
                            <span class="text-gray-600">{{ log.time }}</span>
                            <span class="ml-2" :class="log.type === 'error' ? 'text-red-600' : 'text-green-600'">
                                {{ log.message }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TestImages',
    data() {
        return {
            serverInfo: {
                host: window.location.hostname,
                port: window.location.port,
                scheme: window.location.protocol.replace(':', ''),
                origin: null
            },
            images: [],
            eventLogs: []
        };
    },
    computed: {
        apiListUrl() {
            return `${window.location.origin}/api/profile-photos`;
        },
        newImageUrl() {
            const existingImage = this.images[0];
            return existingImage ? existingImage.url : `${window.location.origin}/profile-photo/nonexistent.png`;
        }
    },
    async mounted() {
        this.logEvent('info', 'Página cargada, iniciando pruebas...');
        await this.loadImages();
    },
    methods: {
        async loadImages() {
            try {
                this.logEvent('info', 'Cargando lista de imágenes...');
                const response = await fetch(this.apiListUrl);
                const data = await response.json();

                this.images = data.images || [];
                this.serverInfo.origin = data.server_info?.origin;

                this.logEvent('info', `Cargadas ${this.images.length} imágenes`);
            } catch (error) {
                this.logEvent('error', `Error al cargar imágenes: ${error.message}`);
            }
        },

        handleImageLoad(event) {
            const img = event.target;
            this.logEvent('info', `Imagen cargada correctamente: ${img.alt}`);
        },

        handleImageError(event) {
            const img = event.target;
            this.logEvent('error', `Error al cargar imagen: ${img.alt}`);
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        logEvent(type, message) {
            this.eventLogs.unshift({
                time: new Date().toLocaleTimeString(),
                type,
                message
            });

            // Mantener solo los últimos 20 logs
            if (this.eventLogs.length > 20) {
                this.eventLogs = this.eventLogs.slice(0, 20);
            }
        }
    }
};
</script>
