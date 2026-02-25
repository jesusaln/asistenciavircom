<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    employee: Object,
    companyName: String,
    token: String,
    biometric: Object,
    checkTypes: Array,
    serverNowIso: String,
    suggestedType: String,
    todayRecords: { type: Array, default: () => [] },
    todaySummary: { type: Object, default: () => ({ workedMinutes: 0, breakMinutes: 0, totalChecks: 0, hasIncidence: false }) },
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
const qualityCanvas = typeof document !== 'undefined' ? document.createElement('canvas') : null;
let originalGetContext = null;
const faceApiReady = ref(false);
const faceApiError = ref('');
const challengeStepIndex = ref(0);
const challengeSequence = ref([]);
const baselinePose = ref(null);
const liveDescriptor = ref(null);
const challengeStarted = ref(false);
const challengeCompleted = ref(false);
const livenessScore = ref(0);
const faceCount = ref(0);
const mirroredPreview = true;
const eyesOpen = ref(false);
const autoCaptureRunning = ref(false);
const countdown = ref(null);
const captureQuality = ref({
    brightness: 0, sharpness: 0, faceAreaRatio: 0, centerOffset: 1,
    sizePass: false, centerPass: false, singleFace: false, passed: false,
    message: 'Activa cámara para validar calidad',
});
let faceDetectionTimer = null;
let countdownInterval = null;
let isDetectingFrame = false;
const isEnrollment = computed(() => !props.biometric?.is_enrolled);
const challengeLabels = {
    left: '← Mira a la izquierda',
    right: 'Mira a la derecha →',
    up: '↑ Levanta la cabeza',
    down: '↓ Baja la cabeza',
};
const currentChallengeKey = computed(() => challengeSequence.value[challengeStepIndex.value] || null);
const currentChallengeLabel = computed(() => {
    if (challengeCompleted.value) return '✓ Reto completado';
    if (!challengeStarted.value) return 'Centra tu cara en el marco';
    return challengeLabels[currentChallengeKey.value] || 'Preparando...';
});
const challengeProgressPct = computed(() => {
    if (!challengeSequence.value.length) return 0;
    return Math.round((challengeStepIndex.value / challengeSequence.value.length) * 100);
});
const faceInsideOval = computed(() => cameraActive.value && captureQuality.value.singleFace && captureQuality.value.sizePass && captureQuality.value.centerPass);
const readyForAutoCapture = computed(() => faceInsideOval.value && captureQuality.value.passed && form.face_challenge_completed && eyesOpen.value);
const successPulse = ref(false);
let successPulseTimer = null;
const showNotes = ref(false);
const showConfetti = ref(false);
const confettiCanvasRef = ref(null);
let confettiAnimFrame = null;

// Audio feedback
const playBeep = (success = true) => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.value = success ? 880 : 330;
        osc.type = success ? 'sine' : 'triangle';
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (success ? 0.3 : 0.5));
        osc.start(ctx.currentTime); osc.stop(ctx.currentTime + (success ? 0.3 : 0.5));
        if (success) {
            const osc2 = ctx.createOscillator(); const gain2 = ctx.createGain();
            osc2.connect(gain2); gain2.connect(ctx.destination);
            osc2.frequency.value = 1320; osc2.type = 'sine';
            gain2.gain.setValueAtTime(0.12, ctx.currentTime + 0.15);
            gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
            osc2.start(ctx.currentTime + 0.15); osc2.stop(ctx.currentTime + 0.45);
        }
    } catch (e) { /* audio not supported */ }
};

const vibrate = (pattern) => {
    try { navigator.vibrate?.(pattern); } catch (e) { /* no vibration */ }
};

// Confetti animation
const launchConfetti = () => {
    showConfetti.value = true;
    const canvas = confettiCanvasRef.value;
    if (!canvas) { setTimeout(() => { showConfetti.value = false; }, 3000); return; }
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth; canvas.height = window.innerHeight;
    const particles = [];
    const colors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899'];
    for (let i = 0; i < 120; i++) {
        particles.push({
            x: canvas.width / 2 + (Math.random() - 0.5) * 200,
            y: canvas.height / 2,
            vx: (Math.random() - 0.5) * 12,
            vy: Math.random() * -14 - 4,
            size: Math.random() * 6 + 3,
            color: colors[Math.floor(Math.random() * colors.length)],
            rotation: Math.random() * 360,
            rotationSpeed: (Math.random() - 0.5) * 10,
            opacity: 1,
        });
    }
    let frame = 0;
    const animate = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach(p => {
            p.x += p.vx; p.y += p.vy; p.vy += 0.25; p.rotation += p.rotationSpeed;
            p.opacity = Math.max(0, p.opacity - 0.008);
            ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.rotation * Math.PI / 180);
            ctx.globalAlpha = p.opacity; ctx.fillStyle = p.color;
            ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
            ctx.restore();
        });
        frame++;
        if (frame < 180 && particles.some(p => p.opacity > 0)) {
            confettiAnimFrame = requestAnimationFrame(animate);
        } else {
            showConfetti.value = false;
        }
    };
    confettiAnimFrame = requestAnimationFrame(animate);
};

