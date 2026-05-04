import { ref, watch } from 'vue';

export const usePosDraft = ({
    storageKey,
    selectedItems,
    clienteId,
    almacenId,
    priceListId,
    paymentMethod,
    notify,
}) => {
    const isRestoring = ref(true);

    const saveDraft = () => {
        if (isRestoring.value) return;

        try {
            const payload = {
                items: JSON.parse(JSON.stringify(selectedItems.value)),
                cliente: clienteId.value,
                almacen: almacenId.value,
                priceList: priceListId.value,
                method: paymentMethod.value,
                ts: Date.now()
            };
            localStorage.setItem(storageKey, JSON.stringify(payload));
        } catch (e) {
            console.warn('POS Save Error:', e);
        }
    };

    const loadDraft = () => {
        isRestoring.value = true;
        try {
            const raw = localStorage.getItem(storageKey);
            if (!raw) {
                isRestoring.value = false;
                return;
            }

            const data = JSON.parse(raw);
            // TTL: 6 horas (21600000ms) para evitar conflictos entre turnos
            if (Date.now() - (data.ts || 0) > 21600000) {
                localStorage.removeItem(storageKey);
                isRestoring.value = false;
                return;
            }

            if (Array.isArray(data.items) && data.items.length > 0) {
                selectedItems.value = data.items;
                if (data.cliente) clienteId.value = data.cliente;
                if (data.almacen) almacenId.value = data.almacen;
                if (data.priceList) priceListId.value = data.priceList;
                if (data.method) paymentMethod.value = data.method;
                notify.info(`Caja: Se han recuperado ${data.items.length} productos.`);
            }
        } catch (e) {
            console.warn('POS Load Error:', e);
        } finally {
            setTimeout(() => {
                isRestoring.value = false;
            }, 100);
        }
    };

    const persistDraft = () => saveDraft();

    watch(
        () => [selectedItems.value, clienteId.value, almacenId.value, priceListId.value, paymentMethod.value],
        () => {
            if (!isRestoring.value) {
                saveDraft();
            }
        },
        { deep: true }
    );

    return {
        isRestoring,
        saveDraft,
        loadDraft,
        persistDraft,
    };
};
