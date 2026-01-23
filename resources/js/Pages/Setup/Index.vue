<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const currentStep = ref(1);
const totalSteps = 4;

const form = useForm({
    // Step 1: Admin
    admin_name: '',
    admin_email: '',
    password: '',
    password_confirmation: '',
    
    // Step 2: Empresa
    empresa_nombre: '',
    empresa_rfc: '',
    empresa_cp: '',
    empresa_regimen: '601',
    empresa_uso_cfdi: 'G03',
    empresa_direccion: '',
    empresa_numero_exterior: '',
    empresa_colonia: '',
    empresa_municipio: '',
    empresa_estado: '',
    empresa_logo: null,

    // Step 3: Fiscal (Impuestos)
    iva_porcentaje: '16',
    enable_isr: false,
    enable_retencion_iva: false,
    enable_retencion_isr: false,
    retencion_iva_default: '0',
    retencion_isr_default: '0',

    // Step 4: Operación
    almacen_nombre: 'Almacén Principal'
});

const colonias = ref([]);
const loadingCp = ref(false);
const cpError = ref('');

// Watch CP para autocompletar dirección
watch(() => form.empresa_cp, async (newCp) => {
    if (newCp?.length === 5) {
        loadingCp.value = true;
        colonias.value = [];
        cpError.value = '';
        try {
            const res = await fetch(`/api/cp/${newCp}`);
            
            if (res.ok) {
                const data = await res.json();
                form.empresa_estado = data.estado;
                form.empresa_municipio = data.municipio;
                colonias.value = data.colonias || [];
                
                if (colonias.value.length === 1) {
                    form.empresa_colonia = colonias.value[0];
                } else if (colonias.value.length > 1) {
                    form.empresa_colonia = '';
                }
            } else {
                cpError.value = 'Código postal no encontrado';
            }
        } catch (e) {
            cpError.value = 'Error al buscar código postal';
            console.error("Error fetching CP", e);
        } finally {
            loadingCp.value = false;
        }
    } else {
        colonias.value = [];
        form.empresa_estado = '';
        form.empresa_municipio = '';
        form.empresa_colonia = '';
    }
});

// Validación por paso
const canProceed = computed(() => {
    switch(currentStep.value) {
        case 1:
            return form.admin_name && form.admin_email && form.password && form.password === form.password_confirmation && form.password.length >= 8;
        case 2:
            return form.empresa_nombre && form.empresa_cp?.length === 5 && form.empresa_estado && form.empresa_municipio && form.empresa_colonia && form.empresa_direccion && form.empresa_numero_exterior;
        case 3:
            return form.iva_porcentaje;
        case 4:
            return form.almacen_nombre;
    }
    return true;
});

const nextStep = () => {
    if (currentStep.value < totalSteps && canProceed.value) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const goToStep = (step) => {
    if (step < currentStep.value) {
        currentStep.value = step;
    }
};

const submit = () => {
    form.post(route('setup.store'), {
        forceFormData: true,
        onSuccess: () => {
            // Redirección manejada por Inertia desde el server
        },
        onError: (errors) => {
            console.error('Errores de validación:', errors);
            // Ir al paso con el primer error
            const errorKeys = Object.keys(errors);
            const step1Fields = ['admin_name', 'admin_email', 'password', 'password_confirmation'];
            const step2Fields = ['empresa_nombre', 'empresa_rfc', 'empresa_cp', 'empresa_regimen', 'empresa_uso_cfdi', 'empresa_direccion', 'empresa_numero_exterior', 'empresa_colonia', 'empresa_municipio', 'empresa_estado', 'empresa_logo'];
            const step3Fields = ['iva_porcentaje', 'enable_isr', 'enable_retencion_iva', 'enable_retencion_isr', 'retencion_iva_default', 'retencion_isr_default'];
            const step4Fields = ['almacen_nombre'];
            
            for (const key of errorKeys) {
                if (step1Fields.includes(key)) { currentStep.value = 1; return; }
                if (step2Fields.includes(key)) { currentStep.value = 2; return; }
                if (step3Fields.includes(key)) { currentStep.value = 3; return; }
                if (step4Fields.includes(key)) { currentStep.value = 4; return; }
            }
        },
        onFinish: () => {
            if (!form.hasErrors) {
                form.reset('password', 'password_confirmation');
            }
        },
    });
};

const stepInfo = computed(() => {
    const steps = [
        { num: 1, title: 'Administrador', icon: '👤', desc: 'Cuenta principal' },
        { num: 2, title: 'Empresa', icon: '🏢', desc: 'Datos fiscales' },
        { num: 3, title: 'Impuestos', icon: '📊', desc: 'Configuración fiscal' },
        { num: 4, title: 'Finalizar', icon: '🚀', desc: 'Almacén y resumen' },
    ];
    return steps;
});

const stepTitle = computed(() => stepInfo.value[currentStep.value - 1]?.title || '');

// Restauración de respaldo
const backupFile = ref(null);
const isRestoring = ref(false);
const restoreError = ref('');
const restoreSuccess = ref('');

const handleBackupFile = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (!file.name.endsWith('.zip')) {
            restoreError.value = 'El archivo debe ser un .zip';
            backupFile.value = null;
            return;
        }
        backupFile.value = file;
        restoreError.value = '';
    }
};

