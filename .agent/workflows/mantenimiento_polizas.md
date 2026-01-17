---
description: Proceso de mantenimiento automático de pólizas
---

# 🔧 Sistema de Mantenimientos Preventivos para Pólizas

Este documento describe el sistema de tareas de mantenimiento que se generan automáticamente según la póliza contratada.

---

## 📋 Concepto General

Cada póliza puede tener asociadas **tareas de mantenimiento programadas** que:
1. Se generan automáticamente según frecuencia (semanal, quincenal, mensual)
2. Se asignan al técnico responsable de la póliza
3. Aparecen en el dashboard del técnico como "Tareas Pendientes"
4. Se registran en bitácora con evidencia
5. Notifican al cliente si se detectan anomalías

---

## 🎯 Tipos de Tareas de Mantenimiento

### 💾 Respaldos
| Tarea | Frecuencia | Descripción |
|-------|------------|-------------|
| Verificar respaldo local | Semanal | Revisar que existe y fecha actualizada |
| Verificar respaldo en nube | Mensual | Confirmar sincronización activa |
| Notificar sin respaldo | Inmediato | Alertar si no hay respaldo en 7+ días |

### 💻 Equipos de Cómputo
| Tarea | Frecuencia | Descripción |
|-------|------------|-------------|
| Revisar salud de disco | Mensual | Verificar SMART y espacio libre |
| Revisar actualizaciones Windows | Quincenal | Verificar parches pendientes |
| Revisar antivirus | Semanal | Confirmar base de datos actualizada |
| Limpiar temporales | Mensual | Liberar espacio en disco |

### 📹 CCTV (Cámaras)
| Tarea | Frecuencia | Descripción |
|-------|------------|-------------|
| Verificar grabación | Semanal | Confirmar que todas las cámaras graban |
| Revisar almacenamiento DVR | Quincenal | Verificar espacio disponible |
| Limpiar lentes | Trimestral | Visita para limpieza física |
| Verificar conexión remota | Mensual | Probar acceso desde App |

### 🚨 Alarmas
| Tarea | Frecuencia | Descripción |
|-------|------------|-------------|
| Probar panel | Mensual | Verificar comunicación con central |
| Verificar baterías | Trimestral | Revisar voltaje de respaldo |
| Probar sensores | Bimestral | Activar cada zona manualmente |

### 🛒 POS / Punto de Venta
| Tarea | Frecuencia | Descripción |
|-------|------------|-------------|
| Verificar respaldo BD | Diario | Confirmar backup automático |
| Revisar impresora fiscal | Semanal | Limpieza y prueba de impresión |
| Actualizar catálogos SAT | Mensual | Si aplica facturación |

---

## 🏗️ Estructura de Base de Datos

