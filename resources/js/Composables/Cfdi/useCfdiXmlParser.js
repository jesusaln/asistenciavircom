/**
 * Composable for parsing CFDI XML strings into structured data.
 * Handles different namespaces and versions of CFDI.
 */
export function useCfdiXmlParser() {
    /**
     * Parse CFDI XML to structured data - robust (namespaces)
     * @param {string} xmlString 
     * @returns {object|null}
     */
    const parseCfdiXml = (xmlString) => {
        if (!xmlString) return null
        try {
            const cleanXml = xmlString.replace(/^\uFEFF/, '').trim()
            const parser = new DOMParser()
            const xmlDoc = parser.parseFromString(cleanXml, 'text/xml')
            const parseError = xmlDoc.getElementsByTagName('parsererror')[0]
            if (parseError) return null

            const getByLocalName = (name) => {
                const all = xmlDoc.getElementsByTagName('*')
                for (const node of all) {
                    if (node.localName === name) return node
                }
                return null
            }

            const getAllByLocalName = (name) => {
                const nodes = []
                const all = xmlDoc.getElementsByTagName('*')
                for (const node of all) {
                    if (node.localName === name) nodes.push(node)
                }
                return nodes
            }

            const getAttr = (el, attr) => el?.getAttribute(attr) || ''
            const comprobante = getByLocalName('Comprobante')
            if (!comprobante) return null

            const emisor = getByLocalName('Emisor')
            const receptor = getByLocalName('Receptor')
            const timbre = getByLocalName('TimbreFiscalDigital')
            const impuestos = getByLocalName('Impuestos')
            const conceptos = getAllByLocalName('Concepto')
            const traslados = getAllByLocalName('Traslado')
            const retenciones = getAllByLocalName('Retencion')
            const relacionados = getAllByLocalName('CfdiRelacionado')
            const pagos = getAllByLocalName('Pago')

            const docsRelacionados = []
            const doctos = getAllByLocalName('DoctoRelacionado')
            doctos.forEach(doc => {
                docsRelacionados.push({
                    idDocumento: getAttr(doc, 'IdDocumento'),
                    serie: getAttr(doc, 'Serie'),
                    folio: getAttr(doc, 'Folio'),
                    moneda: getAttr(doc, 'MonedaDR'),
                    metodoPago: getAttr(doc, 'MetodoDePagoDR'),
                    numParcialidad: getAttr(doc, 'NumParcialidad'),
                    impSaldoAnt: getAttr(doc, 'ImpSaldoAnt'),
                    impPagado: getAttr(doc, 'ImpPagado'),
                    impSaldoInsoluto: getAttr(doc, 'ImpSaldoInsoluto'),
                    objetoImp: getAttr(doc, 'ObjetoImpDR')
                })
            })

            return {
                version: getAttr(comprobante, 'Version'),
                serie: getAttr(comprobante, 'Serie'),
                folio: getAttr(comprobante, 'Folio'),
                fecha: getAttr(comprobante, 'Fecha'),
                formaPago: getAttr(comprobante, 'FormaPago'),
                metodoPago: getAttr(comprobante, 'MetodoPago'),
                tipoComprobante: getAttr(comprobante, 'TipoDeComprobante'),
                moneda: getAttr(comprobante, 'Moneda'),
                tipoCambio: getAttr(comprobante, 'TipoCambio'),
                lugarExpedicion: getAttr(comprobante, 'LugarExpedicion'),
                exportacion: getAttr(comprobante, 'Exportacion'),
                noCertificado: getAttr(comprobante, 'NoCertificado'),
                condicionesPago: getAttr(comprobante, 'CondicionesDePago'),
                descuento: getAttr(comprobante, 'Descuento'),
                subtotal: getAttr(comprobante, 'SubTotal'),
                total: getAttr(comprobante, 'Total'),
                emisor: emisor ? {
                    rfc: getAttr(emisor, 'Rfc'),
                    nombre: getAttr(emisor, 'Nombre'),
                    regimenFiscal: getAttr(emisor, 'RegimenFiscal'),
                    facAtrAdquirente: getAttr(emisor, 'FacAtrAdquirente')
                } : null,
                receptor: receptor ? {
                    rfc: getAttr(receptor, 'Rfc'),
                    nombre: getAttr(receptor, 'Nombre'),
                    usoCfdi: getAttr(receptor, 'UsoCFDI'),
                    domicilioFiscal: getAttr(receptor, 'DomicilioFiscalReceptor'),
                    regimenFiscal: getAttr(receptor, 'RegimenFiscalReceptor'),
                    numRegIdTrib: getAttr(receptor, 'NumRegIdTrib'),
                    residenciaFiscal: getAttr(receptor, 'ResidenciaFiscal')
                } : null,
                conceptos: conceptos.map(c => ({
                    clave: getAttr(c, 'ClaveProdServ'),
                    noIdentificacion: getAttr(c, 'NoIdentificacion'),
                    cantidad: getAttr(c, 'Cantidad'),
                    unidad: getAttr(c, 'ClaveUnidad'),
                    unidadNombre: getAttr(c, 'Unidad'),
                    descripcion: getAttr(c, 'Descripcion'),
                    valorUnitario: getAttr(c, 'ValorUnitario'),
                    importe: getAttr(c, 'Importe'),
                    descuento: getAttr(c, 'Descuento'),
                    objetoImp: getAttr(c, 'ObjetoImp')
                })),
                impuestos: {
                    totalImpuestosTrasladados: impuestos ? getAttr(impuestos, 'TotalImpuestosTrasladados') : '',
                    totalImpuestosRetenidos: impuestos ? getAttr(impuestos, 'TotalImpuestosRetenidos') : '',
                    traslados: traslados.map(t => ({
                        base: getAttr(t, 'Base'),
                        impuesto: getAttr(t, 'Impuesto'),
                        tipoFactor: getAttr(t, 'TipoFactor'),
                        tasaOCuota: getAttr(t, 'TasaOCuota'),
                        importe: getAttr(t, 'Importe')
                    })),
                    retenciones: retenciones.map(r => ({
                        impuesto: getAttr(r, 'Impuesto'),
                        importe: getAttr(r, 'Importe')
                    }))
                },
                timbre: timbre ? {
                    uuid: getAttr(timbre, 'UUID'),
                    fechaTimbrado: getAttr(timbre, 'FechaTimbrado'),
                    rfcProvCertif: getAttr(timbre, 'RfcProvCertif'),
                    noCertificadoSAT: getAttr(timbre, 'NoCertificadoSAT'),
                    selloCFD: getAttr(timbre, 'SelloCFD'),
                    selloSAT: getAttr(timbre, 'SelloSAT')
                } : null,
                pagos: pagos.map(p => ({
                    fechaPago: getAttr(p, 'FechaPago'),
                    formaDePago: getAttr(p, 'FormaDePagoP'),
                    moneda: getAttr(p, 'MonedaP'),
                    tipoCambio: getAttr(p, 'TipoCambioP'),
                    monto: getAttr(p, 'Monto'),
                    numOperacion: getAttr(p, 'NumOperacion'),
                    rfcCtaOrden: getAttr(p, 'RfcEmisorCtaOrd'),
                    ctaOrdenante: getAttr(p, 'CtaOrdenante'),
                    rfcCtaBenef: getAttr(p, 'RfcEmisorCtaBen'),
                    ctaBeneficiario: getAttr(p, 'CtaBeneficiario')
                })),
                relacionados: relacionados.map(r => ({
                    tipoRelacion: getAttr(r.parentElement, 'TipoRelacion') || '',
                    uuid: getAttr(r, 'UUID')
                })),
                docsRelacionados
            }
        } catch (e) {
            console.error('Error parsing XML:', e)
            return null
        }
    }

    return {
        parseCfdiXml
    }
}
