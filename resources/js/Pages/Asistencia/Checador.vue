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
    brightness: 0,
    sharpness: 0,
    faceAreaRatio: 0,
    centerOffset: 1,
    sizePass: false,
    centerPass: false,
    singleFace: false,
    passed: false,
    message: 'Activa cámara para validar calidad',
});
let faceDetectionTimer = null;
let countdownInterval = null;
let isDetectingFrame = false;
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
const challengeProgressPct = computed(() => {
    if (!challengeSequence.value.length) return 0;
    return Math.round((challengeStepIndex.value / challengeSequence.value.length) * 100);
});
const challengeStatusClass = computed(() => {
    if (challengeCompleted.value) return 'text-emerald-300';
    if (challengeStarted.value) return 'text-blue-200';
    return 'text-amber-300';
});
const qualityStatusLabel = computed(() => captureQuality.value.passed ? 'Calidad OK' : 'Calidad pendiente');
const qualityStatusClass = computed(() => captureQuality.value.passed ? 'text-emerald-400' : 'text-amber-300');
const faceInsideOval = computed(() => cameraActive.value && captureQuality.value.singleFace && captureQuality.value.sizePass && captureQuality.value.centerPass);
const readyForAutoCapture = computed(() => faceInsideOval.value && captureQuality.value.passed && form.face_challenge_completed && eyesOpen.value);
const successPulse = ref(false);
let successPulseTimer = null;
const overlayClass = computed(() => {
    if (!cameraActive.value) return 'border-white/20';
    return faceInsideOval.value ? 'border-emerald-400/80' : 'border-amber-400/70';
});

