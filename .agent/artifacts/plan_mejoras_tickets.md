# 📋 Plan de Mejoras: Control de Horas y Reportes de Soporte

## 📅 Fecha: 16 de Enero 2026
## 🎯 Objetivo: Implementar sistema completo de tracking de horas de trabajo

---

## ✅ Cambios Ya Implementados

### 1. Filtrado de Tickets (Ocultar Cerrados)
- [x] Por defecto los tickets cerrados NO aparecen en la lista
- [x] Checkbox "Incluir cerrados" para verlos cuando se necesite
- [x] Nuevo orden de prioridad operativa:
  1. Sin asignar (requieren atención inmediata)
  2. Pendientes
  3. En progreso  
  4. Abiertos
  5. Cerrados (solo si se selecciona incluirlos)
- [x] Estadística de tickets cerrados en el dashboard

### 2. Registro Obligatorio de Horas al Resolver/Cerrar
- [x] Modal obligatorio al cambiar estado a "resuelto" o "cerrado"
- [x] Campo de horas trabajadas requerido (mínimo 0.25h = 15 min)
- [x] Se muestra si está vinculado a póliza para descontar horas
- [x] Las horas se guardan en el campo `horas_trabajadas` del ticket

### 3. Dashboard con Estadísticas de Horas
- [x] Horas trabajadas por técnico (últimos 30 días)
- [x] Consumo de horas por póliza (mes actual)
- [x] Alerta visual cuando una póliza excede las horas contratadas
- [x] Promedio de horas por ticket

### 4. Reportes PDF
- [x] **Reporte de Consumo de Póliza** - Para mostrar al cliente
- [x] **Reporte de Horas por Técnico** - Para evaluación interna

---

## 🚧 Pendiente de Implementar

### 5. Hora Inicio y Hora Fin del Servicio
> *Solicitud: "que pusieran de las 8 am a las 12 pm para poder saber en qué horas hizo ese trabajo"*

**Cambios requeridos:**

#### Base de Datos
```php
// Nueva migración
Schema::table('tickets', function (Blueprint $table) {
    $table->timestamp('servicio_inicio_at')->nullable();  // Hora inicio del trabajo
    $table->timestamp('servicio_fin_at')->nullable();     // Hora fin del trabajo
});
```

#### Modelo Ticket
```php
protected $fillable = [
    // ... campos existentes
    'servicio_inicio_at',
    'servicio_fin_at',
];

protected $casts = [
    // ... casts existentes
    'servicio_inicio_at' => 'datetime',
    'servicio_fin_at' => 'datetime',
];

// Accessor para calcular duración automáticamente
public function getDuracionServicioAttribute(): ?float
{
    if ($this->servicio_inicio_at && $this->servicio_fin_at) {
        return $this->servicio_inicio_at->diffInMinutes($this->servicio_fin_at) / 60;
    }
    return null;
}
```

#### Vista del Ticket (Show.vue)
- Agregar inputs de hora inicio y hora fin en el modal de cierre
- Calcular automáticamente las horas trabajadas basado en las horas ingresadas
- Mostrar en los detalles del ticket:
  - ⏰ Inicio: 8:00 AM
  - ⏰ Fin: 12:00 PM  
  - ⏱️ Duración: 4.0 horas

### 6. Vista de Consumo en Portal del Cliente
> *Solicitud: "que también se muestre en el portal del cliente para que sepa cuánto tiempo duró el servicio"*

**Ubicación:** Portal del Cliente > Sección de Tickets

**Información a mostrar por cada ticket:**
- Número de ticket y título
- Fecha del servicio
- Hora inicio → Hora fin
- Duración total
- Si tiene póliza: Horas consumidas de la póliza
- Si NO tiene póliza: Horas a cobrar

**Resumen en Dashboard del Cliente:**
- Total de horas consumidas del mes
- Horas incluidas en póliza vs consumidas
- Barra de progreso visual
- Alerta si está cerca del límite o lo excedió

### 7. Rutas para Reportes PDF

```php
// routes/web.php o routes/admin.php
Route::prefix('reportes/soporte')->group(function () {
    Route::get('/consumo-poliza/{poliza}', [ReporteSoporteController::class, 'consumoPoliza'])
        ->name('reportes.soporte.consumo-poliza');
    
    Route::get('/horas-tecnico/{usuario?}', [ReporteSoporteController::class, 'horasTecnico'])
        ->name('reportes.soporte.horas-tecnico');
});
```

---

## 🔧 Implementación Sugerida (Orden de Prioridad)

### Fase 1: Completar lo Actual (HOY)
1. ✅ Agregar rutas de reportes PDF
2. ✅ Agregar botones para generar reportes en el Dashboard de Soporte
3. 🔄 Desplegar cambios al VPS

