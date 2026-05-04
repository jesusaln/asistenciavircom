import { ref, onMounted, onUnmounted } from 'vue';

export function useDropdownPosition(inputRef) {
  const inputWidth = ref(0);
  const inputPosition = ref({ top: 0, left: 0, height: 0 });

  const updatePosition = () => {
    if (!inputRef.value) return;
    const rect = inputRef.value.getBoundingClientRect();
    inputWidth.value = rect.width;
    inputPosition.value = {
      top: rect.top + window.scrollY,
      left: rect.left + window.scrollX,
      height: rect.height,
    };
  };

  onMounted(() => {
    updatePosition();
    window.addEventListener('resize', updatePosition);
    window.addEventListener('scroll', updatePosition, true);
  });

  onUnmounted(() => {
    window.removeEventListener('resize', updatePosition);
    window.removeEventListener('scroll', updatePosition, true);
  });

  return { inputWidth, inputPosition, updatePosition };
}
