import { jsPDF } from "jspdf";
import axios from 'axios';

/**
 * Configuración de la empresa - cargada dinámicamente
 */
let EMPRESA_CONFIG = null;

/**
 * Cargar configuración de empresa
 */
const cargarEmpresaConfig = async () => {
  if (EMPRESA_CONFIG) return EMPRESA_CONFIG;

  try {
    const response = await axios.get('/empresa/configuracion/api');
    const config = response.data.configuracion;

    // Construir dirección completa
    const direccionParts = [];
    if (config.calle) direccionParts.push(config.calle);
    if (config.numero_exterior) direccionParts.push(`No. ${config.numero_exterior}`);
    if (config.numero_interior) direccionParts.push(`Int. ${config.numero_interior}`);
    if (config.colonia) direccionParts.push(`Col. ${config.colonia}`);
    if (config.ciudad) direccionParts.push(config.ciudad);
    if (config.estado) direccionParts.push(config.estado);
    if (config.pais) direccionParts.push(config.pais);
    if (config.codigo_postal) direccionParts.push(`CP ${config.codigo_postal}`);

    const direccionCompleta = direccionParts.length > 0 ? direccionParts.join(', ') : 'Dirección no configurada';

    EMPRESA_CONFIG = {
      nombre: config.nombre_empresa || "CLIMAS DEL DESIERTO",
      propietario: "JESUS ALBERTO LOPEZ NORIEGA", // Mantener fijo o agregar a config si es necesario
      direccion: direccionCompleta,
      telefono: config.telefono || "(55) 5555-5555",
      email: config.email || "jesus@climasdeldesierto.com",
      web: config.sitio_web || "www.climasdeldesierto.com",
      logo: config.logo_reportes_url || config.logo_url, // Usar logo para reportes
      iva_porcentaje: config.iva_porcentaje || 16,
    };

    return EMPRESA_CONFIG;
  } catch (error) {
    console.error('Error al cargar configuración de empresa:', error);
    // Fallback a configuración por defecto
    EMPRESA_CONFIG = {
      nombre: "CLIMAS DEL DESIERTO",
      propietario: "JESUS ALBERTO LOPEZ NORIEGA",
      direccion: "Av. Paseo de la Reina, 2345",
      telefono: "(55) 5555-5555",
      email: "jesus@climasdeldesierto.com",
      web: "www.climasdeldesierto.com",
      logo: null,
      iva_porcentaje: 16,
    };
    return EMPRESA_CONFIG;
  }
};

/**
 * Configuración de estilos y dimensiones
 */
const ESTILOS = {
  margen: {
    izquierdo: 14,
    derecho: 196,
    superior: 10,
    inferior: 280
  },
  fuentes: {
    titulo: { size: 18, style: "bold" },
    subtitulo: { size: 12, style: "bold" },
    encabezado: { size: 10, style: "normal" },
    texto: { size: 8, style: "normal" },
    textoSmall: { size: 7, style: "normal" }
  },
  colores: {
    primario: [41, 128, 185], // Azul
    secundario: [52, 73, 94], // Gris oscuro
    texto: [44, 62, 80],      // Negro suave
    linea: [189, 195, 199]    // Gris claro
  }
};

/**
 * Utilidades para el PDF
 */
class PDFUtils {
  static formatearFecha(fecha) {
    if (!fecha || Number.isNaN(new Date(fecha).getTime())) {
      return new Date().toLocaleDateString('es-MX');
    }
    return new Date(fecha).toLocaleDateString('es-MX');
  }

  static formatearMoneda(valor, moneda = 'MXN') {
    const numero = Number(valor) || 0;
    return new Intl.NumberFormat('es-MX', {
      style: 'currency',
      currency: moneda
    }).format(numero);
  }

  static truncarTexto(texto, maxLength = 50) {
    if (!texto) return '';
    return texto.length > maxLength ? `${texto.substring(0, maxLength)}...` : texto;
  }

  static validarDatos(data) {
    const errores = [];

    if (!data) {
      errores.push('No se proporcionaron datos');
      return errores;
    }

    if (!data.id) errores.push('ID del documento no encontrado');
    if (!data.cliente) errores.push('Datos del cliente no encontrados');
    if (!data.productos || !Array.isArray(data.productos)) {
      errores.push('Lista de productos no válida');
    }

    return errores;
  }
}

/**
 * Clase principal para generar PDF
 */
class GeneradorPDF {
  constructor() {
    this.doc = new jsPDF();
    this.posicionY = ESTILOS.margen.superior;
    this.paginaActual = 1;
  }

