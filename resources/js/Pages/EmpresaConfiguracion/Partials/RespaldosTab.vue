<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="cloud-upload-alt" class="text-blue-600 dark:text-blue-400" />
                Respaldos en la Nube
            </h2>

            <!-- Selector de proveedor -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
                <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Seleccionar Proveedor Cloud</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button
                        @click="form.cloud_provider = 'none'"
                        :class="[
                            'p-4 rounded-xl border-2 text-left transition-all',
                            form.cloud_provider === 'none' ? 'border-gray-500 dark:border-gray-400 bg-white dark:bg-gray-700' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                        ]"
                    >
                        <FontAwesomeIcon icon="times-circle" class="text-2xl text-gray-400 dark:text-gray-500 mb-2" />
                        <div class="font-medium text-gray-900 dark:text-gray-100">Sin Cloud</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Solo respaldos locales</div>
                    </button>
                    
                    <button
                        @click="form.cloud_provider = 'gdrive'"
                        :class="[
                            'p-4 rounded-xl border-2 text-left transition-all',
                            form.cloud_provider === 'gdrive' ? 'border-blue-500 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600'
                        ]"
                    >
                        <img src="https://www.gstatic.com/images/branding/product/1x/drive_2020q4_48dp.png" class="w-8 h-8 mb-2" alt="Google Drive">
                        <div class="font-medium text-gray-900 dark:text-gray-100">Google Drive</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">15 GB gratis, API estable</div>
                    </button>
                </div>
            </div>

            <!-- Configuración Google Drive -->
            <div v-if="form.cloud_provider === 'gdrive'" class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
                <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <img src="https://www.gstatic.com/images/branding/product/1x/drive_2020q4_48dp.png" class="w-6 h-6" alt="">
                    Google Drive
                </h3>

                <!-- No conectado -->
                <div v-if="!gdriveConnected" class="text-center py-8">
                    <img src="https://www.gstatic.com/images/branding/product/1x/drive_2020q4_48dp.png" class="w-16 h-16 mx-auto mb-4" alt="">
                    <p class="text-gray-600 dark:text-gray-300 mb-6">Conecta tu cuenta de Google Drive para respaldar automáticamente</p>
                    
                    <button 
                        @click="connectGoogleDrive"
                        :disabled="connecting"
                        class="inline-flex items-center gap-3 px-6 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-white dark:hover:bg-gray-600 transition-all"
                    >
                        <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" class="w-5 h-5" alt="">
                        <span class="font-medium text-gray-700 dark:text-gray-200">
                            {{ connecting ? 'Conectando...' : 'Conectar con Google' }}
                        </span>
                        <FontAwesomeIcon v-if="connecting" icon="spinner" spin class="text-gray-400 dark:text-gray-500" />
                    </button>
                </div>

                <!-- Conectado -->
                <div v-else class="space-y-6">
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/40 rounded-full flex items-center justify-center border-4 border-white dark:border-gray-800 shadow-sm">
                                    <FontAwesomeIcon icon="check" class="text-green-600 dark:text-green-400 text-xl" />
                                </div>
                                <div>
                                    <div class="text-green-800 dark:text-green-300 font-bold text-lg">¡Conexión Exitosa!</div>
                                    <div class="text-green-600 dark:text-green-400 flex items-center gap-2">
                                        <FontAwesomeIcon icon="envelope" class="text-xs" />
                                        {{ gdriveUser }}
                                    </div>
                                </div>
                            </div>
                            <button 
                                @click="disconnectGoogleDrive"
                                class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-white dark:bg-gray-700 border border-red-200 dark:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all shadow-sm"
                            >
                                <FontAwesomeIcon icon="unlink" class="mr-2" />
                                Desconectar
                            </button>
                        </div>
                        
                        <div class="text-xs text-green-700 dark:text-green-300 bg-green-100/50 dark:bg-green-900/20 p-2 rounded-lg inline-block">
                            <FontAwesomeIcon icon="info-circle" class="mr-1" />
                            Tus respaldos se guardarán automáticamente en este Drive.
                        </div>
                    </div>

                    <!-- Espacio -->
                    <div v-if="gdriveSpace" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-white dark:bg-gray-700 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                            <div class="text-gray-500 dark:text-gray-400">Total</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ gdriveSpace.total }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-700 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                            <div class="text-gray-500 dark:text-gray-400">Usado</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ gdriveSpace.used }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-700 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                            <div class="text-gray-500 dark:text-gray-400">Disponible</div>
                            <div class="font-semibold text-green-600 dark:text-green-400">{{ gdriveSpace.free }}</div>
                        </div>
                    </div>

                    <!-- Opciones -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nombre de Carpeta
                            </label>
                            <input 
                                type="text" 
                                v-model="form.gdrive_folder_name" 
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                                placeholder="CDD_Backups"
                            >
                        </div>
                        <div class="flex items-end">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.gdrive_auto_backup" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">Auto-subir respaldos</span>
                            </label>
                        </div>
                    </div>

                    <!-- Lista de respaldos -->
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium text-gray-700 dark:text-gray-200">Respaldos en Google Drive</h4>
                            <button @click="loadGDriveBackups" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                <FontAwesomeIcon icon="sync" :spin="loadingBackups" /> Actualizar
                            </button>
                        </div>
                        
                        <div v-if="loadingBackups" class="text-center py-4 text-gray-500 dark:text-gray-400">
                            <FontAwesomeIcon icon="spinner" spin /> Cargando...
                        </div>
                        <div v-else-if="gdriveFiles.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
                            No hay respaldos aún
                        </div>
                        <div v-else class="space-y-2 max-h-48 overflow-y-auto">
                            <div v-for="(file, index) in gdriveFiles" :key="file.id" 
                                class="flex items-center justify-between p-2 rounded-lg"
                                :class="index < 3 ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-700' : 'bg-white dark:bg-gray-700'"
                            >
                                <div class="flex items-center gap-2">
                                    <FontAwesomeIcon icon="file-archive" :class="index < 3 ? 'text-blue-600 dark:text-blue-400' : 'text-blue-500 dark:text-blue-300'" />
                                    <span class="text-sm font-medium" :class="index < 3 ? 'text-blue-900 dark:text-blue-100' : 'text-gray-700 dark:text-gray-200'">{{ file.name }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">({{ formatSize(file.size) }})</span>
                                    <span v-if="index < 3" class="text-[10px] bg-blue-600 text-white px-1.5 rounded-full uppercase font-bold">Protegido</span>
                                </div>
                                <div class="flex gap-1">
                                    <button @click="downloadFile(file.id)" class="p-1 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded" title="Descargar">
                                        <FontAwesomeIcon icon="download" />
                                    </button>
                                    <button 
                                        @click="deleteFile(file.id)" 
                                        class="p-1 rounded transition-colors"
                                        :class="index < 3 ? 'text-gray-300 dark:text-gray-500 cursor-not-allowed' : 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/40'"
                                        :title="index < 3 ? 'Este archivo está protegido' : 'Eliminar'"
                                        :disabled="index < 3"
                                    >
                                        <FontAwesomeIcon icon="trash" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Mensajes -->
            <div v-if="errorMessage" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg text-red-700 dark:text-red-300 mb-4">
                {{ errorMessage }}
            </div>
            <div v-if="successMessage" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg text-green-700 dark:text-green-300 mb-4">
                {{ successMessage }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    form: { type: Object, required: true },
});

