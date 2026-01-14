# Plan de Actualización: Laravel 11 (Estable) & Frontend Stack 2025

Este documento detalla el proceso y el estado final de la actualización masiva del sistema realizada en enero de 2026.

## 🚀 Estado Final Alcanzado

| Componente | Versión Anterior | Versión Nueva | Notas |
| :--- | :--- | :--- | :--- |
| **Laravel** | 11.x (Initial) | **11.47.0** | Se decidió estabilizar en v11 para garantizar compatibilidad de paquetes de terceros. |
| **Vue.js** | 3.3.13 | **3.5.26** | Versión estable con mejoras en rendimiento. |
| **Inertia.js** | 1.0.14 | **2.0.18** | Habilitado el prefetching y nueva barra de progreso Ámbar. |
| **Tailwind CSS** | 3.4.19 | **4.1.18** | Migración completa al motor de alto rendimiento v4. |
| **Vite** | 6.0.11 | **7.3.0** | Uso del plugin nativo `@tailwindcss/vite`. |
| **Node.js** | 18.19.1 | **22.21.1 (LTS)** | Actualizado vía NVM. |

---

## 🛠️ Cambios Realizados

### 1. Infraestructura Frontend (Vite 7 + Tailwind 4)
- **Plugin Nativo:** Se eliminó la dependencia de PostCSS para Tailwind migrando a `@tailwindcss/vite`.
- **CSS Avanzado:** Reemplazo de directivas de pre-procesamiento por CSS moderno nativo en `app.css`.
- **Micro-ajustes de Compatibilidad:** Inyección automática de `@import "tailwindcss" reference;` en todos los componentes Vue que usan bloques `<style>` para permitir el uso de `@apply` con el nuevo motor de Tailwind 4.

### 2. Capa de Comunicación (Inertia 2.0)
- **Prefetching:** Habilitada la funcionalidad de pre-carga para una navegación instantánea.
- **Estética:** Actualización del color de la barra de progreso a Ámbar (#F59E0B).
- **$can global:** Optimización de la función global `$can` para usar el nuevo sistema de acceso a propiedades de Inertia 2.

### 3. Backend (Estabilización en Laravel 11)
- Tras intentar la migración a Laravel 12, se detectaron errores de inicialización en el motor de consola (`artisan`) relacionados con la madurez de los paquetes de terceros (ej. `inertia-laravel`, `l5-swagger`).
- Se restauró la estructura de Laravel 11 para asegurar que el sistema sea 100% fiable en producción.

---

## ⚠️ Notas Técnicas para Desarrolladores

### Activación del Entorno
Si trabajas en terminal, asegúrate de activar Node 22 (Gestionado por NVM):
```bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
nvm use 22
```

### Comandos de Compilación
- **Desarrollo:** `npm run dev`
- **Producción:** `npm run build`

### Próximos Pasos Recomendados (Q2 2026)
1. Evaluar de nuevo el salto a Laravel 12 una vez que `inertiajs/inertia-laravel` lance soporte oficial para la v12.
2. Migrar paulatinamente el uso de `@apply` en componentes Vue hacia utilidades directas en el template.

---
**Fecha de finalización:** 03 de enero de 2026.
**Estado:** ✅ Funcional y estable.
