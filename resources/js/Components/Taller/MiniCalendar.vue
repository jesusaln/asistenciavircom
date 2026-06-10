<script setup>
import { ref, computed, onMounted } from 'vue'
import { format, addMonths, subMonths, startOfMonth, endOfMonth, startOfWeek, endOfWeek, eachDayOfInterval, isSameMonth, isSameDay, isToday, addDays, parseISO } from 'date-fns'
import { es } from 'date-fns/locale'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faChevronLeft, faChevronRight, faCalendarDay, faTimes } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
  modelValue: String,
  label: { type: String, default: 'Fecha' },
  required: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue'])

const showCalendar = ref(false)
const currentMonth = ref(new Date())

const selectedDate = computed(() => {
  return props.modelValue ? parseISO(props.modelValue) : null
})

const formattedValue = computed(() => {
  if (!selectedDate.value) return ''
  return format(selectedDate.value, 'PPP', { locale: es })
})

const calendarDays = computed(() => {
  const start = startOfWeek(startOfMonth(currentMonth.value), { weekStartsOn: 0 })
  const end = endOfWeek(endOfMonth(currentMonth.value), { weekStartsOn: 0 })
  
  return eachDayOfInterval({ start, end }).map(day => ({
    date: day,
    isCurrentMonth: isSameMonth(day, currentMonth.value),
    isSelected: selectedDate.value ? isSameDay(day, selectedDate.value) : false,
    isToday: isToday(day)
  }))
})

const weekDays = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const nextMonth = () => currentMonth.value = addMonths(currentMonth.value, 1)
const prevMonth = () => currentMonth.value = subMonths(currentMonth.value, 1)

const selectDate = (day) => {
  const dateStr = format(day.date, 'yyyy-MM-dd')
  emit('update:modelValue', dateStr)
  showCalendar.value = false
}

const clearDate = () => {
  emit('update:modelValue', '')
  showCalendar.value = false
}

const toggleCalendar = () => {
  if (showCalendar.value) {
    showCalendar.value = false
  } else {
    if (selectedDate.value) currentMonth.value = selectedDate.value
    showCalendar.value = true
  }
}
</script>

<template>
  <div class="relative w-full">
    <label v-if="label" class="text-xs font-black text-slate-400 uppercase tracking-wide ml-1 mb-2 block">
      {{ label }}
      <span v-if="required" class="text-brand-500">*</span>
    </label>
    
    <div class="relative group">
      <div 
        @click.stop="toggleCalendar"
        class="w-full bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-2xl py-3.5 pl-12 pr-4 text-[var(--ui-text)] cursor-pointer hover:border-[var(--ui-accent)]/50 transition-all flex items-center min-h-[52px]"
        :class="{ 'border-[var(--ui-accent)]/50 ring-2 ring-[var(--ui-accent)]/20': showCalendar }"
      >
        <FontAwesomeIcon :icon="faCalendarDay" class="absolute left-4 text-[var(--ui-text-muted)] group-hover:text-[var(--ui-accent)] transition-colors" />
        <span v-if="formattedValue" class="text-sm font-medium">{{ formattedValue }}</span>
        <span v-else class="text-[var(--ui-text-muted)] text-sm">Seleccionar fecha...</span>
      </div>

      <button 
        v-if="modelValue" 
        @click.stop="clearDate"
        class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--ui-text-muted)] hover:text-[var(--ui-text)] transition-colors"
      >
        <FontAwesomeIcon :icon="faTimes" size="xs" />
      </button>
    </div>

    <!-- Dropdown Calendar -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-2 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-2 scale-95"
    >
      <div 
        v-if="showCalendar" 
        v-click-outside="() => showCalendar = false"
        @click.stop
        class="absolute z-[100] mt-2 p-4 bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-3xl shadow-2xl backdrop-blur-xl w-[320px]"
      >


        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <button @click="prevMonth" type="button" class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-[var(--ui-surface-soft)] text-[var(--ui-text-muted)]">
            <FontAwesomeIcon :icon="faChevronLeft" size="xs" />
          </button>
          <h4 class="text-sm font-black text-[var(--ui-text)] uppercase tracking-wider">
            {{ format(currentMonth, 'MMMM yyyy', { locale: es }) }}
          </h4>
          <button @click="nextMonth" type="button" class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-[var(--ui-surface-soft)] text-[var(--ui-text-muted)]">
            <FontAwesomeIcon :icon="faChevronRight" size="xs" />
          </button>
        </div>

        <!-- Weekdays -->
        <div class="grid grid-cols-7 mb-2">
          <div v-for="day in weekDays" :key="day" class="text-center text-[10px] font-black text-[var(--ui-text-muted)] uppercase">
            {{ day }}
          </div>
        </div>

        <!-- Days Grid -->
        <div class="grid grid-cols-7 gap-1">
          <button
            v-for="(day, idx) in calendarDays"
            :key="idx"
            type="button"
            @click="selectDate(day)"
            class="aspect-square flex items-center justify-center text-xs rounded-xl transition-all relative overflow-hidden"
            :class="[
              !day.isCurrentMonth ? 'text-[var(--ui-text-muted)]/30' : 'text-[var(--ui-text)] hover:bg-[var(--ui-surface-soft)]',
              day.isSelected ? 'bg-[var(--ui-accent)] text-[var(--ui-accent-contrast)] font-black shadow-lg shadow-[var(--ui-accent)]/20' : '',
              day.isToday && !day.isSelected ? 'text-[var(--ui-accent)] font-black' : ''
            ]"
          >
            {{ format(day.date, 'd') }}
            <div v-if="day.isToday && !day.isSelected" class="absolute bottom-1 w-1 h-1 bg-[var(--ui-accent)] rounded-full"></div>
          </button>
        </div>

        <!-- Footer -->
        <div class="mt-4 pt-4 border-t border-[var(--ui-border)] flex justify-center">
          <button 
            @click="selectDate({ date: new Date() })"
            type="button"
            class="text-[10px] font-black text-[var(--ui-accent)] uppercase tracking-wide hover:brightness-110"
          >
            Hoy: {{ format(new Date(), 'PP', { locale: es }) }}
          </button>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.backdrop-blur-xl {
  backdrop-filter: blur(24px);
}
</style>
