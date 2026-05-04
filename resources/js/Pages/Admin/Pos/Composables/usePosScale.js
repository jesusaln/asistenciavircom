import { ref } from 'vue';
import axios from 'axios';

export const usePosScale = (notify) => {
    const scaleWeight = ref(0);
    const scaleActive = ref(false);

    const tryWeight = async () => {
        scaleActive.value = true;
        try {
            const response = await axios.get('http://localhost:3001/peso', { timeout: 1000 });
            if (response.data && response.data.success) {
                scaleWeight.value = parseFloat(response.data.peso);
                notify.success(`Peso capturado: ${scaleWeight.value} kg`);
                return scaleWeight.value;
            }
        } catch (error) {
            // Silenciar error de bascula
        } finally {
            scaleActive.value = false;
        }
        return null;
    };

    const isWeighable = (item) => {
        const weighableUnits = ['kg', 'kilo', 'kilogramo', 'g', 'gramo', 'lb', 'libra'];
        const unit = (item.unidad_medida || '').toLowerCase();
        return weighableUnits.some(u => unit.includes(u));
    };

    return {
        scaleWeight,
        scaleActive,
        tryWeight,
        isWeighable,
    };
};