### Fase 2: Hora Inicio/Fin (PRÓXIMA SESIÓN)
1. Crear migración para nuevos campos
2. Actualizar modelo Ticket
3. Modificar modal de cierre para incluir hora inicio/fin
4. Actualizar vista de detalle de ticket
5. Actualizar reportes PDF

### Fase 3: Portal del Cliente (PRÓXIMA SESIÓN)
1. Agregar sección de consumo de horas en Dashboard
2. Mostrar detalles de hora en cada ticket
3. Agregar botón para que el cliente vea su reporte de consumo

---

## 📊 Flujo Propuesto de Cierre de Ticket

```
┌─────────────────────────────────────────────────────────────┐
│  TÉCNICO MARCA TICKET COMO "RESUELTO" O "CERRADO"          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  MODAL: REGISTRO DE TIEMPO DE SERVICIO                     │
│  ┌─────────────────────────────────────────────────────────┐│
│  │  📅 Fecha del Servicio: [16/01/2026]                   ││
│  │                                                         ││
│  │  ⏰ Hora Inicio: [08:00] AM                             ││
│  │  ⏰ Hora Fin:    [12:00] PM                             ││
│  │                                                         ││
│  │  ⏱️ Duración Calculada: 4.0 horas                       ││
│  │                                                         ││
│  │  📝 Notas del servicio: [________________]              ││
│  │                                                         ││
│  │  🛡️ Este ticket tiene póliza                            ││
│  │     Consumo actual: 8h / 20h incluidas                  ││
│  │     Después de este ticket: 12h / 20h                   ││
│  │                                                         ││
│  │  [Cancelar]            [✓ Registrar y Cerrar]          ││
│  └─────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────┘
```

---

## 👁️ Vista del Cliente en Portal

```
┌─────────────────────────────────────────────────────────────┐
│  MI CONSUMO DE SOPORTE - Enero 2026                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🛡️ Póliza: Soporte Premium Mensual                        │
│  📊 Horas Incluidas: 20 horas/mes                          │
│                                                             │
│  ████████████░░░░░░░░ 60% usado                            │
│  12h consumidas de 20h incluidas                           │
│                                                             │
│  ⚠️ Te quedan 8 horas este mes                             │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  ÚLTIMOS SERVICIOS                                         │
├─────────────────────────────────────────────────────────────┤
│  TKT-2026-00042 | Revisión de red                          │
│  📅 16/01/2026  ⏰ 8:00 AM - 12:00 PM  ⏱️ 4.0h              │
│  ──────────────────────────────────────────────────────────│
│  TKT-2026-00038 | Instalación software                     │
│  📅 14/01/2026  ⏰ 2:00 PM - 5:30 PM   ⏱️ 3.5h              │
│  ──────────────────────────────────────────────────────────│
│  TKT-2026-00035 | Backup de servidor                       │
│  📅 10/01/2026  ⏰ 9:00 AM - 1:30 PM   ⏱️ 4.5h              │
│                                                             │
│  [📄 Ver Reporte Completo PDF]                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 Archivos Modificados/Creados

### Controladores
- `app/Http/Controllers/TicketController.php` ✅ Modificado
- `app/Http/Controllers/Reportes/ReporteSoporteController.php` ✅ Creado

### Vistas Vue
- `resources/js/Pages/Soporte/Index.vue` ✅ Modificado
- `resources/js/Pages/Soporte/Show.vue` ✅ Modificado
- `resources/js/Pages/Soporte/Dashboard.vue` ✅ Modificado
- `resources/js/Pages/Portal/Dashboard.vue` 🔄 Pendiente

### Vistas Blade (Reportes PDF)
- `resources/views/reportes/soporte/consumo-poliza.blade.php` ✅ Creado
- `resources/views/reportes/soporte/horas-tecnico.blade.php` ✅ Creado

### Rutas
- `routes/admin.php` 🔄 Pendiente agregar rutas de reportes

### Migraciones
- 🔄 Pendiente: Migración para campos `servicio_inicio_at` y `servicio_fin_at`

---

## 🚀 Comandos para Desplegar

```bash
# En el VPS
cd /var/www/asistenciavircom

# Actualizar código
git pull origin main

# Si hay nuevas migraciones
php artisan migrate

# Reconstruir assets
npm run build

# Limpiar caché
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📌 Notas Importantes

1. **Cálculo de horas**: Se puede ingresar manualmente O calcular automáticamente si se ingresan hora inicio/fin
2. **Pólizas sin límite de horas**: Si `horas_incluidas_mensual` es NULL, solo se registra para estadísticas pero no se valida límite
3. **Reportes PDF**: Se abren en nueva pestaña, el usuario puede imprimir o guardar como PDF desde el navegador
4. **Zona horaria**: Usar la zona horaria configurada en la empresa (America/Hermosillo)
