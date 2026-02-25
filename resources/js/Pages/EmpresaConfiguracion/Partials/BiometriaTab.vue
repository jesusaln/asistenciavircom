<template>
  <div class="space-y-8">
    <div>
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-gray-100 mb-2 flex items-center gap-2">
        <FontAwesomeIcon icon="id-card" class="text-blue-600 dark:text-blue-400" />
        Reglas de Asistencia y Biometría
      </h2>
      <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
        Ajusta políticas de retardo, sensibilidad facial y tolerancia por ubicación. Recomendado: empezar flexible y endurecer gradualmente.
      </p>
    </div>

    <!-- Reglas de Asistencia -->
    <div class="bg-indigo-50 dark:bg-indigo-900/20 p-6 rounded-xl border border-indigo-200 dark:border-indigo-700">
      <h3 class="text-md font-medium text-indigo-900 dark:text-indigo-300 mb-4 flex items-center gap-2">
        <FontAwesomeIcon icon="clock" /> Reglas Locales de Asistencia
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tolerancia para Retardos (minutos)</label>
          <input type="number" min="0" max="120" v-model.number="form.minutos_tolerancia_retardo" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200" placeholder="Ej: 15">
          <p class="text-[10px] text-gray-500 mt-1">Tiempo de gracia antes de marcar la entrada como retardo.</p>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-700">
      <div class="mb-5">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Presets rápidos</label>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            @click="applyPreset('flexible')"
            :class="[
              'px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider border transition-colors',
              activePreset === 'flexible'
                ? 'border-emerald-500 text-white bg-emerald-600 dark:bg-emerald-600'
                : 'border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-700 dark:text-emerald-300'
            ]"
          >
            Flexible
          </button>
          <button
            type="button"
            @click="applyPreset('balanced')"
            :class="[
              'px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider border transition-colors',
              activePreset === 'balanced'
                ? 'border-blue-500 text-white bg-blue-600 dark:bg-blue-600'
                : 'border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:border-blue-700 dark:text-blue-300'
            ]"
          >
            Balanceado
          </button>
          <button
            type="button"
            @click="applyPreset('strict')"
            :class="[
              'px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider border transition-colors',
              activePreset === 'strict'
                ? 'border-rose-500 text-white bg-rose-600 dark:bg-rose-600'
                : 'border-rose-300 text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:border-rose-700 dark:text-rose-300'
            ]"
          >
            Estricto
          </button>
          <button
            type="button"
            @click="restoreRecommended()"
            class="px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-gray-200 dark:hover:bg-slate-700 transition-colors"
          >
            Restaurar recomendados
          </button>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
          Selecciona un preset y luego guarda configuración.
          <span class="font-semibold text-gray-700 dark:text-gray-300">
            Preset activo: {{ activePresetLabel }}
          </span>
        </p>
      </div>

      <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" v-model="form.biometrics_strict_match" class="sr-only peer">
        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-slate-900 after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">Modo estricto (bloquear si no verifica rostro)</span>
      </label>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 ml-14">
        Si está desactivado, el registro se guarda con incidencia para revisión manual.
      </p>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-xl border border-blue-200 dark:border-blue-700">
      <h3 class="text-md font-medium text-blue-900 dark:text-blue-300 mb-4">Umbrales base</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Match facial base (0.50 - 0.95)</label>
          <input type="number" step="0.01" min="0.5" max="0.95" v-model.number="form.biometrics_local_match_threshold" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Liveness base (0.30 - 0.95)</label>
          <input type="number" step="0.01" min="0.3" max="0.95" v-model.number="form.biometrics_local_liveness_threshold" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200">
        </div>
      </div>
    </div>

    <div class="bg-amber-50 dark:bg-amber-900/20 p-6 rounded-xl border border-amber-200 dark:border-amber-700">
      <h3 class="text-md font-medium text-amber-900 dark:text-amber-300 mb-4">Ajustes por geolocalización</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Margen suave geocerca (m)</label>
          <input type="number" min="0" max="5000" v-model.number="form.biometrics_geofence_soft_margin_meters" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200">
        </div>
        <div></div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Relajar match si está cerca</label>
          <input type="number" step="0.01" min="0" max="0.3" v-model.number="form.biometrics_nearby_match_relax" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Relajar liveness si está cerca</label>
          <input type="number" step="0.01" min="0" max="0.3" v-model.number="form.biometrics_nearby_liveness_relax" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Penalizar match si está lejos</label>
          <input type="number" step="0.01" min="0" max="0.3" v-model.number="form.biometrics_far_match_penalty" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Penalizar liveness si está lejos</label>
          <input type="number" step="0.01" min="0" max="0.3" v-model.number="form.biometrics_far_liveness_penalty" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200">
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
  form: { type: Object, required: true },
});

