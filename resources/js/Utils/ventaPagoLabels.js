/**
 * Etiquetas legibles para método interno de venta y claves SAT comunes.
 */

const METODO = {
  efectivo: 'Efectivo',
  transferencia: 'Transferencia',
  tarjeta: 'Tarjeta',
  cheque: 'Cheque',
  credito: 'Crédito',
  otros: 'Otros',
  'tarjeta de crédito': 'Tarjeta de crédito',
  'tarjeta de débito': 'Tarjeta de débito',
  Efectivo: 'Efectivo',
  Transferencia: 'Transferencia',
  Cheque: 'Cheque',
  Otro: 'Otro',
}

const FORMA_SAT = {
  '01': 'Efectivo',
  '02': 'Cheque nominativo',
  '03': 'Transferencia electrónica',
  '04': 'Tarjeta de crédito',
  '28': 'Tarjeta de débito',
  '99': 'Por definir',
}

export function labelMetodoPagoVenta(metodo) {
  if (metodo == null || String(metodo).trim() === '') {
    return ''
  }
  const raw = String(metodo).trim()
  const lower = raw.toLowerCase()
  return METODO[lower] || METODO[raw] || raw
}

export function labelFormaPagoSat(code) {
  if (code == null || String(code).trim() === '') {
    return ''
  }
  const c = String(code).trim().padStart(2, '0')
  return FORMA_SAT[c] || `Clave ${c}`
}

export function labelMetodoPagoSat(code) {
  if (!code) return ''
  const u = String(code).toUpperCase()
  if (u === 'PUE') return 'Pago en una exhibición (PUE)'
  if (u === 'PPD') return 'Pago en parcialidades (PPD)'
  return u
}

export function textoFormaPagoClienteCatalogo(clave) {
  if (!clave) return ''
  return labelFormaPagoSat(clave) + ` (${String(clave).padStart(2, '0')})`
}
