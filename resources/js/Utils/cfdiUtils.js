export const formatMoney = (value) => {
  const num = parseFloat(value) || 0;
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

export const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  try {
    const cleanStr = dateStr.replace('T', ' ').split(' ')[0];
    const [year, month, day] = cleanStr.split('-').map(Number);

    if (!year || !month || !day) return dateStr;

    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
  } catch {
    return dateStr;
  }
};

export const getRegimenFiscalNombre = (codigo) => {
  const regimenes = {
    '601': 'General de Ley PM',
    '603': 'Agricultores, Ganaderos, Silvícolas y Pescadores',
    '605': 'Sueldos y Salarios',
    '606': 'Arrendamiento',
    '607': 'Enajenación de Bienes',
    '608': 'Demás Ingresos',
    '610': 'Residentes en el Extranjero',
    '611': 'Dividendos',
    '612': 'Actividades Empresariales y Profesionales',
    '614': 'Intereses',
    '615': 'Obtención de Premios',
    '616': 'Sin Obligaciones Fiscales',
    '620': 'Sociedades Cooperativas de Producción',
    '621': 'Incorporación Fiscal',
    '622': 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
    '623': 'Opcional para Grupos de Sociedades',
    '624': 'Coordinados',
    '625': 'RIF (Simplificado de Confianza)',
    '626': 'Simplificado de Confianza',
  };
  return regimenes[codigo] || 'Desconocido';
};

export const generarUrlProveedor = (emisor) => {
  const params = new URLSearchParams();
  if (emisor.rfc) params.append('rfc', emisor.rfc);
  if (emisor.nombre) params.append('nombre_razon_social', emisor.nombre);

  if (emisor.rfc && emisor.rfc.length === 12) {
    params.append('tipo_persona', 'moral');
  } else if (emisor.rfc && emisor.rfc.length === 13) {
    params.append('tipo_persona', 'fisica');
  }

  if (emisor.regimen_fiscal) {
    params.append('regimen_fiscal', emisor.regimen_fiscal);
  }

  return `/proveedores/create?${params.toString()}`;
};