// Humanized quality message
const qualityHumanMessage = computed(() => {
    const q = captureQuality.value;
    if (!cameraActive.value) return { icon: '📷', text: 'Activa la cámara', color: 'text-neutral-400' };
    if (!q.singleFace && faceCount.value === 0) return { icon: '👤', text: 'Acerca tu rostro', color: 'text-amber-300' };
    if (faceCount.value > 1) return { icon: '⚠️', text: 'Solo 1 persona', color: 'text-rose-300' };
    if (!q.sizePass) return { icon: '↔️', text: 'Ajusta distancia', color: 'text-amber-300' };
    if (!q.centerPass) return { icon: '🎯', text: 'Centra tu rostro', color: 'text-amber-300' };
    if (!eyesOpen.value) return { icon: '👁️', text: 'Ojos abiertos', color: 'text-amber-300' };
    if (q.passed) return { icon: '✅', text: 'Listo para captura', color: 'text-emerald-400' };
    return { icon: '⏳', text: q.message, color: 'text-amber-300' };
});

// Day timeline helpers
const formatTime = (dateStr) => {
    const d = new Date(dateStr);
    return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false });
};
const tipoIcon = (tipo) => ({ entry: '🟢', exit: '🔴', break_start: '☕', break_end: '▶️' }[tipo] || '⬜');
const tipoLabel = (tipo) => ({ entry: 'Entrada', exit: 'Salida', break_start: 'Descanso', break_end: 'Regreso' }[tipo] || tipo);
const formatWorkedTime = (mins) => {
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return `${h}h ${m.toString().padStart(2, '0')}m`;
};

const form = useForm({
    tipo: props.suggestedType || 'entry',
    token: props.token || '',
    latitud: '', longitud: '', precision_metros: '',
    selfie: null, consentimiento: true,
    face_challenge_completed: false, face_liveness_score: '', face_descriptor: '',
    face_detected_count: 0, face_capture_quality_passed: false,
    face_quality_brightness: '', face_quality_sharpness: '',
    face_quality_area_ratio: '', face_quality_center_offset: '', face_quality_message: '',
    notas: '',
});

const nextType = (current) => {
    if (current === 'entry') return 'break_start';
    if (current === 'break_start') return 'break_end';
    if (current === 'break_end') return 'exit';
    return 'entry';
};

const distanceToCenter = computed(() => {
    if (!form.latitud || !form.longitud || !props.employee?.almacen_coords) return null;
    const { lat: lat1, lng: lng1 } = props.employee.almacen_coords;
    const lat2 = parseFloat(form.latitud), lng2 = parseFloat(form.longitud);
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180, dLon = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
    return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
});

const isOutOfGeofence = computed(() => {
    if (distanceToCenter.value === null) return false;
    return distanceToCenter.value > (props.employee.almacen_coords?.radius || 200);
});

const captureLocation = () => {
    if (!navigator.geolocation) { geoMessage.value = 'GPS no soportado.'; return; }
    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitud = String(pos.coords.latitude);
            form.longitud = String(pos.coords.longitude);
            form.precision_metros = String(Math.round(pos.coords.accuracy || 0));
            gettingLocation.value = false;
        },
        () => { geoMessage.value = 'Error de GPS. Activa la ubicación.'; gettingLocation.value = false; },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
};

const stopCamera = () => {
    if (faceDetectionTimer) { clearInterval(faceDetectionTimer); faceDetectionTimer = null; }
    if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
    countdown.value = null;
    if (mediaStream) { mediaStream.getTracks().forEach(t => t.stop()); mediaStream = null; }
    cameraActive.value = false;
};

const clearSelfiePreview = () => {
    if (selfiePreview.value) { URL.revokeObjectURL(selfiePreview.value); selfiePreview.value = ''; }
};

