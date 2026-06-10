# Plan de Mejoras para la Integración de Módulos (Visión 360)

Este documento describe el plan para implementar una integración y sincronización completa entre los módulos de Clientes, Pólizas, Tickets y Citas. El objetivo es crear un sistema proactivo, reducir la carga de trabajo manual y mejorar la visibilidad de la información para agentes y clientes.

---

## Fase 1: Cimientos y Hub Central del Cliente

**Objetivo:** Establecer las bases técnicas y crear la vista centralizada de información del cliente.

-   [ ] **1.1. Crear Controlador del Hub:**
    -   Crear `app/Http/Controllers/ClienteHubController.php`.
    -   Implementar un método `show(Cliente $cliente)` que cargue eficientemente las relaciones necesarias:
        -   `polizas()->activa()`
        -   `tickets()->latest()->take(10)` con sus relaciones (`asignado`, `categoria`).
        -   `citas()->proximas()->take(5)` con su relación (`tecnico`).
        -   `polizas.consumosMesActual()`

-   [ ] **1.2. Ruta del Hub:**
    -   Añadir una nueva ruta en `routes/admin.php`: `Route::get('/clientes/{cliente}/hub', [ClienteHubController::class, 'show'])->name('clientes.hub');`

-   [ ] **1.3. Vista Inicial del Hub (Inertia/Vue):**
    -   Crear el componente `resources/js/Pages/Clientes/Hub.vue`.
    -   Diseñar una estructura básica con pestañas: "Resumen", "Pólizas", "Tickets", "Citas".
    -   Mostrar la información básica cargada por el controlador. El diseño detallado se refinará en la Fase 4.

-   [ ] **1.4. Botón de Acceso:**
    -   En la tabla de listado de clientes, añadir un botón/enlace que dirija a la ruta `clientes.hub`.

---

## Fase 2: Automatización Principal (Eventos y Notificaciones)

**Objetivo:** Implementar los flujos de trabajo automatizados más críticos usando el sistema de eventos de Laravel.

-   [ ] **2.1. Evento `TicketCreado`:**
    -   Crear el evento: `php artisan make:event TicketCreado`.
    -   Despachar este evento desde `Ticket::boot()` o el controlador correspondiente cuando un ticket es creado.

-   [ ] **2.2. Listener de `TicketCreado`: Asignación Inteligente:**
    -   Crear el listener: `php artisan make:listener AsignacionInteligenteListener --event=TicketCreado`.
    -   Implementar la lógica para auto-asignar el ticket.
        -   *Lógica v1 (simple):* Asignar a un agente predeterminado o dejarlo sin asignar para revisión manual.
        -   *Lógica v2 (avanzada):* Buscar si el cliente tiene un técnico preferido o asignar al agente con menos tickets abiertos.

-   [ ] **2.3. Listener de `TicketCreado`: Notificación al Cliente:**
    -   Crear el listener: `php artisan make:listener NotificarClienteTicketCreado --event=TicketCreado`.
    -   Crear la notificación: `php artisan make:notification TicketRecibido`.
    -   Implementar el envío de un email al cliente con el folio del ticket y un mensaje de confirmación.

-   [ ] **2.4. Evento `CitaCompletada`:**
    -   Crear el evento: `php artisan make:event CitaCompletada`.
    -   Despachar este evento desde `CitaController` o el modelo `Cita` cuando su estado cambia a `completado`.

-   [ ] **2.5. Listener de `CitaCompletada`: Actualizar Ticket Origen:**
    -   Crear el listener: `php artisan make:listener ActualizarTicketDesdeCita --event=CitaCompletada`.
    -   Implementar la lógica: si la cita tiene un `ticket_id`, añadir un comentario privado al ticket (`"Visita completada el día X. Resumen: ..."`). Opcionalmente, cambiar el estado del ticket.

---

## Fase 3: Funcionalidades Proactivas

**Objetivo:** Construir herramientas que permitan a los agentes actuar de forma más eficiente y anticiparse a las necesidades del cliente.

-   [ ] **3.1. Botón "Agendar Cita" desde Ticket:**
    -   En la vista del `Ticket`, añadir un botón "Agendar Cita".
    -   El botón debe redirigir al formulario de creación de `Cita`.
    -   Pasar en la URL los parámetros necesarios (`cliente_id`, `ticket_id`, `descripcion`) para pre-rellenar el formulario automáticamente.

-   [ ] **3.2. Programador de Tareas para Pólizas:**
    -   Crear un nuevo comando de Artisan: `php artisan make:command Polizas:VerificarVencimientos`.
    -   En el comando, buscar pólizas que estén a 30, 15 o 7 días de vencer.
    -   Registrar el comando en `app/Console/Kernel.php` para que se ejecute diariamente.

-   [ ] **3.3. Evento `PolizaProximaAVencer`:**
    -   Crear el evento: `php artisan make:event PolizaProximaAVencer`.
    -   Despachar este evento desde el comando anterior para cada póliza encontrada.

-   [ ] **3.4. Listener de `PolizaProximaAVencer`: Crear Ticket de Renovación:**
    -   Crear el listener: `php artisan make:listener CrearTicketRenovacion --event=PolizaProximaAVencer`.
    -   Implementar la lógica para crear un nuevo `Ticket` con:
        -   **Categoría:** "Renovación / Ventas".
        -   **Asignado a:** Equipo de ventas.
        -   **Título:** "Seguimiento de renovación Póliza #FOLIO".
        -   **Prioridad:** Baja.

---

## Fase 4: Portales y Experiencia de Usuario (UI/UX)

**Objetivo:** Pulir las interfaces y desarrollar los portales para maximizar la usabilidad y el autoservicio.

-   [ ] **4.1. Refinamiento del Hub del Cliente:**
    -   Mejorar el diseño de `Hub.vue` para que sea visualmente claro e interactivo.
    -   Usar componentes gráficos (badges, barras de progreso) para mostrar estados y consumos.
    -   Añadir filtros y paginación a las listas de tickets y citas.

-   [ ] **4.2. Dashboard del Agente:**
    -   Modificar la página de inicio (`Dashboard.vue`) para que los usuarios con rol "agente" o "tecnico" vean:
        -   Un widget con "Mis 5 tickets más urgentes".
        -   Un widget con "Mis próximas 3 citas".
        -   Alertas visuales para tickets vencidos.

-   [ ] **4.3. Diseño del Portal del Cliente:**
    -   Crear un nuevo conjunto de rutas y controladores para el portal de clientes (fuera del `admin.php`).
    -   Diseñar las vistas para que los clientes puedan ver el estado de sus tickets y pólizas.

-   [ ] **4.4. Implementación del Portal del Cliente:**
    -   Asegurar la autenticación para clientes (`Cliente` model).
    -   Construir la lógica para que los clientes puedan crear nuevos tickets desde su portal.
    -   Mostrar la información de consumo de sus pólizas.
