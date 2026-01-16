---
description: Guía para crear y administrar pólizas de servicio
---

# Guía de Pólizas de Servicio

Esta guía explica cómo crear, configurar y administrar pólizas de servicio para clientes.

## ¿Qué es una Póliza de Servicio?

Una póliza es un **contrato de servicio recurrente** que garantiza al cliente:
- Soporte técnico prioritario
- Mantenimientos preventivos programados  
- Horas de servicio incluidas
- Tiempo de respuesta garantizado (SLA)

## Crear una Nueva Póliza

### Paso 1: Acceder al Módulo
1. Ir a **Menú → Pólizas de Servicio**
2. Click en **"+ Nueva Póliza"**

### Paso 2: Configuración General
| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Cliente** | Seleccionar cliente existente | "Empresa XYZ S.A." |
| **Nombre de la Póliza** | Nombre descriptivo del plan | "Póliza Gold Mantenimiento" |
| **Estado** | Generalmente "Activa" para nuevas | Activa ✅ |
| **SLA Respuesta** | Horas máximas para primera respuesta | 4 horas |
| **Descripción** | Detalle del alcance del servicio | "Incluye 2 visitas mensuales..." |

### Paso 3: Configuración Financiera
| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Monto Mensual** | Cargo recurrente (sin IVA) | $3,500.00 |
| **Día de Cobro** | Día del mes para generar cobro | Día 5 |
| **Límite de Tickets** | Máximo de tickets/mes (opcional) | 10 |

### Paso 4: Control por Horas (Opcional)
| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Horas Incluidas/Mes** | Horas de servicio incluidas | 8 horas |
| **$ Hora Extra** | Costo por hora adicional | $350.00 |

### Paso 5: Mantenimiento Preventivo
| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Frecuencia (Meses)** | Cada cuántos meses se agenda | 3 meses |
| **Anticipación (Días)** | Días antes para generar ticket | 7 días |
| **Próxima Visita** | Fecha del primer mantenimiento | 15/02/2026 |
| **Autogenerar Ticket/Cita** | ✅ Activa la automatización | ✅ |

### Paso 6: Registrar Equipos
- Agregar todos los equipos cubiertos por la póliza
- Incluir nombre descriptivo y número de serie
- **Ya no hay límite de equipos**

### Paso 7: Guardar
- Click en **"LEGALIZAR PÓLIZA"** (nueva) o **"ACTUALIZAR CONTRATO"** (edición)

---

## Dashboard de Pólizas

El dashboard muestra métricas clave:

| Métrica | Qué Significa | Acción |
|---------|---------------|--------|
| **Ingresos Mensuales** | Total de ingresos recurrentes | Monitorear crecimiento |
| **Cobros Pendientes** | Deuda acumulada | ⚠️ Dar seguimiento |
| **Tasa de Retención** | % de clientes que renuevan | Meta: >80% |
| **Exceso de Horas** | Dinero por facturar | 💰 Generar cobros |
| **Próximas a Vencer** | Pólizas por renovar | 📧 Enviar recordatorio |

---

## Acciones Rápidas

Desde la vista de una póliza individual puedes:

1. **💰 Cobrar Ahora** - Genera una cuenta por cobrar inmediata
2. **📧 Recordar Renovación** - Envía email al cliente con resumen de beneficios
3. **📄 PDF Beneficios** - Genera documento para el cliente
4. **📊 Historial** - Ver consumo de horas detallado

---

## Indicadores de Salud

La póliza muestra un indicador visual de estado:

| Indicador | Significado |
|-----------|-------------|
| 🟢 Saludable | Todo en orden |
| 🟡 Atención | 80%+ horas consumidas |
| 🟠 Urgente | Vence en 7 días o menos |
| 🟣 Excedida | Sobrepasó horas incluidas |
| 🔴 Vencida | La póliza ya expiró |

---

## Flujo de Trabajo Sugerido

```
1. CREAR PÓLIZA
   ↓
2. REGISTRAR EQUIPOS
   ↓
3. CONFIGURAR MANTENIMIENTO AUTOMÁTICO
   ↓
4. SISTEMA GENERA TICKETS/CITAS
   ↓
5. TÉCNICO REGISTRA HORAS
   ↓
6. DASHBOARD MUESTRA CONSUMO
   ↓
7. GENERAR COBRO MENSUAL
   ↓
8. ENVIAR RECORDATORIO RENOVACIÓN (30 días antes)
```

---

## Preguntas Frecuentes

### ¿Cómo bloqueo soporte a un cliente moroso?
El dashboard muestra "Pólizas con Deuda". Puedes cambiar el estado de la póliza a "Inactiva" hasta que regularice su pago.

### ¿Cómo facturo horas extra?
1. Ve al Dashboard de Pólizas
2. Revisa la sección "Exceso de Horas"
3. Click en "💰 Generar Cobro" para cada póliza

### ¿Cómo agrego más equipos?
1. Edita la póliza
2. Busca la sección "Equipos Cubiertos"
3. Agrega los equipos necesarios
4. Guarda los cambios

### ¿El cliente puede ver su póliza?
Sí, desde el Portal del Cliente puede ver:
- Horas consumidas vs disponibles
- Equipos cubiertos
- Historial de servicios

---

*Última actualización: 15 de Enero de 2026*