const restoreBackup = async () => {
    if (!backupFile.value) {
        restoreError.value = 'Selecciona un archivo .zip primero';
        return;
    }

    isRestoring.value = true;
    restoreError.value = '';
    restoreSuccess.value = '';

    const formData = new FormData();
    formData.append('backup', backupFile.value);

    try {
        const response = await fetch('/api/setup/restore-backup', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        const data = await response.json();

        if (response.ok) {
            restoreSuccess.value = data.message || '¡Respaldo restaurado correctamente!';
            // Redirigir al login después de 2 segundos
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
        } else {
            restoreError.value = data.message || 'Error al restaurar el respaldo';
        }
    } catch (error) {
        console.error('Error al restaurar:', error);
        restoreError.value = 'Error de conexión al restaurar el respaldo';
    } finally {
        isRestoring.value = false;
    }
};

const triggerBackupInput = () => {
    document.getElementById('backup-file-input').click();
};
</script>

<template>
    <Head title="Instalación del Sistema" />

    <div class="min-h-screen flex flex-col items-center justify-center py-12 px-4 bg-gradient-to-br from-slate-900 via-gray-900 to-slate-800">
        
        <!-- Logo/Branding -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg shadow-blue-500/25 mb-4">
                <span class="text-3xl">⚡</span>
            </div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
                Asistente de Instalación
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-2">Configura tu sistema en unos simples pasos</p>
        </div>

        <!-- Stepper Visual -->
        <div class="w-full max-w-2xl mb-8">
            <div class="flex items-center justify-between relative">
                <!-- Progress Line Background -->
                <div class="absolute top-5 left-0 right-0 h-0.5 bg-gray-700 z-0"></div>
                <!-- Progress Line Active -->
                <div class="absolute top-5 left-0 h-0.5 bg-gradient-to-r from-blue-500 to-indigo-500 z-0 transition-all duration-500"
                     :style="{ width: ((currentStep - 1) / (totalSteps - 1)) * 100 + '%' }"></div>
                
                <!-- Step Circles -->
                <div v-for="step in stepInfo" :key="step.num" 
                     class="relative z-10 flex flex-col items-center cursor-pointer group"
                     @click="goToStep(step.num)">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg font-bold transition-all duration-300 shadow-lg"
                         :class="{
                             'bg-gradient-to-br from-blue-500 to-indigo-600 text-white scale-110 shadow-blue-500/40': currentStep === step.num,
                             'bg-green-500 text-white': currentStep > step.num,
                             'bg-gray-700 text-gray-400': currentStep < step.num
                         }">
                        <span v-if="currentStep > step.num">✓</span>
                        <span v-else>{{ step.icon }}</span>
                    </div>
                    <span class="mt-2 text-xs font-medium transition-colors"
                          :class="currentStep >= step.num ? 'text-white' : 'text-gray-500 dark:text-gray-400'">
                        {{ step.title }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="w-full max-w-2xl">
            <div class="bg-gray-800/80 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-700/50 overflow-hidden">
                
                <!-- Card Header -->
                <div class="px-8 py-6 bg-gradient-to-r from-gray-800 to-gray-800/50 border-b border-gray-700/50">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500/20 to-indigo-500/20 flex items-center justify-center text-2xl">
                            {{ stepInfo[currentStep - 1]?.icon }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">{{ stepTitle }}</h2>
                            <p class="text-sm text-gray-400">{{ stepInfo[currentStep - 1]?.desc }}</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="p-8">
                    
                    <!-- General Error Message -->
                    <div v-if="Object.keys(form.errors).length > 0" 
                         class="mb-6 p-4 bg-red-500/10 border border-red-500/30 rounded-xl animate-shake">
                        <div class="flex items-start gap-3">
                            <span class="text-red-400 text-xl">⚠️</span>
                            <div>
                                <p class="text-red-400 font-semibold">Por favor, corrige los siguientes errores:</p>
                                <ul class="list-disc list-inside text-sm text-red-300/80 mt-2">
                                    <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- STEP 1: ADMIN -->
                    <div v-show="currentStep === 1" class="space-y-5 animate-slide-in">
                        <div>
                            <InputLabel for="admin_name" value="Nombre completo" class="text-gray-300" />
                            <TextInput id="admin_name" v-model="form.admin_name" type="text" 
                                       class="mt-2 block w-full bg-gray-700/50 border-gray-600 focus:border-blue-500 focus:ring-blue-500/20" 
                                       placeholder="Ej. Juan Pérez" required autofocus />
                            <InputError :message="form.errors.admin_name" class="mt-2" />
                        </div>
                        
                        <div>
                            <InputLabel for="admin_email" value="Correo electrónico" class="text-gray-300" />
                            <TextInput id="admin_email" v-model="form.admin_email" type="email" 
                                       class="mt-2 block w-full bg-gray-700/50 border-gray-600 focus:border-blue-500" 
                                       placeholder="correo@empresa.com" required />
                            <InputError :message="form.errors.admin_email" class="mt-2" />
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="password" value="Contraseña" class="text-gray-300" />
                                <TextInput id="password" v-model="form.password" type="password" 
                                           class="mt-2 block w-full bg-gray-700/50 border-gray-600" 
                                           placeholder="••••••••" required />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mínimo 8 caracteres</p>
                                <InputError :message="form.errors.password" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="password_confirmation" value="Confirmar contraseña" class="text-gray-300" />
                                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" 
                                           class="mt-2 block w-full bg-gray-700/50 border-gray-600" 
                                           placeholder="••••••••" required />
                                <p v-if="form.password && form.password_confirmation && form.password !== form.password_confirmation" 
                                   class="text-xs text-red-400 mt-1">Las contraseñas no coinciden</p>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: EMPRESA -->
                    <div v-show="currentStep === 2" class="space-y-5 animate-slide-in">
                        <div>
                            <InputLabel for="empresa_nombre" value="Nombre comercial / Razón social" class="text-gray-300" />
                            <TextInput id="empresa_nombre" v-model="form.empresa_nombre" type="text" 
                                       class="mt-2 block w-full bg-gray-700/50 border-gray-600" 
                                       placeholder="Ej. Climas del Desierto S.A. de C.V." required />
                            <InputError :message="form.errors.empresa_nombre" class="mt-2" />
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="empresa_rfc" value="RFC" class="text-gray-300" />
                                <TextInput id="empresa_rfc" v-model="form.empresa_rfc" type="text" 
                                           class="mt-2 block w-full bg-gray-700/50 border-gray-600 uppercase" 
                                           placeholder="XAXX010101000" maxlength="13" />
                                <InputError :message="form.errors.empresa_rfc" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="empresa_cp" class="text-gray-300">
                                    Código Postal
                                    <span v-if="loadingCp" class="ml-2 inline-block w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></span>
                                </InputLabel>
                                <TextInput id="empresa_cp" v-model="form.empresa_cp" type="text" 
                                           class="mt-2 block w-full bg-gray-700/50 border-gray-600" 
                                           :class="{ 'border-green-500': colonias.length > 0, 'border-red-500': cpError }"
                                           placeholder="83117" maxlength="5" required />
                                <p v-if="cpError" class="text-xs text-red-400 mt-1">{{ cpError }}</p>
                                <p v-else-if="colonias.length > 0" class="text-xs text-green-400 mt-1">
                                    ✓ {{ form.empresa_estado }}, {{ form.empresa_municipio }}
                                </p>
                                <InputError :message="form.errors.empresa_cp" class="mt-2" />
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="empresa_regimen" value="Régimen Fiscal" class="text-gray-300" />
                                <select id="empresa_regimen" v-model="form.empresa_regimen" 
                                        class="mt-2 block w-full rounded-md bg-gray-700/50 border-gray-600 text-white focus:border-blue-500 focus:ring-blue-500/20">
                                    <option value="601">601 - General de Ley PM</option>
                                    <option value="603">603 - PM Fines no Lucrativos</option>
                                    <option value="605">605 - Sueldos y Salarios</option>
                                    <option value="606">606 - Arrendamiento</option>
                                    <option value="612">612 - PF Actividades Empresariales</option>
                                    <option value="621">621 - Incorporación Fiscal</option>
                                    <option value="626">626 - RESICO</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="empresa_uso_cfdi" value="Uso de CFDI" class="text-gray-300" />
                                <select id="empresa_uso_cfdi" v-model="form.empresa_uso_cfdi" 
                                        class="mt-2 block w-full rounded-md bg-gray-700/50 border-gray-600 text-white focus:border-blue-500">
                                    <option value="G01">G01 - Adquisición de mercancías</option>
                                    <option value="G03">G03 - Gastos en general</option>
                                    <option value="P01">P01 - Por definir</option>
                                    <option value="S01">S01 - Sin efectos fiscales</option>
                                </select>
                            </div>
                        </div>

                        <div class="border-t border-gray-700 pt-5 mt-5">
                            <h4 class="text-sm font-medium text-gray-400 mb-4">📍 Dirección Fiscal</h4>
                            
                            <div class="grid grid-cols-3 gap-4">
                                <div class="col-span-2">
                                    <InputLabel for="empresa_direccion" value="Calle" class="text-gray-300" />
                                    <TextInput id="empresa_direccion" v-model="form.empresa_direccion" type="text" 
                                               class="mt-2 block w-full bg-gray-700/50 border-gray-600" placeholder="Av. Principal" />
                                </div>
                                <div>
                                    <InputLabel for="empresa_numero_exterior" value="No. Ext" class="text-gray-300" />
                                    <TextInput id="empresa_numero_exterior" v-model="form.empresa_numero_exterior" type="text" 
                                               class="mt-2 block w-full bg-gray-700/50 border-gray-600" placeholder="123" />
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <InputLabel for="empresa_colonia" value="Colonia" class="text-gray-300" />
                                    <select v-if="colonias.length > 1" id="empresa_colonia" v-model="form.empresa_colonia" 
                                            class="mt-2 block w-full rounded-md bg-gray-700/50 border-gray-600 text-white focus:border-blue-500">
                                        <option value="">Selecciona una colonia...</option>
                                        <option v-for="col in colonias" :key="col" :value="col">{{ col }}</option>
                                    </select>
                                    <TextInput v-else id="empresa_colonia" v-model="form.empresa_colonia" type="text" 
                                               class="mt-2 block w-full bg-gray-700/50 border-gray-600" 
                                               :placeholder="loadingCp ? 'Buscando...' : 'Ingresa el CP primero'" 
                                               :disabled="loadingCp" />
                                </div>
                                <div>
                                    <InputLabel for="empresa_municipio" value="Municipio" class="text-gray-300" />
                                    <TextInput id="empresa_municipio" v-model="form.empresa_municipio" type="text" 
                                               class="mt-2 block w-full bg-gray-700/50 border-gray-600 read-only:bg-gray-800" 
                                               readonly />
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <InputLabel for="empresa_estado" value="Estado" class="text-gray-300" />
                                <select id="empresa_estado" v-model="form.empresa_estado" 
                                        class="mt-2 block w-full rounded-md bg-gray-700/50 border-gray-600 text-white focus:border-blue-500">
                                    <option value="">Selecciona un estado...</option>
                                    <option v-for="edo in ['Aguascalientes','Baja California','Baja California Sur','Campeche','Chiapas','Chihuahua','Ciudad de México','Coahuila','Colima','Durango','Estado de México','Guanajuato','Guerrero','Hidalgo','Jalisco','Michoacán','Morelos','Nayarit','Nuevo León','Oaxaca','Puebla','Querétaro','Quintana Roo','San Luis Potosí','Sinaloa','Sonora','Tabasco','Tamaulipas','Tlaxcala','Veracruz','Yucatán','Zacatecas']" 
                                            :key="edo" :value="edo">{{ edo }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="empresa_logo" value="Logo de la empresa (opcional)" class="text-gray-300" />
                            <div class="mt-2 flex items-center justify-center w-full">
                                <label for="empresa_logo" 
                                       class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-600 border-dashed rounded-xl cursor-pointer bg-gray-700/30 hover:bg-gray-700/50 transition-colors">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <span class="text-3xl mb-2">📷</span>
                                        <p class="text-sm text-gray-400">
                                            <span class="font-semibold text-blue-400">Click para subir</span> o arrastra aquí
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">PNG, JPG (max. 2MB)</p>
                                    </div>
                                    <input id="empresa_logo" type="file" class="hidden" @input="form.empresa_logo = $event.target.files[0]" accept="image/*" />
                                </label>
                            </div>
                            <p v-if="form.empresa_logo" class="text-xs text-green-400 mt-2">✓ Archivo seleccionado: {{ form.empresa_logo.name }}</p>
                        </div>
                    </div>

                    <!-- STEP 3: FISCAL -->
                    <div v-show="currentStep === 3" class="space-y-6 animate-slide-in">
                        <div class="bg-gradient-to-r from-blue-500/10 to-indigo-500/10 p-5 rounded-xl border border-blue-500/20">
                            <h3 class="text-blue-300 font-semibold mb-4 flex items-center gap-2">
                                📊 Configuración de IVA
                            </h3>
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <InputLabel for="iva_porcentaje" value="Tasa de IVA (%)" class="text-gray-300" />
                                    <TextInput id="iva_porcentaje" v-model="form.iva_porcentaje" type="number" step="0.01" 
                                               class="mt-2 block w-32 bg-gray-700/50 border-gray-600 text-2xl font-bold text-center" />
                                </div>
                                <div class="text-right">
                                    <span class="text-4xl text-gray-400">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h3 class="text-gray-400 font-medium text-sm uppercase tracking-wider">Retenciones (Opcional)</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Activa solo si tu empresa aplica retenciones de impuestos.</p>

                            <!-- ISR Switch -->
                            <div class="flex items-center justify-between bg-gray-700/30 p-4 rounded-xl hover:bg-gray-700/50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">💰</span>
                                    <div>
                                        <span class="text-gray-200 font-medium">ISR (Impuesto Sobre Renta)</span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Para reportes y cálculos</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.enable_isr" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white dark:bg-slate-900 after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </div>

                            <!-- Retencion IVA -->
                            <div class="bg-gray-700/30 p-4 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl">📉</span>
                                        <div>
                                            <span class="text-gray-200 font-medium">Retención de IVA</span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Se aplica a ciertos servicios</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.enable_retencion_iva" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white dark:bg-slate-900 after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                    </label>
                                </div>
                                <div v-if="form.enable_retencion_iva" class="mt-4 pl-11 animate-slide-down">
                                    <InputLabel for="retencion_iva_default" value="Porcentaje por defecto (%)" class="text-gray-400" />
                                    <TextInput id="retencion_iva_default" v-model="form.retencion_iva_default" type="number" step="0.01" 
                                               class="mt-2 block w-24 bg-gray-700 border-gray-600" />
                                </div>
                            </div>

                            <!-- Retencion ISR -->
                            <div class="bg-gray-700/30 p-4 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl">📈</span>
                                        <div>
                                            <span class="text-gray-200 font-medium">Retención de ISR</span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Para honorarios y servicios profesionales</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.enable_retencion_isr" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white dark:bg-slate-900 after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                    </label>
                                </div>
                                <div v-if="form.enable_retencion_isr" class="mt-4 pl-11 animate-slide-down">
                                    <InputLabel for="retencion_isr_default" value="Porcentaje por defecto (%)" class="text-gray-400" />
                                    <TextInput id="retencion_isr_default" v-model="form.retencion_isr_default" type="number" step="0.01" 
                                               class="mt-2 block w-24 bg-gray-700 border-gray-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: ALMACEN Y RESUMEN -->
                    <div v-show="currentStep === 4" class="space-y-6 animate-slide-in">
                        <div class="bg-gradient-to-r from-green-500/10 to-emerald-500/10 p-5 rounded-xl border border-green-500/20">
                            <h3 class="text-green-300 font-semibold mb-2 flex items-center gap-2">
                                🏭 Almacén Principal
                            </h3>
                            <p class="text-sm text-gray-400 mb-4">El sistema soporta múltiples almacenes. Este será el primero.</p>
                            <TextInput id="almacen_nombre" v-model="form.almacen_nombre" type="text" 
                                       class="block w-full bg-gray-700/50 border-gray-600" 
                                       placeholder="Ej. Bodega Central, Sucursal Matriz" required />
                        </div>

                        <!-- Resumen Final -->
                        <div class="bg-gray-700/30 rounded-xl overflow-hidden">
                            <div class="px-5 py-3 bg-gray-700/50 border-b border-gray-600">
                                <h4 class="text-white font-bold flex items-center gap-2">
                                    📋 Resumen de Instalación
                                </h4>
                            </div>
                            <div class="p-5 space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-700/50">
                                    <span class="text-gray-400">👤 Administrador</span>
                                    <span class="text-white font-medium">{{ form.admin_name || '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-700/50">
                                    <span class="text-gray-400">📧 Email</span>
                                    <span class="text-white font-medium">{{ form.admin_email || '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-700/50">
                                    <span class="text-gray-400">🏢 Empresa</span>
                                    <span class="text-white font-medium">{{ form.empresa_nombre || '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-700/50">
                                    <span class="text-gray-400">📍 Ubicación</span>
                                    <span class="text-white font-medium">{{ form.empresa_municipio }}, {{ form.empresa_estado }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-gray-400">💵 IVA</span>
                                    <span class="text-white font-medium">{{ form.iva_porcentaje }}%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center py-4">
                            <p class="text-gray-400 text-sm">
                                Al hacer clic en <span class="text-blue-400 font-medium">"Finalizar"</span>, 
                                se creará tu cuenta y empresa.
                            </p>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between mt-10 pt-6 border-t border-gray-700/50">
                        <!-- Input oculto para archivo de respaldo -->
                        <input 
                            type="file" 
                            id="backup-file-input" 
                            class="hidden" 
                            accept=".zip"
                            @change="handleBackupFile"
                        />

                        <button type="button" 
                                v-if="currentStep > 1"
                                @click="prevStep"
                                class="flex items-center gap-2 text-gray-400 hover:text-white font-medium transition-colors group">
                            <span class="group-hover:-translate-x-1 transition-transform">←</span>
                            Anterior
                        </button>
                        
                        <!-- Botón de restaurar respaldo solo en paso 1 -->
                        <div v-else class="flex flex-col items-start gap-2">
                            <button type="button"
                                    @click="backupFile ? restoreBackup() : triggerBackupInput()"
                                    :disabled="isRestoring"
                                    class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500/20 to-orange-500/20 text-amber-400 hover:text-amber-300 border border-amber-500/30 hover:border-amber-500/50 font-medium rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed group">
                                <span v-if="isRestoring" class="w-4 h-4 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></span>
                                <span v-else>📦</span>
                                <span v-if="isRestoring">Restaurando...</span>
                                <span v-else-if="backupFile">Restaurar: {{ backupFile.name }}</span>
                                <span v-else>Restaurar Respaldo</span>
                            </button>
                            
                            <!-- Cambiar archivo si ya hay uno seleccionado -->
                            <button v-if="backupFile && !isRestoring" 
                                    type="button"
                                    @click="triggerBackupInput"
                                    class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-400 underline">
                                Cambiar archivo
                            </button>
                            
                            <!-- Mensajes de error/éxito -->
                            <p v-if="restoreError" class="text-xs text-red-400">{{ restoreError }}</p>
                            <p v-if="restoreSuccess" class="text-xs text-green-400">{{ restoreSuccess }}</p>
                        </div>

                        <button v-if="currentStep < totalSteps" 
                                type="button" 
                                @click="nextStep"
                                :disabled="!canProceed"
                                class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 transition-all disabled:opacity-50 disabled:cursor-not-allowed group">
                            Siguiente
                            <span class="group-hover:translate-x-1 transition-transform">→</span>
                        </button>

                        <button v-else 
                                type="submit"
                                :disabled="form.processing || !canProceed"
                                class="flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-bold rounded-xl shadow-lg shadow-green-500/25 hover:shadow-green-500/40 transition-all disabled:opacity-50">
                            <span v-if="form.processing" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span v-else>🚀</span>
                            {{ form.processing ? 'Instalando...' : 'Finalizar e Instalar' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- Footer -->
        <p class="mt-8 text-gray-600 dark:text-gray-300 text-sm">
            Versión 2.0 • Sistema ERP
        </p>
    </div>
</template>

<style scoped>
@keyframes slideIn {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
.animate-slide-in {
    animation: slideIn 0.4s ease-out forwards;
}
.animate-slide-down {
    animation: slideDown 0.3s ease-out forwards;
}
.animate-shake {
    animation: shake 0.4s ease-out;
}
</style>
