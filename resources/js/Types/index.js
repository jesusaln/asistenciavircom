/**
 * Definiciones de tipos JSDoc para el sistema Climas del Desierto.
 * Importar este archivo en componentes para habilitar intellisense.
 * 
 * @example
 * import { Venta, Cliente } from '@/Types';
 * 
 * \/** @type {Venta} *\/
 * const venta = props.venta;
 */

/**
 * @typedef {Object} Cliente
 * @property {number} id
 * @property {string} nombre_razon_social
 * @property {string} rfc
 * @property {string} [email]
 * @property {string} [telefono]
 * @property {string} regimen_fiscal
 * @property {string} codigo_postal
 * @property {string} [direccion]
 * @property {boolean} activo
 * @property {string} [created_at]
 */

/**
 * @typedef {Object} Producto
 * @property {number} id
 * @property {string} nombre
 * @property {string} [descripcion]
 * @property {string} codigo
 * @property {number} precio_venta
 * @property {number} precio_compra
 * @property {number} stock
 * @property {string} sat_clave_prod_serv
 * @property {string} sat_clave_unidad
 * @property {boolean} requiere_serie
 * @property {string} estado - 'activo' | 'inactivo' | 'agotado'
 * @property {boolean} destacado
 */

/**
 * @typedef {Object} VentaItem
 * @property {number} id
 * @property {number} venta_id
 * @property {number} producto_id
 * @property {string} producto_nombre
 * @property {number} cantidad
 * @property {number} precio_unitario
 * @property {number} subtotal
 * @property {number} impuestos
 * @property {number} total
 * @property {Producto} [producto]
 */

/**
 * @typedef {Object} Venta
 * @property {number} id
 * @property {string} folio
 * @property {string} [numero_venta]
 * @property {string} fecha
 * @property {number} cliente_id
 * @property {Cliente} [cliente]
 * @property {number} subtotal
 * @property {number} iva
 * @property {number} total
 * @property {string} estado - 'borrador' | 'pendiente' | 'aprobada' | 'pagado' | 'cancelada' | 'facturada'
 * @property {boolean} pagado
 * @property {string} [metodo_pago]
 * @property {string} [forma_pago]
 * @property {string} [factura_uuid]
 * @property {Array<VentaItem>} [items]
 * @property {Array<VentaItem>} [productos]
 */

/**
 * @typedef {Object} User
 * @property {number} id
 * @property {string} name
 * @property {string} email
 * @property {Array<string>} [roles]
 * @property {Array<string>} [permissions]
 * @property {boolean} [is_admin]
 */

/**
 * @typedef {Object} PaginationData
 * @property {number} current_page
 * @property {number} last_page
 * @property {number} per_page
 * @property {number} total
 * @property {number} from
 * @property {number} to
 * @property {string} [prev_page_url]
 * @property {string} [next_page_url]
 * @property {Array<Object>} links
 */

export const Types = {};