const loadScriptOnce = (src) => new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[src="${src}"]`);
    if (existing) { window.faceapi ? resolve() : existing.addEventListener('load', () => resolve(), { once: true }); return; }
    const script = document.createElement('script');
    script.src = src; script.async = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('No se pudo cargar la librería facial.'));
    document.head.appendChild(script);
});

const enableFrequentReadCanvas = () => {
    if (typeof HTMLCanvasElement === 'undefined' || originalGetContext) return;
    originalGetContext = HTMLCanvasElement.prototype.getContext;
    HTMLCanvasElement.prototype.getContext = function patchedGetContext(type, options) {
        if (type !== '2d') return originalGetContext.call(this, type, options);
        if (options && typeof options === 'object' && Object.prototype.hasOwnProperty.call(options, 'willReadFrequently'))
            return originalGetContext.call(this, type, options);
        const nextOptions = options && typeof options === 'object' ? { ...options, willReadFrequently: true } : { willReadFrequently: true };
        return originalGetContext.call(this, type, nextOptions);
    };
};

const restoreCanvasGetContext = () => {
    if (!originalGetContext || typeof HTMLCanvasElement === 'undefined') return;
    HTMLCanvasElement.prototype.getContext = originalGetContext;
    originalGetContext = null;
};

const buildChallenge = () => {
    const base = ['left', 'right', 'up', 'down'];
    challengeSequence.value = isEnrollment.value ? base : [...base].sort(() => Math.random() - 0.5).slice(0, 2);
    challengeStepIndex.value = 0;
    challengeStarted.value = false; challengeCompleted.value = false;
    baselinePose.value = null; livenessScore.value = 0; faceCount.value = 0;
    form.face_challenge_completed = false; form.face_liveness_score = ''; form.face_descriptor = '';
    form.face_detected_count = 0; form.face_capture_quality_passed = false;
    form.face_quality_brightness = ''; form.face_quality_sharpness = '';
    form.face_quality_area_ratio = ''; form.face_quality_center_offset = ''; form.face_quality_message = '';
    captureQuality.value = { brightness: 0, sharpness: 0, faceAreaRatio: 0, centerOffset: 1, sizePass: false, centerPass: false, singleFace: false, passed: false, message: 'Esperando rostro...' };
};

const avgPoint = (pts) => { const s = pts.reduce((a, p) => ({ x: a.x + p.x, y: a.y + p.y }), { x: 0, y: 0 }); return { x: s.x / pts.length, y: s.y / pts.length }; };
const pointDistance = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);

const eyeAspectRatio = (eyePts = []) => {
    if (eyePts.length < 6) return 0;
    const h = Math.max(0.0001, pointDistance(eyePts[0], eyePts[3]));
    return (pointDistance(eyePts[1], eyePts[5]) + pointDistance(eyePts[2], eyePts[4])) / (2 * h);
};

const detectEyesOpen = (lm) => {
    if (!lm) return false;
    return ((eyeAspectRatio(lm.getLeftEye()) + eyeAspectRatio(lm.getRightEye())) / 2) >= 0.14;
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
        if (Math.abs(yaw) < 0.12 && Math.abs(pitch) < 0.2) { baselinePose.value = { yaw, pitch }; challengeStarted.value = true; }
        return;
    }
    if (challengeCompleted.value) return;

    const relYaw = yaw - baselinePose.value.yaw, relPitch = pitch - baselinePose.value.pitch;
    const current = currentChallengeKey.value;
    let passed = false;
    if (current === 'left') passed = mirroredPreview ? relYaw > 0.10 : relYaw < -0.10;
    if (current === 'right') passed = mirroredPreview ? relYaw < -0.10 : relYaw > 0.10;
    if (current === 'up') passed = relPitch < -0.08;
    if (current === 'down') passed = relPitch > 0.08;
    if (!passed) return;

    challengeStepIndex.value += 1;
    livenessScore.value = challengeStepIndex.value / challengeSequence.value.length;
    form.face_liveness_score = livenessScore.value.toFixed(2);
    if (challengeStepIndex.value >= challengeSequence.value.length) {
        challengeCompleted.value = true; form.face_challenge_completed = true;
    }
};

const frameStats = (source, box) => {
    if (!qualityCanvas) return null;
    const w = source.videoWidth || source.width || 0, h = source.videoHeight || source.height || 0;
    if (!w || !h) return null;
    qualityCanvas.width = 160; qualityCanvas.height = 120;
    const qCtx = qualityCanvas.getContext('2d', { willReadFrequently: true });
    qCtx.drawImage(source, 0, 0, qualityCanvas.width, qualityCanvas.height);
    const img = qCtx.getImageData(0, 0, qualityCanvas.width, qualityCanvas.height).data;
    let brightnessSum = 0, gradientSum = 0, samples = 0;
    const stride = 4, rowPx = qualityCanvas.width;
    for (let y = 1; y < qualityCanvas.height - 1; y++) {
        for (let x = 1; x < qualityCanvas.width - 1; x++) {
            const i = (y * rowPx + x) * stride;
            const lum = 0.299 * img[i] + 0.587 * img[i + 1] + 0.114 * img[i + 2];
            const lumR = 0.299 * img[i + stride] + 0.587 * img[i + stride + 1] + 0.114 * img[i + stride + 2];
            const lumD = 0.299 * img[i + rowPx * stride] + 0.587 * img[i + rowPx * stride + 1] + 0.114 * img[i + rowPx * stride + 2];
            brightnessSum += lum; gradientSum += Math.abs(lum - lumR) + Math.abs(lum - lumD); samples++;
        }
    }
    const brightness = samples ? (brightnessSum / samples) / 255 : 0;
    const sharpness = samples ? (gradientSum / samples) / 255 : 0;
    const area = Math.max(1, w * h);
    const faceAreaRatio = (box.width * box.height) / area;
    const faceCx = box.x + box.width / 2, faceCy = box.y + box.height / 2;
    const centerOffset = Math.hypot(faceCx - w / 2, faceCy - h / 2) / Math.hypot(w / 2, h / 2);
    return { brightness, sharpness, faceAreaRatio, centerOffset };
};

const evaluateQuality = (source, box, facesLength) => {
    faceCount.value = facesLength; form.face_detected_count = facesLength;
    if (facesLength === 0) { form.face_capture_quality_passed = false; captureQuality.value = { ...captureQuality.value, sizePass: false, centerPass: false, singleFace: false, passed: false, message: 'No se detecta rostro' }; return false; }
    if (facesLength > 1) { form.face_capture_quality_passed = false; captureQuality.value = { ...captureQuality.value, singleFace: false, passed: false, message: 'Solo 1 rostro' }; return false; }
    const stats = frameStats(source, box);
    if (!stats) { captureQuality.value = { ...captureQuality.value, passed: false, message: 'No se pudo evaluar' }; return false; }
    const lightingPass = stats.brightness >= 0.10 && stats.brightness <= 0.95;
    const sharpnessPass = stats.sharpness >= 0.02;
    const sizePass = stats.faceAreaRatio >= 0.05 && stats.faceAreaRatio <= 0.80;
    const centerPass = stats.centerOffset <= 0.45;
    const passed = lightingPass && sharpnessPass && sizePass && centerPass;
    let message = 'Calidad correcta';
    if (!lightingPass) message = 'Ajusta iluminación';
    else if (!sharpnessPass) message = 'Evita movimiento';
    else if (!sizePass) message = 'Ajusta distancia';
    else if (!centerPass) message = 'Centra tu rostro';
    captureQuality.value = { ...stats, sizePass, centerPass, singleFace: true, passed, message };
    form.face_capture_quality_passed = passed;
    form.face_quality_brightness = stats.brightness.toFixed(4);
    form.face_quality_sharpness = stats.sharpness.toFixed(4);
    form.face_quality_area_ratio = stats.faceAreaRatio.toFixed(4);
    form.face_quality_center_offset = stats.centerOffset.toFixed(4);
    form.face_quality_message = message;
    return passed;
};

const startFaceDetection = () => {
    if (!window.faceapi || !videoRef.value) return;
    if (faceDetectionTimer) clearInterval(faceDetectionTimer);
    faceDetectionTimer = setInterval(async () => {
        if (!cameraActive.value || !videoRef.value || isDetectingFrame) return;
        isDetectingFrame = true;
        try {
            const detections = await window.faceapi.detectAllFaces(videoRef.value, new window.faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 })).withFaceLandmarks().withFaceDescriptors();
            if (!detections?.length) { eyesOpen.value = false; evaluateQuality(videoRef.value, { x: 0, y: 0, width: 0, height: 0 }, 0); return; }
            const primary = detections.reduce((best, cur) => (cur.detection.box.width * cur.detection.box.height > best.detection.box.width * best.detection.box.height ? cur : best), detections[0]);
            const qualityPass = evaluateQuality(videoRef.value, primary.detection.box, detections.length);
            eyesOpen.value = detectEyesOpen(primary.landmarks);
            processChallenge(primary.landmarks);
            if (!qualityPass) { form.face_descriptor = ''; return; }
            if (!eyesOpen.value) { form.face_descriptor = ''; captureQuality.value = { ...captureQuality.value, passed: false, message: 'Ojos abiertos' }; form.face_capture_quality_passed = false; return; }
            const descriptor = Array.from(primary.descriptor);
            liveDescriptor.value = descriptor; form.face_descriptor = JSON.stringify(descriptor);

            if (readyForAutoCapture.value && !autoCaptureRunning.value && !form.selfie && !countdown.value) {
                cameraMessage.value = 'Rostro detectado...';
                countdown.value = 3;
                if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
                countdownInterval = setInterval(async () => {
                    countdown.value--;
                    if (countdown.value <= 0) {
                        clearInterval(countdownInterval); countdownInterval = null; countdown.value = null;
                        if (readyForAutoCapture.value && !form.selfie) {
                            autoCaptureRunning.value = true; cameraMessage.value = 'Capturando...';
                            try { await captureSelfie(); } finally { autoCaptureRunning.value = false; }
                        }
                    }
                }, 1000);
            }
        } catch (e) { /* frame error */ } finally { isDetectingFrame = false; }
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
        faceApiReady.value = true; return true;
    } catch { faceApiError.value = 'No se pudieron cargar los modelos faciales.'; return false; }
};

const openCamera = async () => {
    if (!navigator.mediaDevices?.getUserMedia) { cameraMessage.value = 'Cámara no soportada.'; return; }
    const ready = await ensureFaceApi(); if (!ready) return;
    buildChallenge();
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
        if (videoRef.value) { videoRef.value.srcObject = mediaStream; await videoRef.value.play(); }
        cameraMessage.value = ''; cameraActive.value = true; startFaceDetection();
    } catch { cameraMessage.value = 'Sin permiso de cámara.'; }
};

const openCameraAutomatically = async () => {
    if (!cameraSupported.value || cameraActive.value || form.selfie) return;
    await openCamera();
};

const captureLocationAsync = () => new Promise((resolve) => {
    if (!navigator.geolocation) { geoMessage.value = 'GPS no soportado.'; resolve(false); return; }
    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => { form.latitud = String(pos.coords.latitude); form.longitud = String(pos.coords.longitude); form.precision_metros = String(Math.round(pos.coords.accuracy || 0)); gettingLocation.value = false; resolve(true); },
        () => { geoMessage.value = 'Error de GPS.'; gettingLocation.value = false; resolve(false); },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
});

const captureSelfie = async (manual = false) => {
    cameraMessage.value = '';
    if (!videoRef.value || !canvasRef.value) return;
    if (!manual) {
        if (!form.face_challenge_completed) { cameraMessage.value = 'Completa el reto de movimientos.'; return; }
        if (!captureQuality.value.passed) { cameraMessage.value = captureQuality.value.message || 'Mejora la calidad.'; return; }
        if (!eyesOpen.value) { cameraMessage.value = 'Mantén los ojos abiertos.'; return; }
    } else {
        if (form.face_detected_count === 0) { cameraMessage.value = 'Debe haber al menos un rostro.'; return; }
        if (!form.face_challenge_completed) {
            form.face_liveness_score = Math.max(Number(form.face_liveness_score || 0), 0.55).toFixed(2);
            form.face_quality_message = 'Captura manual.';
        }
    }

    const video = videoRef.value, canvas = canvasRef.value;
    canvas.width = video.videoWidth || 640; canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

    if (window.faceapi) {
        const detections = await window.faceapi.detectAllFaces(canvas, new window.faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 })).withFaceLandmarks().withFaceDescriptors();
        if (!detections?.length) { cameraMessage.value = 'No se detectó rostro. Intenta de nuevo.'; return; }
        const primary = detections.reduce((best, cur) => (cur.detection.box.width * cur.detection.box.height > best.detection.box.width * best.detection.box.height ? cur : best), detections[0]);
        if (!manual) {
            if (!evaluateQuality(canvas, primary.detection.box, detections.length)) { cameraMessage.value = captureQuality.value.message; return; }
            if (!detectEyesOpen(primary.landmarks)) { cameraMessage.value = 'No cierres los ojos.'; return; }
        } else { evaluateQuality(canvas, primary.detection.box, detections.length); }
        const descriptor = Array.from(primary.descriptor);
        liveDescriptor.value = descriptor; form.face_descriptor = JSON.stringify(descriptor);
    }

    canvas.toBlob(async (blob) => {
        if (!blob) return;
        const file = new File([blob], `checkin-${Date.now()}.jpg`, { type: 'image/jpeg' });
        form.selfie = file; clearSelfiePreview();
        selfiePreview.value = URL.createObjectURL(file);
        cameraMessage.value = 'Foto capturada ✓'; stopCamera();

        if (!form.latitud || !form.longitud) {
            cameraMessage.value = 'Obteniendo ubicación...';
            if (!(await captureLocationAsync())) { cameraMessage.value = 'No se pudo obtener GPS.'; return; }
        }
        cameraMessage.value = 'Enviando registro...'; submit();
    }, 'image/jpeg', 0.8);
};

const refreshClock = () => {
    const elapsed = Date.now() - clockStartClientMs;
    const syncedNow = new Date(clockStartServerMs + elapsed);
    liveClock.value = syncedNow.toLocaleTimeString('es-MX', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

const submit = () => {
    if (form.processing) return;
    form.consentimiento = true;
    const wasEnrollment = isEnrollment.value;
    form.post(route('asistencia.store'), {
        forceFormData: true, preserveScroll: true,
        onSuccess: () => {
            playBeep(true); vibrate([100, 50, 100]);
            successPulse.value = true;
            if (successPulseTimer) clearTimeout(successPulseTimer);
            successPulseTimer = setTimeout(() => { successPulse.value = false; }, 2500);
            if (wasEnrollment) { launchConfetti(); }
            const lastType = form.tipo;
            form.reset('selfie', 'consentimiento', 'notas', 'face_challenge_completed', 'face_liveness_score', 'face_descriptor', 'face_detected_count', 'face_capture_quality_passed', 'face_quality_brightness', 'face_quality_sharpness', 'face_quality_area_ratio', 'face_quality_center_offset', 'face_quality_message');
            clearSelfiePreview(); showNotes.value = false;
            form.tipo = nextType(lastType); buildChallenge(); captureLocation(); openCameraAutomatically(); cameraMessage.value = '';
        },
        onError: () => {
            playBeep(false); vibrate([200, 100, 200, 100, 200]);
            cameraMessage.value = 'No se pudo registrar. Revisa GPS/rostro.';
        }
    });
};

onMounted(() => {
    enableFrequentReadCanvas();
    if (props.serverNowIso) { clockStartServerMs = Date.parse(props.serverNowIso); clockStartClientMs = Date.now(); }
    refreshClock(); clockTimer = setInterval(refreshClock, 1000);
    cameraSupported.value = !!navigator.mediaDevices?.getUserMedia;
    buildChallenge(); captureLocation(); openCameraAutomatically();
});

onUnmounted(() => {
    if (clockTimer) clearInterval(clockTimer);
    if (successPulseTimer) clearTimeout(successPulseTimer);
    if (confettiAnimFrame) cancelAnimationFrame(confettiAnimFrame);
    stopCamera(); clearSelfiePreview(); restoreCanvasGetContext();
});
</script>

<template>
    <Head title="Checador de Asistencia" />

    <div class="min-h-screen bg-[#0a0a0f] text-white flex flex-col items-center p-4 font-['Outfit',sans-serif]">
        <!-- Success Toast -->
        <transition name="success-pop">
            <div v-if="successPulse" class="fixed top-6 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 text-[11px] font-black uppercase tracking-widest bg-gradient-to-r from-emerald-500 to-emerald-600 text-black shadow-emerald-500/40">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                Registro confirmado
            </div>
        </transition>

        <div class="w-full max-w-lg space-y-5">

            <!-- ═══════ HERO CLOCK ═══════ -->
            <div class="relative overflow-hidden rounded-[2rem] p-8 text-center border border-white/[0.06]" style="background: linear-gradient(135deg, rgba(59,130,246,0.12) 0%, rgba(15,15,25,0.95) 50%, rgba(139,92,246,0.08) 100%);">
                <div class="absolute -top-20 -right-20 w-56 h-56 bg-blue-500/10 blur-[80px] rounded-full"></div>
                <div class="absolute -bottom-20 -left-20 w-56 h-56 bg-violet-500/8 blur-[80px] rounded-full"></div>

                <div class="relative z-10">
                    <div class="text-[9px] text-blue-400/80 uppercase tracking-[0.25em] font-extrabold mb-3">{{ companyName }}</div>
                    <div class="text-[4rem] sm:text-[5rem] font-black text-white tracking-[-0.04em] tabular-nums leading-none mb-1" style="text-shadow: 0 0 40px rgba(59,130,246,0.15);">{{ liveClock }}</div>
                    <div class="flex items-center justify-center gap-2 mt-3">
                        <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center overflow-hidden">
                            <img v-if="employee.profilePhoto" :src="employee.profilePhoto" class="w-full h-full object-cover" />
                            <span v-else class="text-[10px] font-black text-neutral-500">{{ employee.name?.[0] }}</span>
                        </div>
                        <div class="text-left">
                            <div class="text-xs font-bold text-white leading-tight">{{ employee.name }}</div>
                            <div class="text-[9px] text-neutral-500 font-medium">{{ employee.puesto || employee.almacen || 'Empleado' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════ TODAY SUMMARY ═══════ -->
            <div v-if="todayRecords.length > 0" class="rounded-[1.5rem] border border-white/[0.06] overflow-hidden" style="background: linear-gradient(180deg, rgba(255,255,255,0.03) 0%, rgba(0,0,0,0.4) 100%);">
                <div class="px-5 py-3 border-b border-white/[0.05] flex items-center justify-between">
                    <div class="text-[9px] font-black uppercase tracking-[0.2em] text-neutral-500">Tu Día</div>
                    <div class="flex items-center gap-3">
                        <div class="text-[10px] font-bold text-blue-400 tabular-nums">{{ formatWorkedTime(todaySummary.workedMinutes) }}</div>
                        <div v-if="todaySummary.breakMinutes > 0" class="text-[9px] text-neutral-600 font-medium">☕ {{ todaySummary.breakMinutes }}m</div>
                        <div v-if="todaySummary.hasIncidence" class="text-amber-500 text-[9px]">⚠️</div>
                    </div>
                </div>

                <div class="px-5 py-3 space-y-0">
                    <div v-for="(rec, idx) in todayRecords" :key="rec.id" class="flex items-center gap-3 py-1.5 group">
                        <!-- Timeline line -->
                        <div class="flex flex-col items-center w-5">
                            <div v-if="idx > 0" class="w-px h-2 bg-white/10"></div>
                            <div class="text-xs">{{ tipoIcon(rec.tipo) }}</div>
                            <div v-if="idx < todayRecords.length - 1" class="w-px h-2 bg-white/10"></div>
                        </div>
                        <div class="flex-1 flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-neutral-300">{{ tipoLabel(rec.tipo) }}</span>
                            <div class="flex items-center gap-2">
                                <span v-if="rec.es_incidencia" class="text-[8px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 font-bold uppercase">Inc.</span>
                                <span :class="['text-[8px] px-1.5 py-0.5 rounded font-bold uppercase', rec.face_verified ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400']">{{ rec.face_verified ? '✓ ID' : '✗ ID' }}</span>
                                <span class="text-[11px] font-mono text-neutral-500 tabular-nums">{{ formatTime(rec.registrado_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════ STATUS BADGES ═══════ -->
            <div class="grid grid-cols-2 gap-3">
                <div :class="['rounded-2xl border p-4 transition-all duration-300', form.latitud ? 'bg-emerald-500/[0.06] border-emerald-500/20' : 'bg-white/[0.02] border-white/[0.06]']">
                    <div class="flex items-center gap-3">
                        <div :class="['w-9 h-9 rounded-xl flex items-center justify-center text-sm transition-all', form.latitud ? 'bg-emerald-500/15 text-emerald-400' : 'bg-white/5 text-neutral-600']">📍</div>
                        <div>
                            <div class="text-[8px] uppercase font-extrabold tracking-[0.15em] text-neutral-600">Ubicación</div>
                            <div class="text-[11px] font-bold">{{ gettingLocation ? 'Buscando...' : (form.latitud ? 'Capturada' : 'Pendiente') }}</div>
                        </div>
                    </div>
                </div>
                <div :class="['rounded-2xl border p-4 transition-all duration-300', form.selfie ? 'bg-emerald-500/[0.06] border-emerald-500/20' : 'bg-white/[0.02] border-white/[0.06]']">
                    <div class="flex items-center gap-3">
                        <div :class="['w-9 h-9 rounded-xl flex items-center justify-center text-sm transition-all', form.selfie ? 'bg-emerald-500/15 text-emerald-400' : 'bg-white/5 text-neutral-600']">📸</div>
                        <div>
                            <div class="text-[8px] uppercase font-extrabold tracking-[0.15em] text-neutral-600">Evidencia</div>
                            <div class="text-[11px] font-bold">{{ form.selfie ? 'Lista' : 'Pendiente' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════ GEOFENCE WARNING ═══════ -->
            <div v-if="isOutOfGeofence" class="rounded-2xl p-4 flex items-start gap-4 border border-amber-500/20 bg-amber-500/[0.06]">
                <div class="text-lg mt-0.5">⚠️</div>
                <div>
                    <div class="text-[10px] font-black text-amber-300 uppercase tracking-wide">Fuera de Zona</div>
                    <div class="text-[11px] text-amber-200/70 mt-0.5">Estás a {{ distanceToCenter }}m de {{ employee.almacen }}.</div>
                </div>
            </div>

            <!-- ═══════ MAIN FORM ═══════ -->
            <form @submit.prevent="submit" class="rounded-[2rem] p-6 sm:p-8 space-y-6 border border-white/[0.06]" style="background: linear-gradient(180deg, rgba(255,255,255,0.025) 0%, rgba(0,0,0,0.3) 100%);">

                <!-- Face Challenge Panel -->
                <div class="rounded-2xl p-4 border border-blue-500/15 bg-blue-500/[0.04]">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-blue-400">
                            {{ isEnrollment ? 'Registro Facial Inicial' : 'Verificación Facial' }}
                        </div>
                        <div class="text-[9px] font-bold tabular-nums" :class="challengeCompleted ? 'text-emerald-400' : 'text-neutral-500'">
                            {{ challengeStepIndex }}/{{ challengeSequence.length }}
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="h-1 rounded-full bg-black/30 overflow-hidden mb-2">
                        <div class="h-full rounded-full transition-all duration-500 ease-out" :class="challengeCompleted ? 'bg-emerald-400' : 'bg-blue-500'" :style="{ width: `${challengeProgressPct}%` }"></div>
                    </div>

                    <div class="flex items-center justify-between text-[10px]">
                        <span :class="challengeCompleted ? 'text-emerald-300 font-bold' : (challengeStarted ? 'text-blue-200' : 'text-amber-300')">{{ currentChallengeLabel }}</span>
                        <span :class="qualityHumanMessage.color" class="font-medium">{{ qualityHumanMessage.icon }} {{ qualityHumanMessage.text }}</span>
                    </div>

                    <p v-if="faceApiError" class="text-[10px] text-rose-400 mt-2 font-medium">{{ faceApiError }}</p>
                </div>

                <!-- Type Selector -->
                <div class="grid grid-cols-2 gap-3">
                    <button
                        v-for="type in checkTypes" :key="type.value" type="button" @click="form.tipo = type.value"
                        :class="['py-3.5 rounded-2xl text-[9px] font-extrabold uppercase tracking-[0.12em] transition-all duration-200 border-2', form.tipo === type.value ? 'bg-blue-600 border-blue-400/60 shadow-lg shadow-blue-600/25 scale-[1.03]' : 'bg-white/[0.02] border-white/[0.04] text-neutral-500 hover:bg-white/[0.05] hover:border-white/10']"
                    >
                        {{ type.label }}
                        <div v-if="suggestedType === type.value" class="text-[7px] mt-0.5 opacity-60 normal-case tracking-normal font-bold">Sugerido</div>
                    </button>
                </div>

                <!-- ═══════ CAMERA VIEWFINDER ═══════ -->
                <div class="space-y-3">
                    <div :class="['relative overflow-hidden rounded-[1.8rem] border-[3px] aspect-square transition-all duration-300 bg-[#0d0d15]', cameraActive ? (faceInsideOval ? 'border-emerald-500/40 shadow-lg shadow-emerald-500/10' : 'border-amber-500/30') : 'border-white/[0.04]']">
                        <video v-show="cameraActive" ref="videoRef" autoplay muted playsinline :class="['w-full h-full object-cover', mirroredPreview ? '-scale-x-100' : '']"></video>
                        <canvas ref="canvasRef" class="hidden"></canvas>

                        <!-- Camera overlay -->
                        <div v-if="cameraActive" class="absolute inset-0 pointer-events-none">
                            <div class="absolute inset-0 bg-gradient-to-b from-black/25 via-transparent to-black/35"></div>
                            <!-- Face oval guide -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div :class="['w-[60%] h-[76%] rounded-[46%] border-[2.5px] transition-all duration-500', faceInsideOval ? 'border-emerald-400/70 shadow-[0_0_30px_rgba(52,211,153,0.15)]' : 'border-white/15']"></div>
                            </div>
                            <!-- Top instruction -->
                            <div class="absolute top-3 left-1/2 -translate-x-1/2 px-4 py-1.5 rounded-full bg-black/50 backdrop-blur-md text-[9px] font-bold uppercase tracking-wider text-white/80">
                                Alinea tu rostro en el óvalo
                            </div>
                            <!-- Challenge instruction -->
                            <div class="absolute bottom-20 left-1/2 -translate-x-1/2 px-4 py-1.5 rounded-full backdrop-blur-md text-[10px] font-bold uppercase tracking-wider" :class="challengeCompleted ? 'bg-emerald-500/20 text-emerald-300' : 'bg-black/50 text-blue-200'">
                                {{ currentChallengeLabel }}
                            </div>
                        </div>

                        <!-- Countdown -->
                        <transition name="countdown-fade">
                            <div v-if="countdown !== null" class="absolute inset-0 flex items-center justify-center bg-black/30 z-30">
                                <div class="relative w-28 h-28 flex items-center justify-center">
                                    <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 100 100">
                                        <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="3" />
                                        <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(59,130,246,0.8)" stroke-width="3" stroke-linecap="round" stroke-dasharray="283" :stroke-dashoffset="283 - (283 * (3 - countdown) / 3)" class="transition-all duration-1000 ease-linear" />
                                    </svg>
                                    <span class="text-5xl font-black text-white drop-shadow-lg">{{ countdown }}</span>
                                </div>
                            </div>
                        </transition>

                        <!-- Selfie preview -->
                        <img v-if="selfiePreview && !cameraActive" :src="selfiePreview" class="w-full h-full object-cover" />

                        <!-- Camera off placeholder -->
                        <div v-if="!cameraActive && !selfiePreview" class="w-full h-full flex flex-col items-center justify-center">
                            <div class="w-16 h-16 rounded-full bg-white/[0.03] border border-white/[0.06] flex items-center justify-center mb-4">
                                <svg class="w-7 h-7 text-neutral-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /></svg>
                            </div>
                            <button type="button" @click="openCamera" class="text-[10px] font-extrabold uppercase tracking-widest text-blue-400 hover:text-blue-300 transition-colors">Activar Cámara</button>
                        </div>

                        <!-- Manual shutter button -->
                        <div v-if="cameraActive" class="absolute bottom-5 inset-x-0 flex justify-center z-20">
                            <button type="button" @click="captureSelfie(true)" :disabled="faceCount === 0"
                                :class="['w-[4.5rem] h-[4.5rem] rounded-full border-[5px] transition-all duration-200', faceCount === 0 ? 'border-white/10 bg-white/10 opacity-40 cursor-not-allowed' : 'border-white/25 bg-white/40 hover:bg-white/60 active:scale-90 cursor-pointer shadow-lg shadow-black/40']">
                            </button>
                        </div>

                        <!-- Retake button -->
                        <button v-if="selfiePreview && !cameraActive" type="button" @click="openCamera" class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm p-2.5 rounded-xl text-white/80 hover:text-white hover:bg-black/70 transition-all border border-white/10">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        </button>
                    </div>

                    <!-- Camera message -->
                    <p v-if="cameraMessage" class="text-[10px] font-bold text-center" :class="form.selfie ? 'text-emerald-400' : 'text-amber-300'">
                        {{ cameraMessage }}
                    </p>
                </div>

                <!-- Notes (expandable) -->
                <div class="space-y-2">
                    <button type="button" @click="showNotes = !showNotes" class="flex items-center gap-2 text-[9px] font-extrabold uppercase tracking-widest text-neutral-600 hover:text-neutral-400 transition-colors">
                        <span>{{ showNotes ? '▾' : '▸' }}</span> Agregar nota (opcional)
                    </button>
                    <transition name="slide-down">
                        <div v-if="showNotes" class="overflow-hidden">
                            <textarea v-model="form.notas" rows="2" maxlength="500" placeholder="Ej: Llegué tarde por tráfico en la carretera..." class="w-full bg-black/30 border border-white/[0.06] rounded-xl text-[11px] text-white placeholder-neutral-700 focus:ring-blue-500/30 focus:border-blue-500/30 resize-none p-3"></textarea>
                            <div class="text-[8px] text-neutral-700 text-right mt-1 font-medium">{{ (form.notas || '').length }}/500</div>
                        </div>
                    </transition>
                </div>

                <!-- Consent -->
                <div class="pt-4 border-t border-white/[0.04]">
                    <div class="flex items-start gap-3">
                        <input id="consent" type="checkbox" v-model="form.consentimiento" checked disabled class="mt-1 w-4 h-4 bg-black/50 border-white/15 rounded text-blue-600 focus:ring-0 opacity-70 cursor-not-allowed">
                        <label for="consent" class="text-[10px] leading-relaxed text-neutral-500">
                            Certifico que este registro es real y consiento el uso de mi ubicación y fotografía para fines laborales.
                        </label>
                    </div>
                </div>

                <!-- Auto Submit Info -->
                <div class="space-y-3">
                    <div class="text-center text-[9px] font-extrabold uppercase tracking-[0.2em] text-neutral-600">
                        Registro automático al capturar foto
                    </div>
                    <div v-if="$page.props.flash?.success" class="text-emerald-400 text-[10px] font-black text-center uppercase tracking-widest">
                        {{ $page.props.flash.success }}
                    </div>
                    <div v-if="form.errors && Object.keys(form.errors).length" class="rounded-xl p-3 bg-rose-500/[0.06] border border-rose-500/20">
                        <div v-for="(err, key) in form.errors" :key="key" class="text-[10px] text-rose-400 font-medium">{{ err }}</div>
                    </div>
                </div>
            </form>

            <!-- Footer -->
            <div class="text-center pb-8">
                <div class="text-[9px] font-extrabold uppercase tracking-[0.3em] text-neutral-800">Asistencia Vircom</div>
            </div>
        </div>

        <!-- Confetti Canvas -->
        <canvas v-if="showConfetti" ref="confettiCanvasRef" class="fixed inset-0 z-[60] pointer-events-none"></canvas>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap');

.success-pop-enter-active,
.success-pop-leave-active {
    transition: all .3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.success-pop-enter-from,
.success-pop-leave-to {
    opacity: 0;
    transform: translate(-50%, -12px) scale(.92);
}

.countdown-fade-enter-active,
.countdown-fade-leave-active {
    transition: opacity .3s ease;
}
.countdown-fade-enter-from,
.countdown-fade-leave-to {
    opacity: 0;
}

.slide-down-enter-active,
.slide-down-leave-active {
    transition: all .25s ease;
    max-height: 200px;
}
.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-4px);
}
</style>
