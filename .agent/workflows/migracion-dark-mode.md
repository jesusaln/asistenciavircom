---
description: Plan de Migración a Modo Oscuro (Dark Premium)
---

# 🌓 Plan de Migración a Modo Oscuro

Este flujo de trabajo permite rastrear la implementación del modo oscuro en todos los componentes de la aplicación, utilizando el switch existente y las clases de Tailwind CSS.

## 🛠️ Cómo migrar un componente

Para cada componente `.vue`, sigue estos pasos:

1. **Fondos**: Usa `dark:bg-slate-950` para el fondo principal o `dark:bg-slate-900` para superficies (cards, modales).
2. **Textos**: Usa `dark:text-slate-100` para títulos y `dark:text-slate-400` para textos secundarios.
3. **Bordes**: Usa `dark:border-slate-800`.
4. **Estados**: Asegúrate de que los hovers usen `dark:hover:bg-slate-800`.
5. **Variables**: Puedes usar las variables CSS unificadas:
   - `var(--empresa-bg-primary)`
   - `var(--empresa-text-primary)`
   - `var(--empresa-border-color)`

---

## 📋 Checklist de Migración

### 🏗️ Layouts (Estructural)
- [x] `resources/js/Layouts/AppLayout.vue` (Ya tiene soporte base)
- [ ] `resources/js/Layouts/GuestLayout.vue` (⚠️ No encontrado, verificar necesidad)

### 📄 Páginas Principales (Pages)
- [x] `resources/js/Pages/Panel.vue` (Dashboard Principal - En progreso)
- [x] `resources/js/Pages/Clientes/Index.vue` (Migrado a Slate)
- [x] `resources/js/Pages/Clientes/Show.vue` (Actualizado con paleta Slate)
- [x] `resources/js/Pages/Ventas/Index.vue` (Migrado a Slate)
- [x] `resources/js/Pages/Ventas/Create.vue` (Migrado a Slate)
- [x] `resources/js/Pages/Soporte/Dashboard.vue` (Migrado a Slate)
- [ ] `resources/js/Pages/Soporte/Tickets/Index.vue`
- [ ] `resources/js/Pages/Pedidos/Index.vue`
- [ ] `resources/js/Pages/Reportes/Index.vue`
- [ ] `resources/js/Pages/Admin/Users/Index.vue`

### 🧩 Componentes Reutilizables (Common UI)
- [x] `resources/js/Components/UI/DataTable.vue` (Soporte básico implementado)
- [x] `resources/js/Components/UI/StatCard.vue` (Soporte básico implementado)
- [x] `resources/js/Components/UI/PageHeader.vue` (Soporte básico implementado)
- [x] `resources/js/Components/Modal.vue` (Limpieza de clases duplicadas)
- [x] `resources/js/Components/TextInput.vue` (Verificado: Paleta Slate Correcta)
- [x] `resources/js/Components/InputLabel.vue` (Actualizado a Slate)
- [x] `resources/js/Components/PrimaryButton.vue` (Verificado)
- [x] `resources/js/Components/SecondaryButton.vue` (Corregido bg duplicado)

---

## 🚀 Próximos Pasos (Pendientes de iniciar)
Selecciona un componente de la lista anterior para comenzar la migración. Se recomienda continuar con `resources/js/Pages/Clientes/Show.vue` o `resources/js/Components/Modal.vue`.