const form = useForm({
    tipo: props.suggestedType || 'entry',
    token: props.token || '',
    latitud: '',
    longitud: '',
    precision_metros: '',
    selfie: null,
    consentimiento: true,
    face_challenge_completed: false,
    face_liveness_score: '',
    face_descriptor: '',
    face_detected_count: 0,
    face_capture_quality_passed: false,
    face_quality_brightness: '',
    face_quality_sharpness: '',
    face_quality_area_ratio: '',
    face_quality_center_offset: '',
    face_quality_message: '',
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
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    countdown.value = null;
    if (mediaStream) {
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    }
    cameraActive.value = false;
};

const clearSelfiePreview = () => {
    if (selfiePreview.value) {
        URL.revokeObjectURL(selfiePreview.value);
        selfiePreview.value = '';
    }
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

const enableFrequentReadCanvas = () => {
    if (typeof HTMLCanvasElement === 'undefined') return;
    if (originalGetContext) return;

    originalGetContext = HTMLCanvasElement.prototype.getContext;
    HTMLCanvasElement.prototype.getContext = function patchedGetContext(type, options) {
        if (type !== '2d') {
            return originalGetContext.call(this, type, options);
        }

        if (options && typeof options === 'object' && Object.prototype.hasOwnProperty.call(options, 'willReadFrequently')) {
            return originalGetContext.call(this, type, options);
        }

        const nextOptions = options && typeof options === 'object'
            ? { ...options, willReadFrequently: true }
            : { willReadFrequently: true };

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
    faceCount.value = 0;
    form.face_challenge_completed = false;
    form.face_liveness_score = '';
    form.face_descriptor = '';
    form.face_detected_count = 0;
    form.face_capture_quality_passed = false;
    form.face_quality_brightness = '';
    form.face_quality_sharpness = '';
    form.face_quality_area_ratio = '';
    form.face_quality_center_offset = '';
    form.face_quality_message = '';
    captureQuality.value = {
        brightness: 0,
        sharpness: 0,
        faceAreaRatio: 0,
        centerOffset: 1,
        sizePass: false,
        centerPass: false,
        singleFace: false,
        passed: false,
        message: 'Esperando rostro...',
    };
};

const avgPoint = (points) => {
    const sum = points.reduce((acc, p) => ({ x: acc.x + p.x, y: acc.y + p.y }), { x: 0, y: 0 });
    return { x: sum.x / points.length, y: sum.y / points.length };
};

const pointDistance = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);

const eyeAspectRatio = (eyePoints = []) => {
    if (eyePoints.length < 6) return 0;
    const horizontal = Math.max(0.0001, pointDistance(eyePoints[0], eyePoints[3]));
    const vertical = pointDistance(eyePoints[1], eyePoints[5]) + pointDistance(eyePoints[2], eyePoints[4]);
    return vertical / (2 * horizontal);
};

const detectEyesOpen = (landmarks) => {
    if (!landmarks) return false;
    const leftEAR = eyeAspectRatio(landmarks.getLeftEye());
    const rightEAR = eyeAspectRatio(landmarks.getRightEye());
    const avgEAR = (leftEAR + rightEAR) / 2;
    // Más relajado aún: 0.14 para ojos
    return avgEAR >= 0.14;
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
    if (current === 'left') passed = mirroredPreview ? relYaw > 0.10 : relYaw < -0.10;
    if (current === 'right') passed = mirroredPreview ? relYaw < -0.10 : relYaw > 0.10;
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

const frameStats = (source, box) => {
    if (!qualityCanvas) return null;
    const w = source.videoWidth || source.width || 0;
    const h = source.videoHeight || source.height || 0;
    if (!w || !h) return null;

    qualityCanvas.width = 160;
    qualityCanvas.height = 120;
    const qCtx = qualityCanvas.getContext('2d', { willReadFrequently: true });
    qCtx.drawImage(source, 0, 0, qualityCanvas.width, qualityCanvas.height);
    const img = qCtx.getImageData(0, 0, qualityCanvas.width, qualityCanvas.height).data;

    let brightnessSum = 0;
    let gradientSum = 0;
    let samples = 0;
    const stride = 4;
    const rowPixels = qualityCanvas.width;

    for (let y = 1; y < qualityCanvas.height - 1; y++) {
        for (let x = 1; x < qualityCanvas.width - 1; x++) {
            const i = (y * rowPixels + x) * stride;
            const lum = 0.299 * img[i] + 0.587 * img[i + 1] + 0.114 * img[i + 2];
            const right = i + stride;
            const down = i + rowPixels * stride;
            const lumRight = 0.299 * img[right] + 0.587 * img[right + 1] + 0.114 * img[right + 2];
            const lumDown = 0.299 * img[down] + 0.587 * img[down + 1] + 0.114 * img[down + 2];
            brightnessSum += lum;
            gradientSum += Math.abs(lum - lumRight) + Math.abs(lum - lumDown);
            samples++;
        }
    }

    const brightness = samples ? (brightnessSum / samples) / 255 : 0;
    const sharpness = samples ? (gradientSum / samples) / 255 : 0;
    const area = Math.max(1, w * h);
    const faceAreaRatio = (box.width * box.height) / area;
    const faceCx = box.x + (box.width / 2);
    const faceCy = box.y + (box.height / 2);
    const frameCx = w / 2;
    const frameCy = h / 2;
    const centerOffset = Math.hypot(faceCx - frameCx, faceCy - frameCy) / Math.hypot(frameCx, frameCy);

    return { brightness, sharpness, faceAreaRatio, centerOffset };
};

const evaluateQuality = (source, box, facesLength) => {
    faceCount.value = facesLength;
    form.face_detected_count = facesLength;

    if (facesLength === 0) {
        form.face_capture_quality_passed = false;
        form.face_quality_message = 'No se detecta rostro';
        captureQuality.value = { ...captureQuality.value, sizePass: false, centerPass: false, singleFace: false, passed: false, message: 'No se detecta rostro' };
        return false;
    }

    if (facesLength > 1) {
        form.face_capture_quality_passed = false;
        form.face_quality_message = 'Solo debe aparecer 1 rostro';
        captureQuality.value = { ...captureQuality.value, sizePass: false, centerPass: false, singleFace: false, passed: false, message: 'Solo debe aparecer 1 rostro' };
        return false;
    }

    const stats = frameStats(source, box);
    if (!stats) {
        captureQuality.value = { ...captureQuality.value, sizePass: false, centerPass: false, singleFace: false, passed: false, message: 'No se pudo evaluar calidad' };
        return false;
    }

    // Relajación extrema de umbrales para entorno real
    const lightingPass = stats.brightness >= 0.10 && stats.brightness <= 0.95;
    const sharpnessPass = stats.sharpness >= 0.02;
    const sizePass = stats.faceAreaRatio >= 0.05 && stats.faceAreaRatio <= 0.80;
    const centerPass = stats.centerOffset <= 0.45;
    const passed = lightingPass && sharpnessPass && sizePass && centerPass;

    let message = 'Calidad correcta';
    if (!lightingPass) message = 'Ajusta iluminación';
    else if (!sharpnessPass) message = 'Evita movimiento, enfoca mejor';
    else if (!sizePass) message = 'Ajusta distancia para encuadrar rostro';
    else if (!centerPass) message = 'Centra tu rostro en pantalla';

    captureQuality.value = {
        ...stats,
        sizePass,
        centerPass,
        singleFace: true,
        passed,
        message,
    };
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
            const detections = await window.faceapi
                .detectAllFaces(videoRef.value, new window.faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }))
                .withFaceLandmarks()
                .withFaceDescriptors();

            if (!detections?.length) {
                eyesOpen.value = false;
                evaluateQuality(videoRef.value, { x: 0, y: 0, width: 0, height: 0 }, 0);
                return;
            }

            const primary = detections.reduce((best, current) => {
                const bestArea = best.detection.box.width * best.detection.box.height;
                const currentArea = current.detection.box.width * current.detection.box.height;
                return currentArea > bestArea ? current : best;
            }, detections[0]);

            const qualityPass = evaluateQuality(videoRef.value, primary.detection.box, detections.length);
            eyesOpen.value = detectEyesOpen(primary.landmarks);
            processChallenge(primary.landmarks);
            if (!qualityPass) {
                form.face_descriptor = '';
                return;
            }
            if (!eyesOpen.value) {
                form.face_descriptor = '';
                captureQuality.value = { ...captureQuality.value, passed: false, message: 'Mantén los ojos abiertos' };
                form.face_capture_quality_passed = false;
                form.face_quality_message = 'Mantén los ojos abiertos';
                return;
            }

            const descriptor = Array.from(primary.descriptor);
            liveDescriptor.value = descriptor;
            form.face_descriptor = JSON.stringify(descriptor);

            if (readyForAutoCapture.value && !autoCaptureRunning.value && !form.selfie && !countdown.value) {
                cameraMessage.value = 'Rostro detectado...';
                
                // Countdown de 3 segundos
                countdown.value = 3;
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                }
                countdownInterval = setInterval(async () => {
                    countdown.value--;
                    if (countdown.value <= 0) {
                        clearInterval(countdownInterval);
                        countdownInterval = null;
                        countdown.value = null;
                        
                        if (readyForAutoCapture.value && !form.selfie) {
                            autoCaptureRunning.value = true;
                            cameraMessage.value = 'Capturando...';
                            try {
                                await captureSelfie();
                            } finally {
                                autoCaptureRunning.value = false;
                            }
                        }
                    }
                }, 1000);
            }
        } catch (e) {
            // Ignore intermittent frame errors
        } finally {
            isDetectingFrame = false;
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
        cameraMessage.value = '';
        cameraActive.value = true;
        startFaceDetection();
    } catch (error) {
        cameraMessage.value = 'Sin permiso de cámara.';
    }
};

