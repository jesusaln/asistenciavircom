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
- [ ] `resources/js/Layouts/GuestLayout.vue`

### 📄 Páginas Principales (Pages)
- [ ] `resources/js/Pages/Dashboard.vue`
- [ ] `resources/js/Pages/Clientes/Index.vue`
- [ ] `resources/js/Pages/Clientes/Show.vue`
- [ ] `resources/js/Pages/Ventas/Index.vue`
- [ ] `resources/js/Pages/Ventas/Create.vue`
- [ ] `resources/js/Pages/Soporte/Dashboard.vue`
- [ ] `resources/js/Pages/Soporte/Tickets/Index.vue`
- [ ] `resources/js/Pages/Pedidos/Index.vue`
- [ ] `resources/js/Pages/Reportes/Index.vue`
- [ ] `resources/js/Pages/Admin/Users/Index.vue`

### 🧩 Componentes Reutilizables (Common UI)
- [ ] `resources/js/Components/UI/DataTable.vue`
- [ ] `resources/js/Components/UI/PageHeader.vue`
- [ ] `resources/js/Components/UI/StatCard.vue`
- [ ] `resources/js/Components/Modal.vue`
- [ ] `resources/js/Components/TextInput.vue`
- [ ] `resources/js/Components/InputLabel.vue`
- [ ] `resources/js/Components/PrimaryButton.vue`
- [ ] `resources/js/Components/SecondaryButton.vue`

---

## 🚀 Próximos Pasos (Pendientes de iniciar)
Selecciona un componente de la lista anterior para comenzar la migración. Se recomienda empezar por los componentes de UI (`DataTable`, `StatCard`) ya que impactan en muchas páginas a la vez.
