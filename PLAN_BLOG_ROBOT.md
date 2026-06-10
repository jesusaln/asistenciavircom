# Plan de Implementación: Blog Robot & Automatización

Este documento detalla las fases para convertir el blog de **Asistencia Vircom** en una plataforma automatizada y de alta autoridad.

---

## 🚀 Fase 1: Infraestructura y Experiencia Premium (Completado)
*   [x] **Diseño Premium**: Implementación de búsqueda, filtros por categoría y diseño minimalista.
*   [x] **Experiencia de Lectura**: Tabla de contenidos (TOC) dinámica que sigue el scroll.
*   [x] **Tutoriales Técnicos**: Resaltado de sintaxis de código (Syntax Highlighting) para comandos y scripts.
*   [x] **Engagement**: Barra de progreso de lectura y botones flotantes para compartir en redes.

## 🛡️ Fase 2: Gestión de Contenidos y Seguridad (Completado)
*   [x] **Control de Imágenes**: Opción para subir fotos directamente al servidor (evita enlaces rotos).
*   [x] **API Corporativa**: Creación de endpoint seguro (`/api/blog/robot/draft`) para recibir datos externos.
*   [x] **Seguridad**: Sistema de autenticación vía Bearer Token configurable desde el panel de administración.
*   [x] **Panel de Control**: Nueva pestaña en Configuración de Empresa para gestionar el robot.

## 🤖 Fase 3: Automatización con n8n (En Proceso)
*   [x] **Generación de Token**: Token único generado para el robot.
*   [x] **Workflow Template**: Creación del archivo `n8n_blog_workflow.json` listo para importar.
*   [ ] **Importación en n8n**: (Acción del Usuario) Importar el JSON en su instancia de n8n.
*   [ ] **Prueba de Conexión**: Envío del primer borrador automático desde el robot.

## 🧠 Fase 4: Inteligencia Artificial Avanzada (Siguiente)
*   [ ] **Resumen Automático**: Configurar el robot para que lea una URL externa, la resuma con IA y la envíe al blog.
*   [ ] **Reescritura de Marca**: Ajustar el "tono de voz" del robot para que siempre suene como un experto de Vircom.
*   [ ] **Generación de Imágenes**: Integración del generador de imágenes mediante IA para portadas automáticas.

---

## 🛠️ Instrucciones de Uso Inmediato

### Para recibir noticias externas:
1.  Asegúrate que el **Robot de Blog** esté habilitado en el panel de configuración.
2.  Importa el archivo `n8n_blog_workflow.json` en tu n8n.
3.  Tu robot enviará noticias como **Borradores** (Drafts).
4.  Revisa los borradores en `Gestionar Blog` y dales clic en **Publicar** cuando estés listo.
