import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { format } from 'date-fns'
import { auditoriaService } from '../services/auditoriaService'

const THEME_COLOR = [15, 23, 42] // Slate 900
const ACCENT_COLOR = [220, 38, 38] // Red 600

export const pdfGenerator = {
  /**
   * Genera el encabezado base para todos los reportes
   */
  _addHeader(doc, title, subtitle = '') {
    doc.setFillColor(...THEME_COLOR)
    doc.rect(0, 0, doc.internal.pageSize.width, 30, 'F')
    
    doc.setTextColor(255, 255, 255)
    doc.setFontSize(18)
    doc.text('THE LAMPLIGHT', 14, 15)
    
    doc.setFontSize(12)
    doc.text(title, 14, 23)
    
    if (subtitle) {
      doc.setFontSize(10)
      doc.text(subtitle, doc.internal.pageSize.width - 14, 23, { align: 'right' })
    }
    
    doc.setTextColor(0, 0, 0)
  },

  /**
   * RF21: Nota de Venta (Comprobante simplificado sin valor fiscal)
   */
  exportarNotaVenta(transaccion, autoPrint = false) {
    const doc = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: [80, 200]
    })

    doc.setFontSize(14)
    doc.setFont('helvetica', 'bold')
    doc.text('BARBERÍA LIMA LIMÓN', 40, 10, { align: 'center' })
    
    doc.setFontSize(10)
    doc.setFont('helvetica', 'normal')
    doc.text('NOTA DE VENTA', 40, 15, { align: 'center' })
    doc.setFontSize(8)
    doc.text('(Sin Valor Fiscal)', 40, 19, { align: 'center' })

    doc.setFontSize(9)
    doc.text(`Fecha: ${format(new Date(), 'dd/MM/yyyy HH:mm')}`, 5, 28)
    doc.text(`Barbero: ${transaccion.barbero}`, 5, 33)
    doc.text(`Cliente: ${transaccion.cliente || 'Consumidor Final'}`, 5, 38)
    if (transaccion.contacto) {
      doc.text(`Contacto: ${transaccion.contacto}`, 5, 43)
    }

    doc.line(5, 46, 75, 46)

    let y = 52
    doc.setFont('helvetica', 'bold')
    doc.text('DETALLE', 5, y)
    doc.text('SUB', 65, y, { align: 'right' })
    y += 5
    doc.setFont('helvetica', 'normal')

    if (transaccion.servicios && transaccion.servicios.length > 0) {
      doc.setFontSize(8)
      doc.text('Servicios:', 5, y)
      y += 4
      transaccion.servicios.forEach(s => {
        doc.text(s.nombre, 8, y)
        doc.text(s.costo.toFixed(2), 75, y, { align: 'right' })
        y += 4
      })
    }

    if (transaccion.productos && transaccion.productos.length > 0) {
      y += 2
      doc.setFontSize(8)
      doc.text('Productos:', 5, y)
      y += 4
      transaccion.productos.forEach(p => {
        doc.text(`${p.nombre} (x${p.cantidad})`, 8, y)
        doc.text((p.precio * p.cantidad).toFixed(2), 75, y, { align: 'right' })
        y += 4
      })
    }

    doc.line(5, y + 2, 75, y + 2)
    y += 8

    doc.setFontSize(12)
    doc.setFont('helvetica', 'bold')
    doc.text('TOTAL:', 5, y)
    doc.text(`Bs. ${parseFloat(transaccion.total).toFixed(2)}`, 75, y, { align: 'right' })

    y += 15
    doc.setFontSize(8)
    doc.setFont('helvetica', 'normal')
    doc.text('¡Gracias por su preferencia!', 40, y, { align: 'center' })

    if (autoPrint) doc.autoPrint()
    
    auditoriaService.registrarReporte('Nota de Venta (Comprobante)', { 
      cliente: transaccion.cliente || 'Consumidor Final', 
      barbero: transaccion.barbero 
    })

    const blobUrl = doc.output('bloburl')
    return blobUrl
  },

  /**
   * HU-15: Reporte de Ventas Consolidadas
   */
  exportarReporteVentas(datos, filtros) {
    const doc = new jsPDF()
    const periodoStr = `${filtros.inicio} al ${filtros.fin}`
    
    this._addHeader(doc, 'Reporte de Ventas Consolidadas', `Periodo: ${periodoStr}`)

    doc.setFontSize(11)
    doc.setFont('helvetica', 'bold')
    doc.text('Resumen del Periodo', 14, 40)
    doc.setFont('helvetica', 'normal')
    doc.text(`Ingreso Total: Bs. ${parseFloat(datos.resumen.ingreso_total).toFixed(2)}`, 14, 47)
    doc.text(`Total Servicios: ${datos.resumen.cantidad_servicios}`, 14, 53)
    doc.text(`Total Productos: ${datos.resumen.cantidad_productos}`, 14, 59)

    const tableData = datos.transacciones.map(t => [
      t.referencia,
      t.fecha,
      t.barbero,
      t.servicios || '-',
      t.productos || '-',
      t.metodos_pago || '-',
      `Bs. ${parseFloat(t.monto_total).toFixed(2)}`
    ])

    autoTable(doc,{
      startY: 68,
      head: [['Ref', 'Fecha', 'Barbero', 'Servicios', 'Productos', 'Pago', 'Total']],
      body: tableData,
      theme: 'grid',
      headStyles: { fillColor: THEME_COLOR },
      styles: { fontSize: 8 }
    })

    auditoriaService.registrarReporte('Reporte de Ventas Consolidadas', filtros)
    window.open(doc.output('bloburl'), '_blank')
  },

  /**
   * HU-16 / RF27: Finanzas
   */
  exportarFinanzas(datos) {
    const doc = new jsPDF()
    const periodoStr = `${datos.periodo.inicio} al ${datos.periodo.fin}`
    
    this._addHeader(doc, 'Rendimiento Financiero (Admin)', `Periodo: ${periodoStr}`)

    doc.setFontSize(11)
    doc.setFont('helvetica', 'bold')
    doc.text('Ingresos Generales', 14, 40)
    doc.setFont('helvetica', 'normal')
    doc.text(`Por Servicios: Bs. ${parseFloat(datos.ingresos_servicios).toFixed(2)}`, 14, 46)
    doc.text(`Por Ventas de Productos: Bs. ${parseFloat(datos.ingresos_ventas).toFixed(2)}`, 14, 52)
    doc.setFont('helvetica', 'bold')
    doc.text(`Total Ingresos: Bs. ${parseFloat(datos.ingresos_totales).toFixed(2)}`, 14, 58)

    doc.text('Fondos de la Barbería', 105, 40)
    doc.setFont('helvetica', 'normal')
    doc.text(`Retención Servicios (50%): Bs. ${parseFloat(datos.fondos_barberia.servicios).toFixed(2)}`, 105, 46)
    doc.text(`Retención Productos: Bs. ${parseFloat(datos.fondos_barberia.productos).toFixed(2)}`, 105, 52)
    doc.text(`Ausentes (50%): Bs. ${parseFloat(datos.fondos_barberia.ausentes).toFixed(2)}`, 105, 58)
    doc.setFont('helvetica', 'bold')
    doc.text(`Fondo Total Barbería: Bs. ${parseFloat(datos.fondos_barberia.total).toFixed(2)}`, 105, 64)

    doc.text(`Comisiones Totales a Pagar: Bs. ${parseFloat(datos.comisiones_a_pagar).toFixed(2)}`, 14, 72)

    const tableData = datos.desglose_barberos.map(b => [
      b.nombre,
      `Bs. ${parseFloat(b.servicios).toFixed(2)}`,
      `Bs. ${parseFloat(b.productos).toFixed(2)}`,
      `Bs. ${parseFloat(b.ausentes).toFixed(2)}`,
      `Bs. ${parseFloat(b.total).toFixed(2)}`
    ])

    autoTable(doc,{
      startY: 80,
      head: [['Barbero', 'Comisión Servicios', 'Comisión Productos', 'Comisión Ausentes', 'Total a Pagar']],
      body: tableData,
      theme: 'grid',
      headStyles: { fillColor: THEME_COLOR },
    })

    auditoriaService.registrarReporte('Rendimiento Financiero (Admin)', datos.periodo)
    window.open(doc.output('bloburl'), '_blank')
  },

  /**
   * RF20: Reporte Inventario
   */
  exportarInventario(datos, filtros) {
    const doc = new jsPDF()
    const periodoStr = `${filtros.inicio} al ${filtros.fin}`
    
    this._addHeader(doc, 'Reporte de Inventario', `Periodo: ${periodoStr}`)

    const tableData = datos.inventario.map(i => [
      i.nombre,
      i.stock_inicial,
      i.cantidad_vendida,
      i.stock_final,
      `Bs. ${parseFloat(i.ganancia_acumulada).toFixed(2)}`
    ])

    autoTable(doc, {
      startY: 40,
      head: [['Producto', 'Stock Inicial', 'Cant. Vendida', 'Stock Final', 'Ganancia Acumulada']],
      body: tableData,
      theme: 'grid',
      headStyles: { fillColor: THEME_COLOR },
      didParseCell: function(data) {
        if (data.section === 'body' && datos.inventario[data.row.index].alerta) {
            data.cell.styles.fillColor = [254, 226, 226] // red-100
        }
      }
    })

    auditoriaService.registrarReporte('Reporte de Inventario', filtros)
    window.open(doc.output('bloburl'), '_blank')
  },

  /**
   * HU-17 / RF26: Reporte de Barbero
   */
  exportarReporteBarbero(datos) {
    const doc = new jsPDF()
    const periodoStr = `${datos.periodo.inicio} al ${datos.periodo.fin}`
    
    this._addHeader(doc, 'Reporte Personal de Barbero', `Periodo: ${periodoStr}`)

    doc.setFontSize(11)
    doc.setFont('helvetica', 'bold')
    doc.text('Resumen de Rendimiento', 14, 40)
    doc.setFont('helvetica', 'normal')
    doc.text(`Ingresos Totales Brutos: Bs. ${parseFloat(datos.ingresos_totales).toFixed(2)}`, 14, 47)
    
    doc.setFont('helvetica', 'bold')
    doc.text(`Comisión Total a Cobrar: Bs. ${parseFloat(datos.comision_calculada).toFixed(2)}`, 14, 55)
    doc.setFont('helvetica', 'normal')
    doc.text(`- Por Servicios: Bs. ${parseFloat(datos.desglose_ganancias.servicios).toFixed(2)}`, 18, 61)
    doc.text(`- Por Productos: Bs. ${parseFloat(datos.desglose_ganancias.productos).toFixed(2)}`, 18, 67)
    doc.text(`- Por Ausentes: Bs. ${parseFloat(datos.desglose_ganancias.ausentes).toFixed(2)}`, 18, 73)

    const tableData = datos.detalle_transacciones.map(t => [
      t.Fecha,
      t.Cliente,
      t.Detalle,
      `Bs. ${parseFloat(t.MontoTotal).toFixed(2)}`,
      `Bs. ${parseFloat(t.Comision).toFixed(2)}`
    ])

    autoTable(doc, {
      startY: 85,
      head: [['Fecha y Hora', 'Cliente', 'Detalle', 'Monto Bruto', 'Comisión Ganada']],
      body: tableData,
      theme: 'grid',
      headStyles: { fillColor: THEME_COLOR },
      styles: { fontSize: 8 }
    })

    auditoriaService.registrarReporte('Reporte Personal de Barbero', datos.periodo)
    window.open(doc.output('bloburl'), '_blank')
  }
}

export default pdfGenerator