const openCameraAutomatically = async () => {
    if (!cameraSupported.value) return;
    if (cameraActive.value) return;
    if (form.selfie) return;
    await openCamera();
};

const captureLocationAsync = () => new Promise((resolve) => {
    if (!navigator.geolocation) {
        geoMessage.value = 'GPS no soportado.';
        resolve(false);
        return;
    }

    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitud = String(position.coords.latitude);
            form.longitud = String(position.coords.longitude);
            form.precision_metros = String(Math.round(position.coords.accuracy || 0));
            gettingLocation.value = false;
            resolve(true);
        },
        () => {
            geoMessage.value = 'Error de GPS. Activa la ubicación.';
            gettingLocation.value = false;
            resolve(false);
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
});

const captureSelfie = async (manual = false) => {
    cameraMessage.value = '';
    if (!videoRef.value || !canvasRef.value) return;

    if (!manual) {
        if (!form.face_challenge_completed) {
            cameraMessage.value = 'Completa el reto de movimientos antes de tomar la foto.';
            return;
        }
        if (!captureQuality.value.passed) {
            cameraMessage.value = captureQuality.value.message || 'Mejora la calidad antes de capturar.';
            return;
        }
        if (!eyesOpen.value) {
            cameraMessage.value = 'Mantén los ojos abiertos para tomar la foto.';
            return;
        }
    } else {
        if (form.face_detected_count === 0) {
            cameraMessage.value = 'Debe haber al menos un rostro visible.';
            return;
        }
        if (!form.face_challenge_completed) {
            const fallbackLiveness = Math.max(Number(form.face_liveness_score || 0), 0.55);
            form.face_liveness_score = fallbackLiveness.toFixed(2);
            form.face_quality_message = 'Captura manual sin reto completo.';
            cameraMessage.value = 'Manual (Se recomienda completar reto)';
        }
    }

    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    if (window.faceapi) {
        const detections = await window.faceapi
            .detectAllFaces(canvas, new window.faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }))
            .withFaceLandmarks()
            .withFaceDescriptors();

        if (!detections?.length) {
            cameraMessage.value = 'No se detectó rostro válido en la selfie. Intenta de nuevo.';
            return;
        }

        const primary = detections.reduce((best, current) => {
            const bestArea = best.detection.box.width * best.detection.box.height;
            const currentArea = current.detection.box.width * current.detection.box.height;
            return currentArea > bestArea ? current : best;
        }, detections[0]);

        // Validaciones reducidas para permitir capturas con la librería
        if (!manual) {
            const qualityPass = evaluateQuality(canvas, primary.detection.box, detections.length);
            if (!qualityPass) {
                cameraMessage.value = captureQuality.value.message || 'Calidad insuficiente para selfie.';
                return;
            }
            if (!detectEyesOpen(primary.landmarks)) {
                cameraMessage.value = 'No cierres los ojos al capturar.';
                return;
            }
        } else {
            // Evaluamos para guardar valores en capture stat, pero no bloquea
            evaluateQuality(canvas, primary.detection.box, detections.length);
        }

        const descriptor = Array.from(primary.descriptor);
        liveDescriptor.value = descriptor;
        form.face_descriptor = JSON.stringify(descriptor);
    }

    canvas.toBlob(async (blob) => {
        if (!blob) return;
        const file = new File([blob], `checkin-${Date.now()}.jpg`, { type: 'image/jpeg' });
        form.selfie = file;
        clearSelfiePreview();
        selfiePreview.value = URL.createObjectURL(file);
        if (!form.face_challenge_completed && manual) {
            cameraMessage.value = 'Foto manual capturada correctamente.';
        } else {
            cameraMessage.value = 'Foto capturada correctamente.';
        }
        stopCamera();

        if (!form.latitud || !form.longitud) {
            cameraMessage.value = 'Capturando ubicación para enviar...';
            const gotLocation = await captureLocationAsync();
            if (!gotLocation) {
                cameraMessage.value = 'No se pudo obtener GPS. Activa ubicación e intenta de nuevo.';
                return;
            }
        }

        cameraMessage.value = 'Enviando registro...';
        submit();
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
    if (form.processing) return;
    form.consentimiento = true;
    form.post(route('asistencia.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            successPulse.value = true;
            if (successPulseTimer) {
                clearTimeout(successPulseTimer);
            }
            successPulseTimer = setTimeout(() => {
                successPulse.value = false;
            }, 2200);

            const lastType = form.tipo;
            form.reset(
                'selfie',
                'consentimiento',
                'notas',
                'face_challenge_completed',
                'face_liveness_score',
                'face_descriptor',
                'face_detected_count',
                'face_capture_quality_passed',
                'face_quality_brightness',
                'face_quality_sharpness',
                'face_quality_area_ratio',
                'face_quality_center_offset',
                'face_quality_message'
            );
            clearSelfiePreview();
            form.tipo = nextType(lastType);
            buildChallenge();
            captureLocation();
            openCameraAutomatically();
            cameraMessage.value = '';
        },
        onError: () => {
            cameraMessage.value = 'No se pudo registrar. Revisa GPS/rostro e intenta de nuevo.';
        }
    });
};