### Tabla: `poliza_mantenimientos` (Template de tareas por póliza)
```sql
CREATE TABLE poliza_mantenimientos (
    id BIGSERIAL PRIMARY KEY,
    poliza_id BIGINT REFERENCES polizas_servicio(id),
    tipo VARCHAR(50),           -- 'respaldo', 'disco', 'antivirus', 'cctv', etc.
    nombre VARCHAR(255),        -- Nombre de la tarea
    descripcion TEXT,           -- Instrucciones para el técnico
    frecuencia VARCHAR(20),     -- 'diario', 'semanal', 'quincenal', 'mensual', 'trimestral'
    requiere_visita BOOLEAN DEFAULT FALSE,
    activo BOOLEAN DEFAULT TRUE,
    ultima_ejecucion TIMESTAMP,
    proxima_ejecucion TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Tabla: `poliza_mantenimiento_ejecuciones` (Bitácora)
```sql
CREATE TABLE poliza_mantenimiento_ejecuciones (
    id BIGSERIAL PRIMARY KEY,
    mantenimiento_id BIGINT REFERENCES poliza_mantenimientos(id),
    tecnico_id BIGINT REFERENCES users(id),
    fecha_ejecucion TIMESTAMP,
    resultado VARCHAR(20),      -- 'ok', 'alerta', 'critico', 'pendiente'
    notas TEXT,
    evidencia_json JSONB,       -- Screenshots, valores, etc.
    tiempo_minutos INT,
    notificado_cliente BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP
);
```

---

## 📱 Flujo del Técnico

1. **Dashboard del Técnico** muestra:
   ```
   ┌─────────────────────────────────────────┐
   │ 🔧 Mantenimientos Pendientes Hoy (5)    │
   ├─────────────────────────────────────────┤
   │ 💾 Verificar respaldo - Cliente ABC     │
   │ 💻 Revisar disco - Ofic. Judith         │
   │ 📹 Verificar grabación - Bodega XYZ     │
   │ 🚨 Probar panel alarma - Casa López     │
   │ 🛒 Respaldo BD - Tienda Norte           │
   └─────────────────────────────────────────┘
   ```

2. **Al completar tarea**, el técnico:
   - Marca resultado: ✅ OK / ⚠️ Alerta / 🔴 Crítico
   - Agrega notas
   - Sube evidencia (opcional)
   - Sistema genera próxima ejecución automáticamente

3. **Si hay alerta o crítico**:
   - Se notifica al admin
   - Se crea ticket automático (opcional)
   - Se notifica al cliente (configurable)

---

## 🔔 Notificaciones

| Evento | Destinatario | Canal |
|--------|--------------|-------|
| Tarea pendiente | Técnico | Dashboard + Email AM |
| Tarea atrasada (+24h) | Técnico + Admin | Email + Push |
| Resultado con alerta | Admin | Email |
| Resultado crítico | Admin + Cliente | Email + SMS (opt) |
| Resumen semanal | Cliente | Email |

---

## 📊 Dashboard de Pólizas Mejorado

### Para el Admin
```
┌──────────────────────────────────────────────────────┐
│ 📊 Estado de Mantenimientos                          │
├──────────────────────────────────────────────────────┤
│ ✅ Completados hoy: 12                               │
│ ⏳ Pendientes: 8                                     │
│ ⚠️ Con alertas: 3                                    │
│ 🔴 Atrasados: 2                                      │
├──────────────────────────────────────────────────────┤
│ 📝 Últimas ejecuciones:                              │
│ • Respaldo BD - Tienda Norte ✅ OK (hace 1h)         │
│ • Disco SMART - Ofic Central ⚠️ 85% usado (hace 2h) │
│ • Antivirus - Laptop Gerente ✅ OK (hace 3h)         │
└──────────────────────────────────────────────────────┘
```

### Para el Cliente (Portal)
```
┌──────────────────────────────────────────────────────┐
│ 🛡️ Historial de Mantenimientos                       │
├──────────────────────────────────────────────────────┤
│ Último mantenimiento: 15 Ene 2026                    │
│ Próximo programado: 22 Ene 2026                      │
├──────────────────────────────────────────────────────┤
│ 📋 Últimas revisiones:                               │
│ • 15 Ene - Verificación de respaldos ✅              │
│ • 12 Ene - Revisión de disco duro ✅                 │
│ • 08 Ene - Actualización antivirus ✅                │
└──────────────────────────────────────────────────────┘
```

---

## 🚀 Fases de Implementación

### Fase 1: Base de Datos y Modelo (2-3 hrs)
- [ ] Crear migración `poliza_mantenimientos`
- [ ] Crear migración `poliza_mantenimiento_ejecuciones`
- [ ] Crear modelos Eloquent
- [ ] Crear seeders con tareas predefinidas

### Fase 2: Generador Automático (2-3 hrs)
- [ ] Comando artisan `polizas:generar-mantenimientos`
- [ ] Scheduler para ejecutar diariamente
- [ ] Lógica de cálculo de próxima ejecución

### Fase 3: Dashboard del Técnico (3-4 hrs)
- [ ] Vista de tareas pendientes
- [ ] Formulario de ejecución (resultado + notas)
- [ ] Upload de evidencia
- [ ] Histórico de ejecuciones

### Fase 4: Notificaciones (2-3 hrs)
- [ ] Notificación de tareas pendientes AM
- [ ] Alerta de tareas atrasadas
- [ ] Notificación al cliente de alertas

### Fase 5: Portal del Cliente (2-3 hrs)
- [ ] Sección "Historial de Mantenimientos"
- [ ] Reporte mensual de actividades
- [ ] Descarga de bitácora PDF

---

## 📝 Plantillas de Tareas por Tipo de Póliza

### Póliza Soporte (Mini/Básica/Pro/Premium)
- Verificar respaldo local (mensual)
- Revisar salud de disco (mensual)
- Verificar antivirus (quincenal)

### Póliza CCTV
- Verificar grabación (semanal)
- Revisar almacenamiento (quincenal)
- Probar conexión remota (mensual)

### Póliza Alarmas
- Probar comunicación panel (mensual)
- Verificar baterías (trimestral)
- Probar sensores (bimestral)

### Póliza POS
- Verificar respaldo BD (diario vía notificación)
- Revisar impresora (semanal)
- Actualizar catálogos (mensual)

---

## ✅ Estado de Implementación

| Fase | Estado | Fecha | Notas |
|------|--------|-------|-------|
| 1 | ✅ Completado | 2026-01-17 | Migraciones y Modelos |
| 2 | ✅ Completado | 2026-01-17 | Servicio y Comandos |
| 3 | ⏳ Pendiente | - | Dashboard Técnico |
| 4 | ⏳ Pendiente | - | Notificaciones |
| 5 | 🚧 En Progreso | 2026-01-17 | Visualización y Solicitud Manual en Portal Cliente |

---

*Documento actualizado: 2026-01-17*
