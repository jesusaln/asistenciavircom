import { computed } from 'vue'

const LOCALE = 'es-MX'
const TIMEZONE = 'America/Mexico_City'

export function useFormatters() {
  const currencyFormatter = computed(() =>
    new Intl.NumberFormat(LOCALE, {
      style: 'currency',
      currency: 'MXN',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  )

  const numberFormatter = computed(() =>
    new Intl.NumberFormat(LOCALE, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    })
  )

  const percentFormatter = computed(() =>
    new Intl.NumberFormat(LOCALE, {
      style: 'percent',
      minimumFractionDigits: 1,
      maximumFractionDigits: 1,
    })
  )

  const shortDateFormatter = computed(() =>
    new Intl.DateTimeFormat(LOCALE, {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      timeZone: TIMEZONE,
    })
  )

  const mediumDateFormatter = computed(() =>
    new Intl.DateTimeFormat(LOCALE, {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      timeZone: TIMEZONE,
    })
  )

  const longDateFormatter = computed(() =>
    new Intl.DateTimeFormat(LOCALE, {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      timeZone: TIMEZONE,
    })
  )

  const monthYearFormatter = computed(() =>
    new Intl.DateTimeFormat(LOCALE, {
      month: 'long',
      year: 'numeric',
      timeZone: TIMEZONE,
    })
  )

  const timeFormatter = computed(() =>
    new Intl.DateTimeFormat(LOCALE, {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
      timeZone: TIMEZONE,
    })
  )

  const dateTimeFormatter = computed(() =>
    new Intl.DateTimeFormat(LOCALE, {
      dateStyle: 'medium',
      timeStyle: 'short',
      timeZone: TIMEZONE,
    })
  )

  const fullDateTimeFormatter = computed(() =>
    new Intl.DateTimeFormat(LOCALE, {
      dateStyle: 'full',
      timeStyle: 'medium',
      timeZone: TIMEZONE,
    })
  )

  function formatCurrency(value) {
    if (value == null || isNaN(value)) return '$0.00'
    return currencyFormatter.value.format(Number(value))
  }

  function formatNumber(value) {
    if (value == null || isNaN(value)) return '0'
    return numberFormatter.value.format(Number(value))
  }

  function formatPercent(value) {
    if (value == null || isNaN(value)) return '0%'
    return percentFormatter.value.format(Number(value) / 100)
  }

  function parseDate(value) {
    if (!value) return null
    if (value instanceof Date) return value
    const d = new Date(value)
    return isNaN(d.getTime()) ? null : d
  }

  function formatShortDate(value) {
    const d = parseDate(value)
    return d ? shortDateFormatter.value.format(d) : '—'
  }

  function formatMediumDate(value) {
    const d = parseDate(value)
    return d ? mediumDateFormatter.value.format(d) : '—'
  }

  function formatLongDate(value) {
    const d = parseDate(value)
    return d ? longDateFormatter.value.format(d) : '—'
  }

  function formatMonthYear(value) {
    const d = parseDate(value)
    return d ? monthYearFormatter.value.format(d) : '—'
  }

  function formatTime(value) {
    const d = parseDate(value)
    return d ? timeFormatter.value.format(d) : '—'
  }

  function formatDateTime(value) {
    const d = parseDate(value)
    return d ? dateTimeFormatter.value.format(d) : '—'
  }

  function formatFullDateTime(value) {
    const d = parseDate(value)
    return d ? fullDateTimeFormatter.value.format(d) : '—'
  }

  function formatRelativeDate(value) {
    const d = parseDate(value)
    if (!d) return '—'
    const now = new Date()
    const diffMs = d.getTime() - now.getTime()
    const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24))

    if (diffDays === 0) return 'Hoy'
    if (diffDays === 1) return 'Mañana'
    if (diffDays === -1) return 'Ayer'
    if (Math.abs(diffDays) < 7) {
      const rtf = new Intl.RelativeTimeFormat(LOCALE, { numeric: 'auto' })
      return rtf.format(diffDays, 'day')
    }
    return mediumDateFormatter.value.format(d)
  }

  function formatFileSize(bytes) {
    if (bytes == null || isNaN(bytes)) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    let unitIndex = 0
    let size = Number(bytes)
    while (size >= 1024 && unitIndex < units.length - 1) {
      size /= 1024
      unitIndex++
    }
    return `${numberFormatter.value.format(size)} ${units[unitIndex]}`
  }

  return {
    formatCurrency,
    formatNumber,
    formatPercent,
    formatShortDate,
    formatMediumDate,
    formatLongDate,
    formatMonthYear,
    formatTime,
    formatDateTime,
    formatFullDateTime,
    formatRelativeDate,
    formatFileSize,
  }
}

export default useFormatters