const presets = {
  flexible: {
    biometrics_strict_match: false,
    biometrics_local_match_threshold: 0.72,
    biometrics_local_liveness_threshold: 0.45,
    biometrics_geofence_soft_margin_meters: 120,
    biometrics_nearby_match_relax: 0.06,
    biometrics_nearby_liveness_relax: 0.10,
    biometrics_far_match_penalty: 0.06,
    biometrics_far_liveness_penalty: 0.10,
  },
  balanced: {
    biometrics_strict_match: false,
    biometrics_local_match_threshold: 0.76,
    biometrics_local_liveness_threshold: 0.52,
    biometrics_geofence_soft_margin_meters: 90,
    biometrics_nearby_match_relax: 0.04,
    biometrics_nearby_liveness_relax: 0.07,
    biometrics_far_match_penalty: 0.08,
    biometrics_far_liveness_penalty: 0.12,
  },
  strict: {
    biometrics_strict_match: true,
    biometrics_local_match_threshold: 0.82,
    biometrics_local_liveness_threshold: 0.60,
    biometrics_geofence_soft_margin_meters: 60,
    biometrics_nearby_match_relax: 0.02,
    biometrics_nearby_liveness_relax: 0.03,
    biometrics_far_match_penalty: 0.12,
    biometrics_far_liveness_penalty: 0.15,
  },
};

const applyPreset = (presetKey) => {
  const preset = presets[presetKey];
  if (!preset) return;
  Object.assign(props.form, preset);
};

const restoreRecommended = () => {
  applyPreset('balanced');
};

const isClose = (a, b, epsilon = 0.0001) => Math.abs(Number(a) - Number(b)) <= epsilon;

const matchesPreset = (presetKey) => {
  const preset = presets[presetKey];
  if (!preset) return false;

  return (
    Boolean(props.form.biometrics_strict_match) === Boolean(preset.biometrics_strict_match) &&
    isClose(props.form.biometrics_local_match_threshold, preset.biometrics_local_match_threshold) &&
    isClose(props.form.biometrics_local_liveness_threshold, preset.biometrics_local_liveness_threshold) &&
    Number(props.form.biometrics_geofence_soft_margin_meters) === Number(preset.biometrics_geofence_soft_margin_meters) &&
    isClose(props.form.biometrics_nearby_match_relax, preset.biometrics_nearby_match_relax) &&
    isClose(props.form.biometrics_nearby_liveness_relax, preset.biometrics_nearby_liveness_relax) &&
    isClose(props.form.biometrics_far_match_penalty, preset.biometrics_far_match_penalty) &&
    isClose(props.form.biometrics_far_liveness_penalty, preset.biometrics_far_liveness_penalty)
  );
};

const activePreset = computed(() => {
  if (matchesPreset('flexible')) return 'flexible';
  if (matchesPreset('balanced')) return 'balanced';
  if (matchesPreset('strict')) return 'strict';
  return 'custom';
});

const activePresetLabel = computed(() => {
  if (activePreset.value === 'flexible') return 'Flexible';
  if (activePreset.value === 'balanced') return 'Balanceado';
  if (activePreset.value === 'strict') return 'Estricto';
  return 'Personalizado';
});
</script>