  // Configurar fuente y tamaño
  setFont(size, style = 'normal') {
    this.doc.setFontSize(size);
    this.doc.setFont('helvetica', style);
  }

  // Configurar color
  setColor(color) {
    this.doc.setTextColor(...color);
  }

  // Verificar si necesita nueva página
  verificarEspacio(espacioNecesario = 20) {
    if (this.posicionY + espacioNecesario > ESTILOS.margen.inferior) {
      this.nuevaPagina();
      return true;
    }
    return false;
  }

  // Crear nueva página
  nuevaPagina() {
    this.doc.addPage();
    this.paginaActual++;
    this.posicionY = ESTILOS.margen.superior;
    this.agregarNumeroPagina();
  }

  // Agregar número de página
  agregarNumeroPagina() {
    this.setFont(ESTILOS.fuentes.textoSmall.size, ESTILOS.fuentes.textoSmall.style);
    this.setColor(ESTILOS.colores.texto);
    this.doc.text(
      `Página ${this.paginaActual}`,
      ESTILOS.margen.derecho - 20,
      290,
      { align: 'right' }
    );
  }

  // Agregar línea divisoria
  agregarLinea(y = this.posicionY) {
    this.doc.setDrawColor(...ESTILOS.colores.linea);
    this.doc.line(ESTILOS.margen.izquierdo, y, ESTILOS.margen.derecho, y);
  }

  // Encabezado de la empresa
  async agregarEncabezado() {
    const config = await cargarEmpresaConfig();
    const { nombre, propietario, direccion, telefono, email, web, logo } = config;

    // Logo si está disponible
    if (logo) {
      try {
        // Agregar imagen del logo (ajustar tamaño y posición según necesites)
        this.doc.addImage(logo, 'JPEG', 85, this.posicionY, 40, 20); // x, y, width, height
        this.posicionY += 25; // Espacio después del logo
      } catch (error) {
        console.warn('Error al cargar logo:', error);
        // Continuar sin logo si hay error
      }
    }

    // Nombre de la empresa
    this.setFont(ESTILOS.fuentes.titulo.size, ESTILOS.fuentes.titulo.style);
    this.setColor(ESTILOS.colores.primario);
    this.doc.text(nombre, 105, this.posicionY + 10, { align: 'center' });

    // Propietario
    this.setFont(ESTILOS.fuentes.encabezado.size, ESTILOS.fuentes.encabezado.style);
    this.setColor(ESTILOS.colores.secundario);
    this.doc.text(propietario, 105, this.posicionY + 18, { align: 'center' });

    // Información de contacto
    this.setFont(ESTILOS.fuentes.texto.size, ESTILOS.fuentes.texto.style);
    this.setColor(ESTILOS.colores.texto);

    const contactoInfo = [direccion, telefono, email, web].filter(info => info);
    contactoInfo.forEach((info, index) => {
      this.doc.text(info, 105, this.posicionY + 24 + (index * 4), { align: 'center' });
    });

    this.posicionY += 45;
    this.agregarLinea();
    this.posicionY += 8;
  }

  // Título del documento
  agregarTituloDocumento(tipoDocumento, data) {
    this.setFont(ESTILOS.fuentes.subtitulo.size, ESTILOS.fuentes.subtitulo.style);
    this.setColor(ESTILOS.colores.primario);
    this.doc.text(tipoDocumento.toUpperCase(), ESTILOS.margen.izquierdo, this.posicionY);

    // Información básica del documento
    this.posicionY += 8;
    this.setFont(ESTILOS.fuentes.texto.size, ESTILOS.fuentes.texto.style);
    this.setColor(ESTILOS.colores.texto);

    // Número y fecha en la misma línea
    this.doc.text(`Número: ${data.id}`, ESTILOS.margen.izquierdo, this.posicionY);
    this.doc.text(`Fecha: ${PDFUtils.formatearFecha(data.fecha)}`, 140, this.posicionY);

    this.posicionY += 8;
    this.agregarLinea();
    this.posicionY += 8;
  }

