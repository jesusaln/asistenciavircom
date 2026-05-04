import { ref, computed } from 'vue';
import axios from 'axios';

export const usePosCaja = ({ almacenId, formatCurrency, notify, focusSearchInput }) => {
    const cajaAbierta = ref(false);
    const showAperturaModal = ref(false);
    const showCierreModal = ref(false);
    const montoApertura = ref(0);
    const closingDetails = ref(null);
    const loadingCaja = ref(false);

    const denominaciones = ref({
        '500': 0, '200': 0, '100': 0, '50': 0, '20': 0,
        'moneda_20': 0, 'moneda_10': 0, 'moneda_5': 0, 'moneda_2': 0, 'moneda_1': 0, 'moneda_050': 0
    });

    const updateDenominacion = ({ key, value }) => {
        denominaciones.value[key] = value;
    };

    const totalDeclaradoCalculado = computed(() => {
        let total = 0;
        total += (denominaciones.value['500'] || 0) * 500;
        total += (denominaciones.value['200'] || 0) * 200;
        total += (denominaciones.value['100'] || 0) * 100;
        total += (denominaciones.value['50'] || 0) * 50;
        total += (denominaciones.value['20'] || 0) * 20;
        total += (denominaciones.value['moneda_20'] || 0) * 20;
        total += (denominaciones.value['moneda_10'] || 0) * 10;
        total += (denominaciones.value['moneda_5'] || 0) * 5;
        total += (denominaciones.value['moneda_2'] || 0) * 2;
        total += (denominaciones.value['moneda_1'] || 0) * 1;
        total += (denominaciones.value['moneda_050'] || 0) * 0.50;
        return total;
    });

    const checkCajaStatus = async () => {
        try {
            const { data } = await axios.get(route('pos.caja.status'), { params: { almacen_id: almacenId.value } });
            if (data.status === 'abierta') {
                cajaAbierta.value = true;
            } else {
                cajaAbierta.value = false;
                showAperturaModal.value = true;
            }
        } catch (e) {
            console.error('Error verificando caja', e);
            notify.error('Error de conexión al verificar caja');
        }
    };

    const abrirCaja = async () => {
        if (montoApertura.value < 0) return notify.error('Monto inválido');
        loadingCaja.value = true;
        try {
            await axios.post(route('pos.caja.open'), {
                monto_inicial: montoApertura.value,
                almacen_id: almacenId.value
            });
            cajaAbierta.value = true;
            showAperturaModal.value = false;
            notify.success('Caja abierta correctamente');
            focusSearchInput();
        } catch (e) {
            notify.error(e.response?.data?.message || 'Error al abrir caja');
        } finally {
            loadingCaja.value = false;
        }
    };

    const prepararCierreCaja = async () => {
        loadingCaja.value = true;
        try {
            const { data } = await axios.get(route('pos.caja.closing-details'), { params: { almacen_id: almacenId.value } });
            closingDetails.value = data;
            Object.keys(denominaciones.value).forEach(k => denominaciones.value[k] = 0);
            showCierreModal.value = true;
        } catch (e) {
            notify.error('Error obteniendo detalles de cierre');
        } finally {
            loadingCaja.value = false;
        }
    };

    const cerrarCaja = async (force = false) => {
        if (totalDeclaradoCalculado.value < 0) return;
        loadingCaja.value = true;
        try {
            const payload = {
                almacen_id: almacenId.value,
                detalles_cierre: denominaciones.value,
                monto_declarado: totalDeclaradoCalculado.value,
                notas: 'Cierre desde POS Premium',
                force: force
            };

            const response = await axios.post(route('pos.caja.close'), payload);
            const data = response.data;

            if (data.status === 'confirmation_required') {
                if (confirm(`${data.message}\n\nFaltante: ${formatCurrency(data.shortage_amount)}\n\n¿Desea cerrar la caja de todos modos?`)) {
                    await cerrarCaja(true);
                } else {
                    loadingCaja.value = false;
                }
                return;
            }

            cajaAbierta.value = false;
            showCierreModal.value = false;
            notify.success('Caja cerrada correctamente. Corte generado.');
            window.location.reload();
        } catch (e) {
            notify.error(e.response?.data?.message || 'Error al cerrar caja');
            loadingCaja.value = false;
        }
    };

    return {
        cajaAbierta,
        showAperturaModal,
        showCierreModal,
        montoApertura,
        closingDetails,
        loadingCaja,
        denominaciones,
        updateDenominacion,
        totalDeclaradoCalculado,
        checkCajaStatus,
        abrirCaja,
        prepararCierreCaja,
        cerrarCaja,
    };
};
