<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    employee: Object, // Trae id, name, puesto, almacen, almacen_coords
    companyName: String,
    token: String,
    biometric: Object,
    checkTypes: Array,
    serverNowIso: String,
    suggestedType: String,
});

const gettingLocation = ref(false);
const geoMessage = ref('');
const liveClock = ref('');
let clockTimer = null;
let clockStartClientMs = Date.now();
let clockStartServerMs = Date.now();
const cameraSupported = ref(false);
const cameraMessage = ref('');
const cameraActive = ref(false);
const videoRef = ref(null);
const canvasRef = ref(null);
const selfiePreview = ref('');
let mediaStream = null;
const faceApiReady = ref(false);
const faceApiError = ref('');
const challengeStepIndex = ref(0);
const challengeSequence = ref([]);
const baselinePose = ref(null);
const liveDescriptor = ref(null);
const challengeStarted = ref(false);
const challengeCompleted = ref(false);
const livenessScore = ref(0);
let faceDetectionTimer = null;
const isEnrollment = computed(() => !props.biometric?.is_enrolled);
const challengeLabels = {
    left: 'Mira a la izquierda',
    right: 'Mira a la derecha',
    up: 'Levanta un poco la cabeza',
    down: 'Baja un poco la cabeza',
};
const currentChallengeKey = computed(() => challengeSequence.value[challengeStepIndex.value] || null);
const currentChallengeLabel = computed(() => {
    if (challengeCompleted.value) return 'Reto completado';
    if (!challengeStarted.value) return 'Centra tu cara en el marco';
    return challengeLabels[currentChallengeKey.value] || 'Preparando reto';
});

const form = useForm({
    tipo: props.suggestedType || 'entry',
    token: props.token || '',
    latitud: '',
    longitud: '',
    precision_metros: '',
    selfie: null,
    consentimiento: false,
    face_challenge_completed: false,
    face_liveness_score: '',
    face_descriptor: '',
    notas: '',
});

// Lógica de sugerencia de siguiente paso
const nextType = (current) => {
    if (current === 'entry') return 'break_start';
    if (current === 'break_start') return 'break_end';
    if (current === 'break_end') return 'exit';
    return 'entry';
};

const distanceToCenter = computed(() => {
    if (!form.latitud || !form.longitud || !props.employee?.almacen_coords) return null;
    const lat1 = props.employee.almacen_coords.lat;
    const lng1 = props.employee.almacen_coords.lng;
    const lat2 = parseFloat(form.latitud);
    const lng2 = parseFloat(form.longitud);
    
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return Math.round(R * c);
});

const isOutOfGeofence = computed(() => {
    if (distanceToCenter.value === null) return false;
    const radius = props.employee.almacen_coords.radius || 200;
    return distanceToCenter.value > radius;
});

const captureLocation = () => {
    if (!navigator.geolocation) {
        geoMessage.value = 'GPS no soportado.';
        return;
    }

    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitud = String(position.coords.latitude);
            form.longitud = String(position.coords.longitude);
            form.precision_metros = String(Math.round(position.coords.accuracy || 0));
            gettingLocation.value = false;
        },
        (error) => {
            geoMessage.value = 'Error de GPS. Activa la ubicación.';
            gettingLocation.value = false;
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
};

const stopCamera = () => {
    if (faceDetectionTimer) {
        clearInterval(faceDetectionTimer);
        faceDetectionTimer = null;
    }
    if (mediaStream) {
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    }
    cameraActive.value = false;
};

