<template>
  <Teleport to="#app">
    <div
      v-if="show && hasItems"
      ref="panelEl"
      class="z-[100] mt-1 bg-[var(--ui-surface)] text-[var(--ui-text)] border border-[var(--ui-border)] rounded-2xl shadow-lg overflow-y-auto custom-scrollbar"
      :style="{
        position: 'absolute',
        width: width + 'px',
        top: position.top + position.height + 'px',
        left: position.left + 'px',
        maxHeight
      }"
    >
      <div v-if="$slots.header" class="sticky top-0 bg-[var(--ui-surface-alt)] border-b border-[var(--ui-border)] px-4 py-2 z-10">
        <slot name="header" />
      </div>
      <div>
        <slot name="item" v-for="(item, index) in items" :item="item" :index="index" :key="getKey(item, index)" />
      </div>
      <div v-if="$slots.footer" class="border-t border-[var(--ui-border)] bg-[var(--ui-surface-alt)]">
        <slot name="footer" />
      </div>
    </div>

    <div
      v-if="show && !hasItems && empty"
      ref="emptyEl"
      class="z-[100] mt-1 bg-[var(--ui-surface)] text-[var(--ui-text)] border border-[var(--ui-border)] rounded-2xl shadow-lg p-4"
      :style="{
        position: 'absolute',
        width: width + 'px',
        top: position.top + position.height + 'px',
        left: position.left + 'px'
      }"
    >
      <slot name="empty">
        <div class="text-center">
          <p class="text-sm font-medium text-[var(--ui-text-muted)]">{{ emptyTitle }}</p>
          <p class="text-xs text-[var(--ui-text-soft)] mt-1">{{ emptySubtitle }}</p>
        </div>
      </slot>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  items: { type: Array, default: () => [] },
  width: { type: Number, default: 0 },
  position: { type: Object, default: () => ({ top: 0, left: 0, height: 0 }) },
  maxHeight: { type: String, default: '60vh' },
  empty: { type: Boolean, default: false },
  emptyTitle: { type: String, default: 'Sin resultados' },
  emptySubtitle: { type: String, default: '' },
  itemKey: { type: [String, Function], default: null },
});

const hasItems = computed(() => props.items && props.items.length > 0);

const getKey = (item, index) => {
  if (!props.itemKey) return index;
  if (typeof props.itemKey === 'function') return props.itemKey(item, index);
  return item?.[props.itemKey] ?? index;
};

const panelEl = ref(null);
const emptyEl = ref(null);

defineExpose({
  panelEl,
  emptyEl,
});
</script>