const connecting = ref(false);
const gdriveConnected = ref(false);
const gdriveUser = ref('');
const gdriveSpace = ref(null);
const gdriveFiles = ref([]);
const loadingBackups = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const connectGoogleDrive = () => {
    connecting.value = true;
    errorMessage.value = '';
    
    // Abrir ventana de OAuth
    const width = 600;
    const height = 700;
    const left = (window.innerWidth - width) / 2;
    const top = (window.innerHeight - height) / 2;
    
    const authWindow = window.open(
        '/api/gdrive/auth',
        'GoogleDriveAuth',
        `width=${width},height=${height},left=${left},top=${top}`
    );

    // Escuchar mensaje de la ventana
    const handleMessage = (event) => {
        if (event.data?.type === 'gdrive_auth_success') {
            gdriveConnected.value = true;
            connecting.value = false;
            successMessage.value = '¡Conectado a Google Drive!';
            checkGDriveConnection();
            window.removeEventListener('message', handleMessage);
        } else if (event.data?.type === 'gdrive_auth_error') {
            errorMessage.value = event.data.message || 'Error de autorización';
            connecting.value = false;
            window.removeEventListener('message', handleMessage);
        }
    };

    window.addEventListener('message', handleMessage);

    // Timeout si no hay respuesta
    setTimeout(() => {
        if (connecting.value) {
            connecting.value = false;
            window.removeEventListener('message', handleMessage);
        }
    }, 120000);
};

const disconnectGoogleDrive = async () => {
    if (!confirm('¿Desconectar Google Drive?')) return;
    
    try {
        await axios.post('/api/gdrive/disconnect');
        gdriveConnected.value = false;
        gdriveUser.value = '';
        gdriveSpace.value = null;
        gdriveFiles.value = [];
        successMessage.value = 'Google Drive desconectado';
    } catch (error) {
        errorMessage.value = 'Error al desconectar';
    }
};

const checkGDriveConnection = async () => {
    errorMessage.value = '';
    try {
        const response = await axios.get('/api/gdrive/test');
        const data = response.data;
        
        if (data.success) {
            gdriveConnected.value = true;
            gdriveUser.value = data.user?.email || '';
            gdriveSpace.value = data.space;
            loadGDriveBackups();
        } else {
            gdriveConnected.value = false;
        }
    } catch (error) {
        gdriveConnected.value = false;
    }
};

const loadGDriveBackups = async () => {
    loadingBackups.value = true;
    try {
        const response = await axios.get('/api/gdrive/list');
        const data = response.data;
        if (data.success) {
            gdriveFiles.value = data.files.filter(f => !f.is_folder);
        }
    } catch (error) {
        console.error(error);
    } finally {
        loadingBackups.value = false;
    }
};

const downloadFile = (fileId) => {
    window.open(`/api/gdrive/download?file_id=${fileId}`, '_blank');
};

const deleteFile = async (fileId) => {
    if (!confirm('¿Eliminar este respaldo?')) return;
    
    try {
        const response = await axios.post('/api/gdrive/delete', { file_id: fileId });
        if (response.data.success) {
            successMessage.value = 'Respaldo eliminado con éxito';
            loadGDriveBackups();
        } else {
            errorMessage.value = response.data.message || 'Error al eliminar';
        }
    } catch (error) {
        errorMessage.value = 'Error al eliminar';
    }
};



const formatSize = (bytes) => {
    if (!bytes) return '0 B';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    return (bytes / (1024 * 1024 * 1024)).toFixed(1) + ' GB';
};

onMounted(() => {
    if (props.form.cloud_provider === 'gdrive') {
        checkGDriveConnection();
    }
});
</script>
