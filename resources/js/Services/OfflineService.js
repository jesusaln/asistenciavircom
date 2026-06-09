/**
 * CDD Offline Engine - Native IndexedDB Wrapper
 * Gestiona la persistencia de reportes técnicos sin conexión.
 */

const DB_NAME = 'cdd_offline_v1';
const STORE_NAME = 'queue';

const getDB = () => {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, 1);
        request.onupgradeneeded = (e) => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
            }
        };
        request.onsuccess = (e) => resolve(e.target.result);
        request.onerror = (e) => reject(e.target.error);
    });
};

export const OfflineService = {
    /**
     * Guarda un reporte en la cola local
     */
    async enqueueReport(citaId, formData) {
        const db = await getDB();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            
            // Los archivos (Files/Blobs) son compatibles nativamente con IndexedDB
            const item = {
                cita_id: citaId,
                payload: formData,
                timestamp: Date.now(),
                retries: 0
            };

            const request = store.add(item);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Obtiene todos los elementos pendientes
     */
    async getQueue() {
        const db = await getDB();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Elimina un reporte procesado
     */
    async dequeueReport(id) {
        const db = await getDB();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            const request = store.delete(id);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Verifica si hay algo pendiente
     */
    async isQueueEmpty() {
        const queue = await this.getQueue();
        return queue.length === 0;
    }
};
