import { ref } from 'vue';

export function useProveedorExpress({ axios, Swal, cfdiData, newProviderEmail, newProviderPhone }) {
  const registrandoProveedor = ref(false);

  const registrarProveedorExpress = async () => {
    if (!cfdiData.value?.emisor) return;

    registrandoProveedor.value = true;

    try {
      const emisor = cfdiData.value.emisor;
      const nombreEmisor = emisor.nombre || emisor.Nombre || '';
      if (!nombreEmisor) {
        console.warn('Nombre de emisor no encontrado en CFDI data', emisor);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'El nombre del proveedor no se encontró en el XML.',
          confirmButtonColor: '#EF4444',
        });
        return;
      }

      const payload = {
        rfc: emisor.rfc,
        nombre_razon_social: nombreEmisor,
        regimen_fiscal: emisor.regimen_fiscal,
        codigo_postal: cfdiData.value.lugar_expedicion || '',
        tipo_persona: emisor.rfc && emisor.rfc.length === 13 ? 'fisica' : 'moral',
        activo: true,
        email: newProviderEmail.value,
        telefono: newProviderPhone.value,
      };

      console.log('Registrando proveedor con datos:', payload);

      const response = await axios.post(route('proveedores.store'), payload);

      if (response.data.success) {
        cfdiData.value.proveedor_encontrado = response.data.proveedor;

        if (cfdiData.value.emisor) {
          cfdiData.value.emisor.id = response.data.proveedor.id;
        }
        cfdiData.value.proveedor_id = response.data.proveedor.id;

        Swal.fire({
          icon: 'success',
          title: 'Proveedor registrado',
          text: 'El proveedor se registró correctamente.',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
        });
      }
    } catch (error) {
      console.error('Error registrando proveedor:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: error.response?.data?.message || error.message || 'Error al registrar proveedor',
        confirmButtonColor: '#EF4444',
      });
    } finally {
      registrandoProveedor.value = false;
    }
  };

  return { registrandoProveedor, registrarProveedorExpress };
}