const loadScriptOnce = (src) => new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[src="${src}"]`);
    if (existing) {
        if (window.faceapi) resolve();
        else existing.addEventListener('load', () => resolve(), { once: true });
        return;
    }

    const script = document.createElement('script');
    script.src = src;
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('No se pudo cargar la librería facial.'));
    document.head.appendChild(script);
});

const buildChallenge = () => {
    const base = ['left', 'right', 'up', 'down'];
    if (isEnrollment.value) {
        challengeSequence.value = base;
    } else {
        const shuffled = [...base].sort(() => Math.random() - 0.5);
        challengeSequence.value = shuffled.slice(0, 2);
    }

    challengeStepIndex.value = 0;
    challengeStarted.value = false;
    challengeCompleted.value = false;
    baselinePose.value = null;
    livenessScore.value = 0;
    form.face_challenge_completed = false;
    form.face_liveness_score = '';
};

const avgPoint = (points) => {
    const sum = points.reduce((acc, p) => ({ x: acc.x + p.x, y: acc.y + p.y }), { x: 0, y: 0 });
    return { x: sum.x / points.length, y: sum.y / points.length };
};

const processChallenge = (landmarks) => {
    const leftEye = avgPoint(landmarks.getLeftEye());
    const rightEye = avgPoint(landmarks.getRightEye());
    const nose = landmarks.getNose()[3] || landmarks.getNose()[0];
    const eyeDist = Math.max(1, Math.abs(rightEye.x - leftEye.x));
    const eyeMid = { x: (leftEye.x + rightEye.x) / 2, y: (leftEye.y + rightEye.y) / 2 };
    const yaw = (nose.x - eyeMid.x) / eyeDist;
    const pitch = (nose.y - eyeMid.y) / eyeDist;

    if (!baselinePose.value) {
        if (Math.abs(yaw) < 0.12 && Math.abs(pitch) < 0.2) {
            baselinePose.value = { yaw, pitch };
            challengeStarted.value = true;
        }
        return;
    }

    if (challengeCompleted.value) return;

    const relYaw = yaw - baselinePose.value.yaw;
    const relPitch = pitch - baselinePose.value.pitch;
    const current = currentChallengeKey.value;

    let passed = false;
    if (current === 'left') passed = relYaw < -0.10;
    if (current === 'right') passed = relYaw > 0.10;
    if (current === 'up') passed = relPitch < -0.08;
    if (current === 'down') passed = relPitch > 0.08;

    if (!passed) return;

    challengeStepIndex.value += 1;
    livenessScore.value = challengeStepIndex.value / challengeSequence.value.length;
    form.face_liveness_score = livenessScore.value.toFixed(2);

    if (challengeStepIndex.value >= challengeSequence.value.length) {
        challengeCompleted.value = true;
        form.face_challenge_completed = true;
    }
};

const startFaceDetection = () => {
    if (!window.faceapi || !videoRef.value) return;
    if (faceDetectionTimer) clearInterval(faceDetectionTimer);

    faceDetectionTimer = setInterval(async () => {
        if (!cameraActive.value || !videoRef.value) return;
        try {
            const detection = await window.faceapi
                .detectSingleFace(videoRef.value, new window.faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }))
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) return;

            const descriptor = Array.from(detection.descriptor);
            liveDescriptor.value = descriptor;
            form.face_descriptor = JSON.stringify(descriptor);
            processChallenge(detection.landmarks);
        } catch (e) {
            // Ignore intermittent frame errors
        }
    }, 450);
};

const ensureFaceApi = async () => {
    if (faceApiReady.value) return true;
    try {
        await loadScriptOnce('https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js');
        const modelBase = 'https://justadudewhohacks.github.io/face-api.js/models';
        await Promise.all([
            window.faceapi.nets.tinyFaceDetector.loadFromUri(modelBase),
            window.faceapi.nets.faceLandmark68Net.loadFromUri(modelBase),
            window.faceapi.nets.faceRecognitionNet.loadFromUri(modelBase),
        ]);
        faceApiReady.value = true;
        return true;
    } catch (error) {
        faceApiError.value = 'No se pudieron cargar los modelos faciales. Revisa tu conexión.';
        return false;
    }
};

const openCamera = async () => {
    if (!navigator.mediaDevices?.getUserMedia) {
        cameraMessage.value = 'Cámara no soportada.';
        return;
    }

    const ready = await ensureFaceApi();
    if (!ready) return;
    buildChallenge();

    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user' },
            audio: false,
        });
        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
            await videoRef.value.play();
        }
        cameraActive.value = true;
        startFaceDetection();
    } catch (error) {
        cameraMessage.value = 'Sin permiso de cámara.';
    }
};

const captureSelfie = async () => {
    if (!videoRef.value || !canvasRef.value) return;
    if (!form.face_challenge_completed) {
        cameraMessage.value = 'Completa el reto de movimientos antes de tomar la foto.';
        return;
    }

    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    if (window.faceapi) {
        const detection = await window.faceapi
            .detectSingleFace(canvas, new window.faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }))
            .withFaceLandmarks()
            .withFaceDescriptor();
        if (!detection) {
            cameraMessage.value = 'No se detectó rostro válido en la selfie. Intenta de nuevo.';
            return;
        }

        const descriptor = Array.from(detection.descriptor);
        liveDescriptor.value = descriptor;
        form.face_descriptor = JSON.stringify(descriptor);
    }

    canvas.toBlob((blob) => {
        if (!blob) return;
        const file = new File([blob], `checkin-${Date.now()}.jpg`, { type: 'image/jpeg' });
        form.selfie = file;
        selfiePreview.value = URL.createObjectURL(file);
        stopCamera();
    }, 'image/jpeg', 0.8);
};

const refreshClock = () => {
    const elapsed = Date.now() - clockStartClientMs;
    const syncedNow = new Date(clockStartServerMs + elapsed);
    liveClock.value = syncedNow.toLocaleTimeString('es-MX', { 
        hour12: false, 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    });
};

const submit = () => {
    form.post(route('asistencia.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            const lastType = form.tipo;
            form.reset('selfie', 'consentimiento', 'notas', 'face_challenge_completed', 'face_liveness_score', 'face_descriptor');
            selfiePreview.value = '';
            form.tipo = nextType(lastType);
            buildChallenge();
            captureLocation();
        }
    });
};

onMounted(() => {
    if (props.serverNowIso) {
        clockStartServerMs = Date.parse(props.serverNowIso);
        clockStartClientMs = Date.now();
    }
    refreshClock();
    clockTimer = setInterval(refreshClock, 1000);
    cameraSupported.value = !!navigator.mediaDevices?.getUserMedia;
    buildChallenge();
    captureLocation();
});

onUnmounted(() => {
    if (clockTimer) clearInterval(clockTimer);
    stopCamera();
});
</script>

<template>
    <Head title="Checador de Asistencia" />

    <div class="min-h-screen bg-neutral-950 text-white flex flex-col items-center p-4">
        <div class="w-full max-w-lg space-y-6">
            
            <!-- Header Clock -->
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-900/40 to-black border border-white/10 rounded-[2.5rem] p-8 text-center shadow-2xl backdrop-blur-sm">
                <div class="relative z-10 text-[10px] text-blue-400 uppercase tracking-[0.2em] font-black mb-2">{{ companyName }}</div>
                <div class="relative z-10 text-6xl font-black text-white tracking-tighter tabular-nums mb-2">{{ liveClock }}</div>
                <div class="relative z-10 text-xs text-blue-200/60 font-medium">Registrando para <span class="text-white font-bold">{{ employee.name }}</span></div>
                
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-500/10 blur-[100px] rounded-full"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-indigo-500/10 blur-[100px] rounded-full"></div>
            </div>

            <!-- Status Badges -->
            <div class="grid grid-cols-2 gap-3">
                <div :class="['rounded-2xl border p-4 transition-all', form.latitud ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-white/5 border-white/10']">
                    <div class="flex items-center gap-3">
                        <div :class="['p-2 rounded-lg', form.latitud ? 'bg-emerald-500/20 text-emerald-400' : 'bg-white/10 text-neutral-500']">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg>
                        </div>
                        <div>
                            <div class="text-[9px] uppercase font-black text-neutral-500">UBICACIÓN</div>
                            <div class="text-[11px] font-bold">{{ form.latitud ? 'Capturada' : 'Buscando...' }}</div>
                        </div>
                    </div>
                </div>

                <div :class="['rounded-2xl border p-4 transition-all', form.selfie ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-white/5 border-white/10']">
                    <div class="flex items-center gap-3">
                        <div :class="['p-2 rounded-lg', form.selfie ? 'bg-emerald-500/20 text-emerald-400' : 'bg-white/10 text-neutral-500']">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /></svg>
                        </div>
                        <div>
                            <div class="text-[9px] uppercase font-black text-neutral-500">EVIDENCIA</div>
                            <div class="text-[11px] font-bold">{{ form.selfie ? 'Lista' : 'Pendiente' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Incidence Alert -->
            <div v-if="isOutOfGeofence" class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 flex items-start gap-4 animate-pulse">
                <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <div>
                    <div class="text-xs font-black text-amber-200 uppercase">Fuera de Zona</div>
                    <div class="text-[11px] text-amber-100/70">Estás a {{ distanceToCenter }}m del almacén {{ employee.almacen }}.</div>
                </div>
            </div>

            <!-- Main Form -->
            <form @submit.prevent="submit" class="bg-white/5 border border-white/10 rounded-[2.5rem] p-8 space-y-8 shadow-xl">
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-2xl p-4">
                    <div class="text-[10px] font-black uppercase tracking-widest text-blue-300">
                        {{ isEnrollment ? 'Primer registro facial' : 'Validación facial' }}
                    </div>
                    <p class="text-[11px] text-blue-100/80 mt-2">
                        {{ currentChallengeLabel }}. Progreso: {{ challengeStepIndex }}/{{ challengeSequence.length }}.
                    </p>
                    <p v-if="faceApiError" class="text-[10px] text-rose-300 mt-2">{{ faceApiError }}</p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <button 
                        v-for="type in checkTypes" 
                        :key="type.value"
                        type="button"
                        @click="form.tipo = type.value"
                        :class="[
                            'py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border-2',
                            form.tipo === type.value 
                                ? 'bg-blue-600 border-blue-400 shadow-lg shadow-blue-600/30 scale-105' 
                                : 'bg-white/5 border-white/5 text-neutral-500 hover:bg-white/10'
                        ]"
                    >
                        {{ type.label }}
                        <div v-if="suggestedType === type.value" class="text-[8px] mt-1 opacity-70">(Sugerido)</div>
                    </button>
                </div>

                <!-- Camera/Selfie -->
                <div class="space-y-4">
                    <div :class="['relative overflow-hidden rounded-[2rem] border-4 aspect-square transition-all bg-neutral-900', cameraActive ? 'border-blue-500/50' : 'border-white/5']">
                        <video v-show="cameraActive" ref="videoRef" autoplay muted playsinline class="w-full h-full object-cover"></video>
                        <canvas ref="canvasRef" class="hidden"></canvas>
                        
                        <img v-if="selfiePreview && !cameraActive" :src="selfiePreview" class="w-full h-full object-cover" />

                        <div v-if="!cameraActive && !selfiePreview" class="w-full h-full flex flex-col items-center justify-center text-neutral-600">
                            <svg class="w-12 h-12 mb-3 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /></svg>
                            <button type="button" @click="openCamera" class="text-[10px] font-black uppercase text-blue-400">Activar Cámara</button>
                        </div>

                        <div v-if="cameraActive" class="absolute bottom-6 inset-x-0 flex justify-center">
                            <button type="button" @click="captureSelfie" class="w-16 h-16 bg-white rounded-full border-8 border-white/20 active:scale-90 transition-transform"></button>
                        </div>

                        <button v-if="selfiePreview && !cameraActive" type="button" @click="openCamera" class="absolute top-4 right-4 bg-black/50 p-2 rounded-full text-white">
                             <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Terms -->
                <div class="space-y-4 pt-4 border-t border-white/10">
                    <div class="flex items-start gap-3">
                        <input id="consent" type="checkbox" v-model="form.consentimiento" class="mt-1 w-5 h-5 bg-black border-white/20 rounded text-blue-600 focus:ring-0">
                        <label for="consent" class="text-[11px] leading-relaxed text-neutral-400">
                            Certifico que este registro es real y consiento el uso de mi ubicación y fotografía para fines laborales.
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="space-y-4">
                    <button 
                        type="submit" 
                        :disabled="form.processing || !form.latitud || !form.selfie || !form.consentimiento || !form.face_challenge_completed || !form.face_descriptor"
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 disabled:from-neutral-800 disabled:to-neutral-800 disabled:text-neutral-600 py-6 rounded-[1.5rem] text-sm font-black uppercase tracking-widest shadow-2xl active:scale-[0.98] transition-all"
                    >
                        {{ form.processing ? 'Sincronizando...' : 'Confirmar Registro' }}
                    </button>
                    
                    <div v-if="$page.props.flash?.success" class="text-emerald-400 text-xs font-black text-center uppercase tracking-widest animate-pulse">
                        {{ $page.props.flash.success }}
                    </div>
                </div>
            </form>

            <div class="text-center pb-8 opacity-20">
                <div class="text-[10px] font-black uppercase tracking-[0.3em]">Asistencia Vircom</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media (max-width: 640px) {
    .text-6xl { font-size: 3.5rem; }
}
</style>