onMounted(() => {
    enableFrequentReadCanvas();
    if (props.serverNowIso) {
        clockStartServerMs = Date.parse(props.serverNowIso);
        clockStartClientMs = Date.now();
    }
    refreshClock();
    clockTimer = setInterval(refreshClock, 1000);
    cameraSupported.value = !!navigator.mediaDevices?.getUserMedia;
    buildChallenge();
    captureLocation();
    openCameraAutomatically();
});

onUnmounted(() => {
    if (clockTimer) clearInterval(clockTimer);
    if (successPulseTimer) clearTimeout(successPulseTimer);
    stopCamera();
    clearSelfiePreview();
    restoreCanvasGetContext();
});
</script>

<template>
    <Head title="Checador de Asistencia" />

    <div class="min-h-screen bg-neutral-950 text-white flex flex-col items-center p-4">
        <transition name="success-pop">
            <div
                v-if="successPulse"
                class="fixed top-6 left-1/2 -translate-x-1/2 z-50 bg-emerald-500 text-black px-5 py-3 rounded-2xl shadow-2xl shadow-emerald-500/40 flex items-center gap-2 text-[11px] font-black uppercase tracking-widest"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                Registro confirmado
            </div>
        </transition>

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
                    <p class="text-[11px] mt-2" :class="challengeStatusClass">
                        Reto: {{ currentChallengeLabel }}.
                    </p>
                    <p class="text-[11px] text-blue-100/80 mt-1">
                        Progreso: {{ challengeStepIndex }}/{{ challengeSequence.length }}.
                    </p>
                    <div class="mt-2 h-1.5 rounded-full bg-black/40 overflow-hidden">
                        <div class="h-full bg-emerald-400 transition-all duration-300" :style="{ width: `${challengeProgressPct}%` }"></div>
                    </div>
                    <p class="text-[11px] mt-1" :class="qualityStatusClass">
                        {{ qualityStatusLabel }}: {{ captureQuality.message }} (rostros: {{ faceCount }})
                    </p>
                    <p class="text-[11px] mt-1" :class="eyesOpen ? 'text-emerald-300' : 'text-amber-300'">
                        Ojos: {{ eyesOpen ? 'Abiertos' : 'Parpadeo o cerrados' }}
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
                    <div :class="['relative overflow-hidden rounded-[2rem] border-4 aspect-square transition-all bg-neutral-900', cameraActive ? (faceInsideOval ? 'border-emerald-500/50' : 'border-amber-500/50') : 'border-white/5']">
                        <video v-show="cameraActive" ref="videoRef" autoplay muted playsinline :class="['w-full h-full object-cover', mirroredPreview ? '-scale-x-100' : '']"></video>
                        <canvas ref="canvasRef" class="hidden"></canvas>

                        <div v-if="cameraActive" class="absolute inset-0 pointer-events-none">
                            <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-transparent to-black/30"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div
                                    :class="[
                                        'w-[62%] h-[78%] rounded-[46%] border-[3px] transition-colors duration-300',
                                        overlayClass
                                    ]"
                                ></div>
                            </div>
                            <div class="absolute top-4 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-black/45 backdrop-blur-sm text-[10px] font-black uppercase tracking-widest text-white/90">
                                Alinea tu rostro dentro del ovalo
                            </div>
                            <div class="absolute bottom-24 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-black/55 backdrop-blur-sm text-[10px] font-black uppercase tracking-widest text-emerald-200">
                                {{ currentChallengeLabel }}
                            </div>
                        </div>

                        <!-- Countdown Overlay -->
                        <div v-if="countdown !== null" class="absolute inset-0 flex items-center justify-center bg-black/20 z-30">
                            <div class="text-9xl font-black text-white drop-shadow-[0_0_20px_rgba(0,0,0,0.8)] animate-ping">
                                {{ countdown }}
                            </div>
                        </div>
                        
                        <img v-if="selfiePreview && !cameraActive" :src="selfiePreview" class="w-full h-full object-cover" />

                        <div v-if="!cameraActive && !selfiePreview" class="w-full h-full flex flex-col items-center justify-center text-neutral-600">
                            <svg class="w-12 h-12 mb-3 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /></svg>
                            <button type="button" @click="openCamera" class="text-[10px] font-black uppercase text-blue-400">Activar Cámara</button>
                        </div>

                        <div v-if="cameraActive" class="absolute bottom-6 inset-x-0 flex justify-center z-20">
                            <button
                                type="button"
                                @click="captureSelfie(true)"
                                :disabled="faceCount === 0"
                                class="w-16 h-16 rounded-full border-8 border-white/20 transition-transform bg-white/50 active:scale-90"
                                :class="faceCount === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80 cursor-pointer'"
                            ></button>
                        </div>

                        <button v-if="selfiePreview && !cameraActive" type="button" @click="openCamera" class="absolute top-4 right-4 bg-black/50 p-2 rounded-full text-white">
                             <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        </button>
                    </div>

                    <p v-if="cameraMessage" class="text-[10px] font-bold" :class="form.selfie ? 'text-emerald-300' : 'text-amber-300'">
                        {{ cameraMessage }}
                    </p>
                </div>

                <!-- Terms -->
                <div class="space-y-4 pt-4 border-t border-white/10">
                    <div class="flex items-start gap-3">
                        <input id="consent" type="checkbox" v-model="form.consentimiento" checked disabled class="mt-1 w-5 h-5 bg-black border-white/20 rounded text-blue-600 focus:ring-0 opacity-80 cursor-not-allowed">
                        <label for="consent" class="text-[11px] leading-relaxed text-neutral-400">
                            Certifico que este registro es real y consiento el uso de mi ubicación y fotografía para fines laborales.
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="space-y-4">
                    <div class="text-center text-[10px] font-black uppercase tracking-[0.2em] text-blue-300/70">
                        Registro automático al capturar foto
                    </div>
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

.success-pop-enter-active,
.success-pop-leave-active {
    transition: all .25s ease;
}

.success-pop-enter-from,
.success-pop-leave-to {
    opacity: 0;
    transform: translate(-50%, -8px) scale(.96);
}
</style>