  // Información del cliente
  agregarInformacionCliente(cliente) {
    if (!cliente) {
      console.warn('No se proporcionó cliente');
      return;
    }

    this.verificarEspacio(50);
    this.setFont(ESTILOS.fuentes.subtitulo.size, ESTILOS.fuentes.subtitulo.style);
    this.setColor(ESTILOS.colores.secundario);
    this.doc.text('CLIENTE', ESTILOS.margen.izquierdo, this.posicionY);
    this.posicionY += 8;

    // Normalización de campos (compatible con tu modal)
    const nombre = cliente.nombre || cliente.nombre_razon_social || 'Cliente sin nombre';
    const rfc = cliente.rfc || 'RFC no proporcionado';
    const email = cliente.email || 'Sin correo';
    const telefono = cliente.telefono || cliente.celular || 'Sin teléfono';
    const direccion = this.construirDireccion(cliente);
    const regimen = cliente.regimen_fiscal || 'No especificado';
    const usoCfdi = cliente.uso_cfdi || 'No especificado';

    const clienteInfo = [
      ['Nombre:', nombre],
      ['RFC:', rfc],
      ['Dirección:', direccion],
      ['Email:', email],
      ['Teléfono:', telefono],
      ['Régimen Fiscal:', regimen],
      ['Uso CFDI:', usoCfdi]
    ];

    this.setFont(ESTILOS.fuentes.texto.size, ESTILOS.fuentes.texto.style);

    clienteInfo.forEach(([etiqueta, valor], index) => {
      if (!valor) return;

      const xPos = index % 2 === 0 ? ESTILOS.margen.izquierdo : 110;
      const yPos = this.posicionY + Math.floor(index / 2) * 6;

      // Etiqueta en negrita
      this.doc.setFont('helvetica', 'bold');
      this.setColor(ESTILOS.colores.secundario);
      this.doc.text(etiqueta, xPos, yPos);

      // Valor en texto normal
      this.doc.setFont('helvetica', 'normal');
      this.setColor(ESTILOS.colores.texto);
      const valorTruncado = PDFUtils.truncarTexto(valor, 35);
      this.doc.text(valorTruncado, xPos + 25, yPos);
    });

    this.posicionY += Math.ceil(clienteInfo.length / 2) * 6 + 5;
    this.agregarLinea();
    this.posicionY += 8;
  }

  construirDireccion(cliente) {
    if (!cliente) return 'N/A';

    const partes = [
      cliente.calle,
      cliente.numero_exterior,
      cliente.numero_interior ? `Int. ${cliente.numero_interior}` : null,
      cliente.colonia,
      cliente.codigo_postal,
      cliente.municipio,
      cliente.estado,
      cliente.pais
    ].filter(Boolean);

    return partes.length > 0 ? partes.join(', ') : 'Dirección no proporcionada';
  }

  // Tabla de productos
  agregarTablaProductos(productos) {
    if (!productos || productos.length === 0) {
      this.doc.text('No hay productos registrados', ESTILOS.margen.izquierdo, this.posicionY);
      this.posicionY += 10;
      return 0;
    }

    this.verificarEspacio(40);

    // Encabezados de la tabla
    this.setFont(ESTILOS.fuentes.texto.size, 'bold');
    this.setColor(ESTILOS.colores.secundario);

    const encabezados = [
      { texto: 'Cant.', x: ESTILOS.margen.izquierdo },
      { texto: 'Concepto', x: 35 },
      { texto: 'Precio Unit.', x: 130 },
      { texto: 'Subtotal', x: 170 }
    ];

    encabezados.forEach(encabezado => {
      this.doc.text(encabezado.texto, encabezado.x, this.posicionY);
    });

    this.posicionY += 4;
    this.agregarLinea();
    this.posicionY += 6;

    // Productos
    this.setFont(ESTILOS.fuentes.texto.size, 'normal');
    this.setColor(ESTILOS.colores.texto);

    let subtotalGeneral = 0;

    productos.forEach(producto => {
      this.verificarEspacio(8);

      const cantidad = producto.pivot?.cantidad || 1;
      const precio = producto.pivot?.precio || producto.precio || 0;
      const subtotal = cantidad * precio;
      subtotalGeneral += subtotal;

      // Datos del producto
      this.doc.text(cantidad.toString(), ESTILOS.margen.izquierdo, this.posicionY);

      const nombreTruncado = PDFUtils.truncarTexto(producto.nombre || 'Producto sin nombre', 45);
      this.doc.text(nombreTruncado, 35, this.posicionY);

      this.doc.text(PDFUtils.formatearMoneda(precio), 130, this.posicionY);
      this.doc.text(PDFUtils.formatearMoneda(subtotal), 170, this.posicionY);

      this.posicionY += 6;
    });

    this.posicionY += 4;
    this.agregarLinea();
    this.posicionY += 8;

    return subtotalGeneral;
  }

