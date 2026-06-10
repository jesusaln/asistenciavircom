/**
 * Normaliza una cadena de texto eliminando acentos y convirtiéndola a minúsculas.
 * @param {string} text 
 * @returns {string}
 */
export const normalizeText = (text) => {
    if (!text) return '';
    return text.toString()
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim();
};

/**
 * Verifica si un texto incluye un término de búsqueda, ignorando acentos y mayúsculas.
 * @param {string} text Texto donde buscar
 * @param {string} search Término de búsqueda
 * @returns {boolean}
 */
export const includesSearch = (text, search) => {
    if (!search) return true;
    if (!text) return false;
    return normalizeText(text).includes(normalizeText(search));
};
