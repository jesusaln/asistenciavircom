import { ref, watch } from 'vue';

export const usePosDraft = ({
    storageKey,
    tabs,
    activeTabIndex,
    almacenId,
    notify,
}) => {
    const isRestoring = ref(true);

    const saveDraft = () => {
        if (isRestoring.value) return;

        try {
            const payload = {
                tabs: JSON.parse(JSON.stringify(tabs.value)),
                activeTabIndex: activeTabIndex.value,
                almacen: almacenId.value,
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
            // TTL: 12 horas para multi-tab
            if (Date.now() - (data.ts || 0) > 43200000) {
                localStorage.removeItem(storageKey);
                isRestoring.value = false;
                return;
            }

            if (data.tabs && Array.isArray(data.tabs)) {
                tabs.value = data.tabs;
                activeTabIndex.value = data.activeTabIndex || 0;
                if (data.almacen) almacenId.value = data.almacen;
                
                const totalItems = data.tabs.reduce((acc, t) => acc + (t.items?.length || 0), 0);
                if (totalItems > 0) {
                    notify.info(`Caja: Sesión recuperada (${data.tabs.length} pestañas).`);
                }
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
        () => [tabs.value, activeTabIndex.value, almacenId.value],
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