  // Totales
  agregarTotales(data, subtotalCalculado) {
    const total = data.total || subtotalCalculado;
    const ivaRate = (data.ivaPorcentaje || EMPRESA_CONFIG?.iva_porcentaje || 16) / 100;
    const iva = data.iva || (total * ivaRate);
    const subtotal = data.subtotal || (total - iva);

    this.verificarEspacio(25);

    this.setFont(ESTILOS.fuentes.texto.size, 'normal');
    this.setColor(ESTILOS.colores.texto);

    const totales = [
      ['Subtotal:', subtotal],
      [`IVA (${(data.ivaPorcentaje || EMPRESA_CONFIG?.iva_porcentaje || 16)}%):`, iva],
      ['TOTAL:', total]
    ];

    totales.forEach(([etiqueta, valor], index) => {
      const esTotal = index === totales.length - 1;

      if (esTotal) {
        this.setFont(ESTILOS.fuentes.subtitulo.size, 'bold');
        this.setColor(ESTILOS.colores.primario);
      }

      this.doc.text(etiqueta, 140, this.posicionY);
      this.doc.text(PDFUtils.formatearMoneda(valor), 170, this.posicionY);

      this.posicionY += esTotal ? 8 : 6;
    });
  }

  // Pie de página (opcional)
  agregarPiePagina(mensaje = '') {
    if (mensaje) {
      this.setFont(ESTILOS.fuentes.textoSmall.size, 'italic');
      this.setColor(ESTILOS.colores.texto);
      this.doc.text(mensaje, 105, 285, { align: 'center' });
    }
    this.agregarNumeroPagina();
  }
}

/**
 * Función principal para generar el PDF
 */
export const generarPDF = async (tipoDocumento, data, opciones = {}) => {
  console.log("Generando PDF con datos:", data);

  try {
    // Validar datos
    const errores = PDFUtils.validarDatos(data);
    if (errores.length > 0) {
      console.error('Errores en los datos:', errores);
      throw new Error(`Datos inválidos: ${errores.join(', ')}`);
    }

    // Crear instancia del generador
    const generador = new GeneradorPDF();

    // Generar contenido (ahora async por la carga de config)
    await generador.agregarEncabezado();
    generador.agregarTituloDocumento(tipoDocumento, data);
    generador.agregarInformacionCliente(data.cliente);

    const subtotalCalculado = generador.agregarTablaProductos(data.productos);
    generador.agregarTotales(data, subtotalCalculado);

    // Pie de página opcional
    if (opciones.mensaje) {
      generador.agregarPiePagina(opciones.mensaje);
    } else {
      generador.agregarNumeroPagina();
    }

    // Configurar nombre del archivo
    const nombreArchivo = opciones.nombreArchivo ||
      `${tipoDocumento.replace(/\s+/g, '_')}_${data.id}_${Date.now()}.pdf`;

    // Guardar o retornar el PDF
    if (opciones.retornarBlob) {
      return generador.doc.output('blob');
    } else if (opciones.abrirEnNuevaVentana) {
      generador.doc.output('dataurlnewwindow');
    } else {
      generador.doc.save(nombreArchivo);
    }

    console.log(`PDF "${nombreArchivo}" generado exitosamente`);
    return true;

  } catch (error) {
    console.error('Error al generar PDF:', error);
    throw new Error(`Error al generar PDF: ${error.message}`);
  }
};

/**
 * Función auxiliar para generar múltiples documentos
 */
export const generarPDFMultiple = async (documentos, opciones = {}) => {
  const resultados = [];

  for (const [index, documento] of documentos.entries()) {
    try {
      const resultado = await generarPDF(
        documento.tipo,
        documento.data,
        {
          ...opciones,
          nombreArchivo: `${documento.tipo}_${documento.data.id}_${index + 1}.pdf`
        }
      );
      resultados.push({ exito: true, documento: documento.tipo, resultado });
    } catch (error) {
      resultados.push({ exito: false, documento: documento.tipo, error: error.message });
    }
  }

  return resultados;
};

// Ejemplo de uso:
/*
const data = {
  id: "001",
  fecha: "2024-01-15",
  cliente: {
    nombre_razon_social: "Empresa Ejemplo S.A. de C.V.",
    rfc: "EEJ123456789",
    calle: "Calle Principal",
    numero_exterior: "123",
    numero_interior: "A",
    colonia: "Centro",
    codigo_postal: "12345",
    municipio: "Ciudad",
    estado: "Estado",
    pais: "México",
    email: "contacto@empresa.com",
    telefono: "555-1234",
    regimen_fiscal: "Régimen General",
    uso_cfdi: "Gastos en general"
  },
  productos: [
    {
      nombre: "Servicio de Instalación",
      pivot: { cantidad: 1, precio: 1500.00 }
    }
  ],
  total: 1740.00
};

// Uso básico
generarPDF("Cotización", data);

// Uso con opciones
generarPDF("Factura", data, {
  nombreArchivo: "factura_personalizada.pdf",
  mensaje: "Gracias por su preferencia",
  retornarBlob: false
});
*/
