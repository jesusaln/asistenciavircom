
export function usePosSounds() {
    // Contexto de audio (Lazy initialization para evitar errores en Chrome)
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    let ctx = null;

    const getAudioContext = () => {
        if (!ctx) {
            ctx = new AudioContext();
        }
        if (ctx.state === 'suspended') {
            ctx.resume();
        }
        return ctx;
    };

    // Helper para crear oscilador
    const playTone = (freq, type, duration, vol = 0.1) => {
        const audioCtx = getAudioContext();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc.type = type;
        osc.frequency.setValueAtTime(freq, audioCtx.currentTime);

        gain.gain.setValueAtTime(vol, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);

        osc.connect(gain);
        gain.connect(audioCtx.destination);

        osc.start();
        osc.stop(audioCtx.currentTime + duration);
    };

    const playBeep = () => {
        // Sonido corto y agudo para escaneo exitoso
        playTone(1200, 'sine', 0.1, 0.1);
    };

    const playError = () => {
        // Sonido grave y "áspero" para error
        const audioCtx = getAudioContext();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(150, audioCtx.currentTime);
        osc.frequency.linearRampToValueAtTime(100, audioCtx.currentTime + 0.3);

        gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
        gain.gain.linearRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);

        osc.connect(gain);
        gain.connect(audioCtx.destination);

        osc.start();
        osc.stop(audioCtx.currentTime + 0.3);
    };

    const playSuccess = () => {
        // Arpegio ascendente para venta completada
        [523.25, 659.25, 783.99, 1046.50].forEach((freq, i) => {
            setTimeout(() => playTone(freq, 'sine', 0.2, 0.1), i * 100);
        });
    };

    const playDelete = () => {
        // Tono descendente rápido
        const audioCtx = getAudioContext();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc.type = 'triangle';
        osc.frequency.setValueAtTime(400, audioCtx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(100, audioCtx.currentTime + 0.2);

        gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gain.gain.linearRampToValueAtTime(0.001, audioCtx.currentTime + 0.2);

        osc.connect(gain);
        gain.connect(audioCtx.destination);

        osc.start();
        osc.stop(audioCtx.currentTime + 0.2);
    };

    return {
        playBeep,
        playError,
        playSuccess,
        playDelete
    };
}
