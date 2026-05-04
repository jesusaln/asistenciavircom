import { onMounted, onUnmounted } from 'vue';

export function useClickOutside(targets, onOutside) {
  const resolveTarget = (target) => {
    if (!target) return null;
    if (typeof target === 'function') return target();
    if (target.value) {
      return target.value?.panelEl?.value
        || target.value?.emptyEl?.value
        || target.value?.$el
        || target.value;
    }
    return target?.panelEl?.value || target?.emptyEl?.value || target?.$el || target;
  };

  const handler = (event) => {
    const clickedInside = targets.some((target) => {
      const el = resolveTarget(target);
      return el && el.contains && el.contains(event.target);
    });
    if (!clickedInside) {
      onOutside(event);
    }
  };

  onMounted(() => {
    document.addEventListener('click', handler);
  });

  onUnmounted(() => {
    document.removeEventListener('click', handler);
  });
}
