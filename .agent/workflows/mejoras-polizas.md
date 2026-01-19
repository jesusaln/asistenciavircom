---
description: Plan de mejoras para el sistema de pólizas de servicio
---

# Mejoras para Pólizas de Servicio

## Estado de Implementación

### ✅ Completado

#### 1. Automatización y Operación
- [x] **SLA Dinámico**: Campos `sla_horas_respuesta` y `sla_horas_resolucion` en PlanPoliza y PolizaServicio
- [x] **SlaService**: Servicio para calcular fechas límite considerando horarios laborales (L-V 9am-6pm, Sáb 9am-2pm)
- [x] **Vinculación automática de Tickets a Póliza**: El sistema vincula automáticamente los tickets al crear si el cliente tiene una póliza activa
- [x] **Servicios Elegibles por Plan**: Tabla pivote `plan_poliza_servicios` para definir qué servicios consumen banco de horas y cuáles generan cobro extra
- [x] **Consumo Inteligente**: Método `consumirHoras()` en PolizaServicio que valida elegibilidad de servicio, genera CXC si no es elegible, y notifica al 20% de horas restantes

#### 2. Experiencia del Cliente (VircomBot)
- [x] **Consulta de Saldo de Póliza por WhatsApp**: Nueva herramienta `consultar_saldo_poliza` en VircomBotService
- [x] Los clientes pueden preguntar: "¿Cuántas horas me quedan?" y obtener respuesta automática

#### 3. Inteligencia de Negocio (Rentabilidad)
- [x] **PolizaRentabilidadService**: Servicio para analizar `ingreso_mensual` vs `costo_operativo` (horas * costo_hora_tecnico)
- [x] **Campo costo_promedio_hora_tecnico**: En `empresa_configuracion` para calcular costos
- [x] **Reporte de Rentabilidad**: Nueva vista `PolizaServicio/ReporteRentabilidad.vue` con:
  - KPIs principales (Total Ingresos, Costos, Utilidad Neta, Margen Promedio)
  - Clasificación por rentabilidad (Rentables, Marginales, En Pérdida)
  - Tabla detallada por póliza con margen y utilidad
- [x] **Acceso desde Dashboard**: Botón "📊 Rentabilidad" en el Dashboard de Pólizas

### 🔲 Pendiente / Futuras Mejoras

#### Automatización
- [ ] **Auto-Facturación de Excedentes**: Al final del mes, generar borrador de factura automática con todos los "Servicios Extra" y "Horas Excedentes" acumulados
- [ ] **Generación automática de Tickets Preventivos**: Si una póliza incluye "N mantenimientos anuales", el sistema debería programarlos automáticamente

#### Experiencia del Cliente
- [ ] **Dashboard Interactivo en Portal**: Gráfico de "Anatomía de Consumo" donde el cliente vea en qué se van sus horas (ej: 40% Soporte Remoto, 60% Redes)
- [ ] **Firma Digital de Contratos**: Que el cliente pueda firmar la aceptación de la póliza directamente en el portal

#### Inteligencia de Negocio
- [ ] **Gestión de Renovaciones (Kanban)**: Tablero de pólizas por vencer (30, 15, 5 días) para que ventas contacte proactivamente

---

## Comandos Útiles

```bash
# Ejecutar migraciones
php artisan migrate

# Probar rutas de pólizas
php artisan route:list --name=polizas

# Ver logs
tail -f storage/logs/laravel.log
```

## Rutas Relevantes

| Ruta | Descripción |
|------|-------------|
| `/polizas-servicio/dashboard` | Dashboard de Pólizas |
| `/polizas-servicio/rentabilidad` | Reporte de Rentabilidad |
| `/polizas-servicio/{id}/historial` | Historial de Consumo |
| `/portal/polizas/{id}` | Vista de Póliza en Portal de Cliente |
