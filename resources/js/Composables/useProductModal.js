import { ref } from 'vue';

export function useProductModal({ cfdiData, Swal, axios, fetchSelectedProducts }) {
  const showProductModal = ref(false);
  const savingProduct = ref(false);
  const catalogosLoaded = ref(false);
  const catalogos = ref({ categorias: [], marcas: [], unidades: [] });
  const currentConceptIndex = ref(null);
  const currentConcept = ref(null);

  const showCategoriaModal = ref(false);
  const savingCategoria = ref(false);
  const nuevaCategoria = ref({ nombre: '', descripcion: '', estado: 'activo' });

  const showMarcaModal = ref(false);
  const savingMarca = ref(false);
  const nuevaMarca = ref({ nombre: '', descripcion: '', estado: 'activo' });

  const productForm = ref({
    nombre: '',
    codigo: '',
    codigo_barras: '',
    categoria_id: '',
    marca_id: '',
    precio_compra: 0,
    precio_venta: 0,
    unidad_medida: '',
    requiere_serie: false,
    tipo_producto: 'fisico',
    estado: 'activo',
    sat_clave_prod_serv: '',
    sat_clave_unidad: '',
    sat_objeto_imp: '02',
    stock: 0,
    descripcion: '',
  });

  const productSerials = ref([]);
  const currentSerial = ref('');
  const serialInput = ref(null);
  const isBulkProductModal = ref(false);

  const saveCategoria = async () => {
    if (!nuevaCategoria.value.nombre.trim()) return;
    savingCategoria.value = true;
    try {
      const response = await axios.post('/api/categorias', nuevaCategoria.value);
      if (response.data && response.data.id) {
        catalogos.value.categorias.push(response.data);
        productForm.value.categoria_id = response.data.id;
        showCategoriaModal.value = false;
        nuevaCategoria.value = { nombre: '', descripcion: '', estado: 'activo' };
        Swal.fire({
          icon: 'success',
          title: 'Categoría Guardada',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
        });
      }
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al guardar categoría: ' + (error.response?.data?.message || error.message),
        confirmButtonColor: '#EF4444',
      });
    } finally {
      savingCategoria.value = false;
    }
  };

  const saveMarca = async () => {
    if (!nuevaMarca.value.nombre.trim()) return;
    savingMarca.value = true;
    try {
      const response = await axios.post('/api/marcas', nuevaMarca.value);
      if (response.data && response.data.id) {
        const marca = response.data;
        catalogos.value.marcas.push(marca);
        productForm.value.marca_id = marca.id;
        showMarcaModal.value = false;
        nuevaMarca.value = { nombre: '', descripcion: '', estado: 'activo' };
      }
    } catch (error) {
      console.error(error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al guardar marca: ' + (error.response?.data?.message || error.message),
        confirmButtonColor: '#EF4444',
      });
    } finally {
      savingMarca.value = false;
    }
  };

  const addSerial = () => {
    const serial = currentSerial.value.trim();
    if (!serial) return;

    if (productSerials.value.includes(serial)) {
      Swal.fire({
        icon: 'warning',
        title: 'Serie Duplicada',
        text: 'Este número de serie ya fue agregado.',
        toast: true,
        position: 'top-end',
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    }

    if (productSerials.value.length >= productForm.value.stock) {
      Swal.fire({
        icon: 'info',
        title: 'Completo',
        text: 'Ya se capturaron todas las series necesarias.',
        toast: true,
        position: 'top-end',
        timer: 3000,
        showConfirmButton: false,
      });
      return;
    }

    productSerials.value.push(serial);
    currentSerial.value = '';

    if (serialInput.value) {
      serialInput.value.focus();
    }
  };

  const removeSerial = (index) => {
    productSerials.value.splice(index, 1);
  };

  const fetchCatalogos = async () => {
    try {
      const ajaxHeaders = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      };

      const [catRes, marcaRes] = await Promise.all([
        axios.get('/categorias', { headers: ajaxHeaders }),
        axios.get('/marcas', { headers: ajaxHeaders }),
      ]);

      const extractData = (res) => {
        if (Array.isArray(res.data)) return res.data;
        if (res.data && Array.isArray(res.data.data)) return res.data.data;
        return res.data || [];
      };

      catalogos.value = {
        categorias: extractData(catRes),
        marcas: extractData(marcaRes),
        unidades: [],
      };
      catalogosLoaded.value = true;
      console.log('Catálogos cargados:', catalogos.value);
    } catch (error) {
      console.error('Error cargando catálogos:', error);
    }
  };

  const openProductModal = (concepto, index, isBulk = false) => {
    currentConcept.value = concepto;
    currentConceptIndex.value = index;
    isBulkProductModal.value = isBulk;

    const pareceNumeroSerie = (val) => /^(\d{4,8}-\d{1,4}-\d{3,6})$/.test(val || '');
    const noIdent = (concepto.no_identificacion || '').trim();
    const esNumeroDeSerie = pareceNumeroSerie(noIdent);

    productForm.value = {
      nombre: concepto.descripcion,
      codigo: noIdent || '',
      codigo_barras: esNumeroDeSerie ? 'GEN-' + Date.now() : (noIdent || 'GEN-' + Date.now()),
      categoria_id: catalogos.value.categorias.length > 0 ? catalogos.value.categorias[0]?.id : '',
      marca_id: catalogos.value.marcas.length > 0 ? catalogos.value.marcas[0]?.id : '',
      precio_compra: Math.round((concepto.valor_unitario || 0) * 100) / 100,
      precio_venta: Math.round(((concepto.valor_unitario || 0) * 1.3) * 100) / 100,
      unidad_medida: concepto.unidad || concepto.clave_unidad || 'PZA',
      requiere_serie: esNumeroDeSerie,
      tipo_producto: 'fisico',
      estado: 'activo',
      sat_clave_prod_serv: concepto.clave_prod_serv || '',
      sat_clave_unidad: concepto.clave_unidad || '',
      sat_objeto_imp: '02',
      stock: parseInt(concepto.cantidad) || 1,
      descripcion: concepto.descripcion,
    };

    if (esNumeroDeSerie && noIdent) {
      productSerials.value = [noIdent];
    } else {
      productSerials.value = [];
    }
    currentSerial.value = '';

    showProductModal.value = true;

    if (!catalogosLoaded.value) {
      fetchCatalogos();
    }
  };

  const saveProduct = async () => {
    if (!productForm.value.nombre || !productForm.value.precio_compra) {
      Swal.fire({
        icon: 'warning',
        title: 'Campos vacíos',
        text: 'Por favor complete los campos obligatorios',
        confirmButtonColor: '#10B981',
      });
      return;
    }

    if (productForm.value.requiere_serie && productSerials.value.length < productForm.value.stock) {
      Swal.fire({
        icon: 'warning',
        title: 'Series incompletas',
        text: `Debe capturar las ${productForm.value.stock} series.`,
        confirmButtonColor: '#10B981',
      });
      return;
    }

    savingProduct.value = true;
    try {
      const payload = {
        ...productForm.value,
        seriales: productSerials.value,
      };

      const response = await axios.post('/productos', payload);
      const newProduct = response.data.producto || response.data;

      if (isBulkProductModal.value) {
        await fetchSelectedProducts();
      } else {
        if (cfdiData.value && cfdiData.value.conceptos[currentConceptIndex.value]) {
          const c = cfdiData.value.conceptos[currentConceptIndex.value];
          c.producto_id = newProduct.id;
          c.producto_nombre = newProduct.nombre;
          c.match_type = 'exact';
          if (newProduct.requiere_serie) {
            c.requiere_serie = true;
            c.seriales = [...productSerials.value];
          }
        }
      }

      showProductModal.value = false;

      Swal.fire({
        icon: 'success',
        title: 'Producto Guardado',
        text: 'El producto se ha guardado correctamente.',
        toast: true,
        position: 'top-end',
        timer: 3000,
        showConfirmButton: false,
      });
    } catch (error) {
      console.error('Error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error al guardar',
        text: 'Error al guardar producto: ' + (error.response?.data?.message || error.message),
        confirmButtonColor: '#EF4444',
      });
    } finally {
      savingProduct.value = false;
    }
  };

  return {
    showProductModal,
    savingProduct,
    catalogosLoaded,
    catalogos,
    currentConceptIndex,
    currentConcept,
    showCategoriaModal,
    savingCategoria,
    nuevaCategoria,
    saveCategoria,
    showMarcaModal,
    savingMarca,
    nuevaMarca,
    saveMarca,
    productForm,
    productSerials,
    currentSerial,
    serialInput,
    addSerial,
    removeSerial,
    openProductModal,
    fetchCatalogos,
    saveProduct,
  };
}
