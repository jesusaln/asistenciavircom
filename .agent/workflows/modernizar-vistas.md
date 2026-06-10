# Modernización de Vistas con Colores de Empresa

## Objetivo
Estandarizar todas las vistas del sistema para usar los colores configurados en **EmpresaConfiguracion** (`color_principal` y `color_secundario`), manteniendo uniformidad visual en todo el sistema.

---

## Composable Disponible

### `useCompanyColors.js`
**Ubicación:** `/resources/js/Composables/useCompanyColors.js`

```javascript
import { useCompanyColors } from '@/Composables/useCompanyColors'

const { colors, cssVars, primaryButtonStyle, headerGradientStyle } = useCompanyColors()
```

**Propiedades disponibles:**
| Propiedad | Descripción |
|-----------|-------------|
| `colors.principal` | Color principal (hex) |
| `colors.secundario` | Color secundario (hex) |
| `cssVars` | CSS variables para inyectar en componente |
| `primaryButtonStyle` | Estilo para botones primarios |
| `headerGradientStyle` | Gradiente para headers |

---

## Patrón de Implementación

### 1. Importar composable
```vue
<script setup>
import { useCompanyColors } from '@/Composables/useCompanyColors'
const { colors, cssVars } = useCompanyColors()
</script>
```

### 2. Aplicar CSS variables al contenedor principal
```vue
<template>
  <div :style="cssVars">
    <!-- contenido -->
  </div>
</template>
```

### 3. Usar colores dinámicos
```vue
<!-- Gradiente de header -->
<div :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">

<!-- Botón primario -->
<button :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">

<!-- Badge con color de empresa -->
<span :style="{ backgroundColor: `${colors.principal}15`, color: colors.principal }">

<!-- Barra de progreso -->
<div :style="{ background: `linear-gradient(90deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
```

---

## Módulos Completados

| Módulo | Vistas | Estado |
|--------|--------|--------|
| **Clientes** | Index, Create, Edit, Show, ClientesHeader | ✅ Completado |
| **Ventas** | Index, VentasHeader, Show | ✅ Completado |
| **Cotizaciones** | Index, CotizacionesHeader, Show | ✅ Completado |
| **Pedidos** | Index, PedidosHeader, Show | ✅ Completado |
| **Productos** | Index, ProductosHeader | ✅ Completado |
| **Compras** | Index, ComprasHeader | ✅ Completado |
| **Proveedores** | Index, ProveedoresHeader | ✅ Completado |
| **Ordenes de Compra** | Index, OrdenesCompraHeader | ✅ Completado |
| **Usuarios** | Index, UsuariosHeader | ✅ Completado |
| **Cobranza** | Index, CobranzaHeader | ✅ Completado |
| **Almacenes** | Index, AlmacenesHeader | ✅ Completado |
| **Rentas** | Index, RentasHeader | ✅ Completado |
| **Servicios** | Index, ServiciosHeader | ✅ Completado |
| **Citas** | Index, CitasHeader | ✅ Completado |
| **Equipos** | Index, EquiposHeader | ✅ Completado |
| **Bitacora** | Index, BitacoraHeader | ✅ Completado |
| **Tecnicos** | Index, TecnicosHeader | ✅ Completado |
| **Entregas Dinero** | Index, EntregasDineroHeader | ✅ Completado |

---

## Notas de Diseño

### Elementos modernizados por vista:
1. **Header** - Gradiente dinámico con icono y título
2. **Botones primarios** - Gradiente + shadow + hover animation
3. **Contenedores** - Glassmorphism (`bg-white/80 backdrop-blur-sm`)
4. **Tarjetas estadísticas** - Bordes redondeados + sombras + backdrop-blur
5. **Badges/Tags** - Colores de empresa con opacidad
6. **Loading spinners** - Color de empresa
