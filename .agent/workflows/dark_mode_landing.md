---
description: Plan de implementación de Modo Oscuro Premium para la Landing Page
---

# Plan de Implementación Dark Mode Premium

El objetivo es permitir que la Landing Page se adapte automáticamente al tema del sistema operativo del usuario.
- **Modo Claro (Default)**: Diseño limpio y profesional.
- **Modo Oscuro**: Diseño "Dark Premium" con efectos de neón, glassmorphism y fondos cinemáticos.

## Fases de Implementación

### Fase 1: Estructura Principal y Secciones Hero/Stats [COMPLETADO]
- [x] Habilitar clases `dark:` en el contenedor raíz.
- [x] Adaptar **Hero Section** (textos, fondos, botones).
- [x] Adaptar **Stats Section** (fondo dual, tarjetas duales).
- [x] Verificar consistencia en **Pólizas Section** (botones y textos internos).

### Fase 2: Secciones de Contenido [COMPLETADO]
- [x] **Process Section**: Adaptar fondos grises a oscuros, iconos y textos.
- [x] **Testimonios**: Adaptar carrusel, tarjetas y textos de clientes.
- [x] **Simulador y Formularios**: Asegurar legibilidad en inputs y etiquetas.
- [x] **FAQ**: Acordeones oscuros.

### Fase 3: Componentes Globales [COMPLETADO]
- [x] **PublicNavbar**: Menú adaptable, logo y dropdowns implementados.
- [x] **PublicFooter**: Ya era oscuro, compatible por defecto.
- [ ] **Modales y Alertas**: Verificar que no queden textos oscuros sobre fondo oscuro.

### Fase 4: Refinamiento y QA [PENDIENTE]
- [ ] Verificar brillos y sombras en ambos modos.
- [ ] Asegurar que las transiciones de color (`duration-300`) sean suaves.
- [ ] Pruebas en navegador con toggle de sistema.

## Notas Técnicas
- Se utiliza la estrategia `media` (auto-detect) de Tailwind por defecto.
- Las secciones "Premium" (Stats, Pólizas) ocultan sus efectos de luz en modo claro (`hidden dark:block`) para mantener la limpieza.
