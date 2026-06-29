/**
 * Formatea una fecha ISO a formato legible en español.
 * @param {string} fecha - Fecha en formato YYYY-MM-DD
 * @returns {string} Fecha formateada, ej: "20 de junio de 2026"
 */
export function formatearFecha(fecha) {
  if (!fecha) return ''
  const date = new Date(fecha + 'T00:00:00')
  return date.toLocaleDateString('es-BO', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}
/**
 * Formatea una fecha a formato corto.
 * @param {string} fecha - Fecha en formato YYYY-MM-DD
 * @returns {string} Fecha formateada, ej: "20/06/2026"
 */
export function formatearFechaCorta(fecha) {
  if (!fecha) return ''
  const date = new Date(fecha + 'T00:00:00')
  return date.toLocaleDateString('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}
/**
 * Calcula la antigüedad en días entre una fecha y hoy.
 * @param {string} fechaIngreso - Fecha en formato YYYY-MM-DD
 * @returns {number} Días de antigüedad
 */
export function calcularAntiguedad(fechaIngreso) {
  if (!fechaIngreso) return 0
  const ingreso = new Date(fechaIngreso + 'T00:00:00')
  const hoy = new Date()
  hoy.setHours(0, 0, 0, 0)
  const diff = hoy.getTime() - ingreso.getTime()
  return Math.floor(diff / (1000 * 60 * 60 * 24))
}
/**
 * Formatea un monto en Bolivianos.
 * @param {number} monto
 * @returns {string} Ej: "Bs. 150.00"
 */
export function formatearMonto(monto) {
  if (monto === null || monto === undefined) return 'Bs. 0.00'
  return `Bs. ${Number(monto).toFixed(2)}`
}
/**
 * Formatea hora de HH:MM:SS a HH:MM.
 * @param {string} hora
 * @returns {string}
 */
export function formatearHora(hora) {
  if (!hora) return ''
  return hora.substring(0, 5)
}
