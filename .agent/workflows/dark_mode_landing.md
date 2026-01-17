---
description: Plan de implementación de Modo Oscuro Premium para la Landing Page
---

# Plan de Implementación: Dark Mode Completo

Este plan implementa el modo oscuro en todas las páginas públicas de la aplicación, respetando la preferencia del sistema del usuario y permitiendo cambio manual.

## Prerequisitos
- [x] Tailwind configurado con `darkMode: 'class'`
- [x] Toggle en PublicNavbar funcionando
- [x] Persistencia en localStorage

---

## Fase 1: Páginas Públicas Core
**Prioridad: ALTA** | **Tiempo estimado: 30 min**

### 1.1 Landing Page (Index.vue)
- [ ] Fondo principal: `bg-white dark:bg-gray-900`
- [ ] Textos principales: `text-gray-900 dark:text-white`
- [ ] Textos secundarios: `text-gray-500 dark:text-gray-400`
- [ ] Cards/Secciones: `bg-gray-50 dark:bg-gray-800`
- [ ] Bordes: `border-gray-200 dark:border-gray-700`

### 1.2 Catálogo de Productos (Catalogo/Index.vue)
- [ ] Aplicar mismas clases base
- [ ] Cards de productos con soporte dark
- [ ] Filtros y sidebar

### 1.3 Catálogo de Pólizas (Catalogo/Polizas.vue)
- [ ] Cards de pólizas
- [ ] Tablas de precios

### 1.4 Página de Contacto (Public/Contacto.vue)
- [ ] Formulario de contacto
- [ ] Mapa/Info de empresa

---

## Fase 2: Autenticación
**Prioridad: ALTA** | **Tiempo estimado: 20 min**

### 2.1 Login de Clientes (Portal/Auth/Login.vue)
- [ ] Fondo y card central
- [ ] Inputs y labels
- [ ] Botones

### 2.2 Registro de Clientes (Portal/Auth/Register.vue)
- [ ] Mismo tratamiento que login

### 2.3 Login Staff (/login - Auth/Login.vue)
- [ ] Página de acceso administrativo

### 2.4 Recuperar Contraseña
- [ ] Páginas de forgot/reset password

---

## Fase 3: Portal del Cliente
**Prioridad: MEDIA** | **Tiempo estimado: 45 min**

### 3.1 Layout del Portal (ClientLayout.vue)
- [ ] Navbar del portal
- [ ] Sidebar/Menú
- [ ] Footer

### 3.2 Dashboard del Cliente
- [ ] Cards de resumen
- [ ] Tablas de tickets/pagos
- [ ] Modales

### 3.3 Páginas internas del portal
- [ ] Tickets, Pólizas, Equipos, Credenciales, etc.

---

## Fase 4: Componentes Compartidos
**Prioridad: MEDIA** | **Tiempo estimado: 30 min**

### 4.1 PublicFooter.vue
- [ ] Fondo oscuro consistente
- [ ] Links y textos

### 4.2 Modales globales
- [ ] DialogModal, ConfirmationModal, etc.

### 4.3 Formularios
- [ ] TextInput, Select, Checkbox con variantes dark

### 4.4 Botones
- [ ] PrimaryButton, SecondaryButton, DangerButton

---

## Fase 5: Carrito y Checkout
**Prioridad: BAJA** | **Tiempo estimado: 25 min**

### 5.1 Carrito (Catalogo/Carrito.vue)
- [ ] Lista de items
- [ ] Resumen de compra

### 5.2 Checkout
- [ ] Formulario de datos
- [ ] Confirmación

---

## Notas Técnicas

### Clases Base a Usar
```css
/* Fondos */
bg-white dark:bg-gray-900
bg-gray-50 dark:bg-gray-800

/* Textos */
text-gray-900 dark:text-white
text-gray-600 dark:text-gray-300
text-gray-500 dark:text-gray-400

/* Bordes */
border-gray-200 dark:border-gray-700
border-gray-100 dark:border-gray-800

/* Inputs */
bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white

/* Shadows */
shadow-gray-200/50 dark:shadow-black/20
```

### Verificación
Después de cada fase:
1. Probar toggle en navbar
2. Verificar persistencia al recargar
3. Verificar respeto a preferencia del sistema (primera visita)

---

## Comandos de Despliegue

// turbo
1. Commit cambios:
```bash
git add -A && git commit -m "feat(ui): Fase X - Dark mode en [páginas]"
```

// turbo
2. Push y deploy:
```bash
git push origin main && ssh root@191.101.233.82 "cd /root/asistenciavircom && git pull && docker exec asistenciavircom-app-1 npm run build"
```
