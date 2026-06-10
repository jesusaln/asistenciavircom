# 📚 Documentación Técnica - CDD App (ERP)

> **Versión**: 2.4 (VentaEnLinea)  
> **Última Actualización**: 26 Diciembre 2025

---

## 📋 Tabla de Contenidos

1. [Descripción General](#1-descripción-general)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Arquitectura del Sistema](#3-arquitectura-del-sistema)
4. [Base de Datos](#4-base-de-datos)
5. [Autenticación y Autorización](#5-autenticación-y-autorización)
6. [API REST](#6-api-rest)
7. [Flujos de Negocio](#7-flujos-de-negocio)
8. [Integraciones Externas](#8-integraciones-externas)
9. [Despliegue](#9-despliegue)
10. [Configuración](#10-configuración)

---

## 1. Descripción General

CDD App es un **Sistema ERP (Enterprise Resource Planning)** diseñado para empresas de servicios técnicos y comercio. Integra:

- **Gestión Comercial**: Ventas, Compras, Cotizaciones, Pedidos
- **Inventario**: Multi-almacén, Series, Lotes, Kits
- **Finanzas**: Cuentas por Cobrar/Pagar, Bancos, Préstamos
- **Facturación**: CFDI 4.0 (SAT México)
- **RRHH**: Empleados, Nóminas, Vacaciones
- **CRM**: Prospectos, Campañas, Tareas
- **Soporte**: Tickets, Base de Conocimientos
- **E-Commerce**: Tienda Online, Carrito, Pagos

---

## 2. Stack Tecnológico

### Backend
| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| PHP | 8.2+ | Lenguaje servidor |
| Laravel | 10.x | Framework MVC |
| PostgreSQL | 14+ | Base de datos principal |
| Redis | 6+ | Cache y Colas |

### Frontend
| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| Vue.js | 3.x | Framework reactivo |
| Inertia.js | 1.x | SPA sin API separada |
| Tailwind CSS | 3.x | Estilos utility-first |
| Vite | 5.x | Build tool |

### Librerías Clave
```json
{
  "laravel/jetstream": "Autenticación/Teams",
  "spatie/laravel-permission": "RBAC (Roles/Permisos)",
  "laravel/sanctum": "API Tokens",
  "tightenco/ziggy": "Rutas en JavaScript",
  "maatwebsite/excel": "Importar/Exportar Excel",
  "barryvdh/laravel-dompdf": "Generación PDF"
}
```

---

## 3. Arquitectura del Sistema

### 3.1 Diagrama de Arquitectura

```
┌──────────────────────────────────────────────────────────────────┐
│                          CLIENTE                                  │
│         (Navegador Web / App Móvil / API Consumer)                │
└──────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌──────────────────────────────────────────────────────────────────┐
│                        NGINX (Reverse Proxy)                      │
│                   SSL Termination + Load Balancing                │
└──────────────────────────────────────────────────────────────────┘
                                │
                ┌───────────────┼───────────────┐
                ▼               ▼               ▼
        ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
        │ PHP-FPM (App)│ │ PHP-FPM (App)│ │ PHP-FPM (App)│
        │    Worker 1   │ │    Worker 2   │ │    Worker N   │
        └──────────────┘ └──────────────┘ └──────────────┘
                                │
                ┌───────────────┼───────────────┐
                ▼               ▼               ▼
        ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
        │  PostgreSQL  │ │    Redis     │ │   Storage    │
        │   (Primary)  │ │ (Cache/Queue)│ │ (Files/Logs) │
        └──────────────┘ └──────────────┘ └──────────────┘
```

### 3.2 Patrón MVC con Inertia

```
Request → Middleware → Controller → Service → Model
                           │
                           ▼
                    Inertia::render()
                           │
                           ▼
                   Vue Component (SPA)
```

### 3.3 Capas de la Aplicación

| Capa | Ubicación | Responsabilidad |
|------|-----------|-----------------|
| Presentación | `resources/js/Pages/` | Componentes Vue |
| Controladores | `app/Http/Controllers/` | Lógica HTTP |
| Servicios | `app/Services/` | Lógica de negocio |
| Modelos | `app/Models/` | Acceso a datos |
| Repositorios | (implícito en Eloquent) | Queries |

---

## 4. Base de Datos

### 4.1 Esquema Principal

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│     users       │────▶│    empleados    │────▶│    tecnicos     │
└─────────────────┘     └─────────────────┘     └─────────────────┘
        │                       │
        ▼                       ▼
┌─────────────────┐     ┌─────────────────┐
│     roles       │     │    nominas      │
└─────────────────┘     └─────────────────┘

┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│    clientes     │────▶│     ventas      │────▶│  venta_items    │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                               │
                               ▼
                        ┌─────────────────┐
                        │      cfdis      │
                        └─────────────────┘

┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   proveedores   │────▶│     compras     │────▶│  compra_items   │
└─────────────────┘     └─────────────────┘     └─────────────────┘

┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   productos     │────▶│    almacenes    │────▶│ inventario_mov  │
└─────────────────┘     └─────────────────┘     └─────────────────┘
        │
        ▼
┌─────────────────┐
│ producto_series │
└─────────────────┘
```

### 4.2 Tablas Principales (113 modelos)

| Categoría | Tablas |
|-----------|--------|
| **Usuarios** | users, roles, permissions, teams |
| **Clientes** | clientes, clientes_tienda |
| **Productos** | productos, categorias, marcas, producto_series, lotes |
| **Inventario** | almacenes, inventario_movimientos, traspasos, ajustes |
| **Ventas** | ventas, venta_items, venta_item_series, cotizaciones |
| **Compras** | compras, compra_items, ordenes_compra, proveedores |
| **CFDI** | cfdis, cfdi_conceptos, sat_* (15 catálogos) |
| **Finanzas** | cuentas_por_cobrar, cuentas_por_pagar, movimientos_bancarios |
| **RRHH** | empleados, tecnicos, nominas, vacaciones |
| **CRM** | crm_prospectos, crm_actividades, crm_campañas |
| **Herramientas** | herramientas, asignaciones, historial_herramientas |
| **Soporte** | tickets, ticket_comments, ticket_categories |

---

## 5. Autenticación y Autorización

### 5.1 Sistema de Autenticación

- **Jetstream**: Autenticación base con 2FA opcional
- **Sanctum**: Tokens API para aplicaciones externas
- **OAuth**: Login con Google/Microsoft

### 5.2 Roles y Permisos (Spatie)

```php
// Roles definidos
'super-admin'    // Acceso total
'admin'          // Administrador
'gerente'        // Gerente
'vendedor'       // Ventas
'almacenista'    // Inventario
'contador'       // Finanzas
'tecnico'        // Servicio técnico
'soporte'        // Atención a clientes

// Ejemplo de permisos
'ventas.ver', 'ventas.crear', 'ventas.editar', 'ventas.eliminar'
'productos.ver', 'productos.crear', 'productos.editar'
'reportes.finanzas', 'reportes.inventario'
```

### 5.3 Middleware de Autorización

```php
// Rutas protegidas por rol
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Solo admins
});

// Rutas protegidas por permiso
Route::middleware(['auth', 'permission:ventas.crear'])->group(function () {
    // Solo usuarios con permiso
});
```

---

## 6. API REST

### 6.1 Endpoints Principales

| Recurso | Métodos | Descripción |
|---------|---------|-------------|
| `/api/auth/login` | POST | Autenticación |
| `/api/clientes` | GET, POST, PUT, DELETE | CRUD Clientes |
| `/api/productos` | GET, POST, PUT, DELETE | CRUD Productos |
| `/api/ventas` | GET, POST | Ventas |
| `/api/inventario` | GET | Consulta stock |
| `/api/cfdi` | POST | Timbrar CFDI |

### 6.2 Autenticación API

```bash
# Obtener token
curl -X POST https://app.example.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "secret"}'

# Usar token
curl https://app.example.com/api/productos \
  -H "Authorization: Bearer {token}"
```

---

## 7. Flujos de Negocio

### 7.1 Flujo de Venta

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Cotizar  │───▶│ Aprobar  │───▶│ Vender   │───▶│ Facturar │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
                                      │               │
                                      ▼               ▼
                               ┌──────────┐    ┌──────────┐
                               │ Descontar│    │  Timbrar │
                               │ Inventario│   │   CFDI   │
                               └──────────┘    └──────────┘
```

### 7.2 Flujo de Compra

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Orden de │───▶│ Aprobar  │───▶│ Recibir  │───▶│ Registrar│
│  Compra  │    │  OC      │    │ Mercancía│    │  Compra  │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
                                      │               │
                                      ▼               ▼
                               ┌──────────┐    ┌──────────┐
                               │ Aumentar │    │ Generar  │
                               │ Inventario│   │ Cta x Pagar│
                               └──────────┘    └──────────┘
```

### 7.3 Flujo de Ticket de Soporte

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│  Crear   │───▶│ Asignar  │───▶│ Trabajar │───▶│ Resolver │
│  Ticket  │    │ Técnico  │    │  Ticket  │    │  Cerrar  │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
      │                               │
      ▼                               ▼
┌──────────┐                   ┌──────────┐
│ Notificar│                   │ Notificar│
│ Cliente  │                   │ Cliente  │
└──────────┘                   └──────────┘
```

---

## 8. Integraciones Externas

### 8.1 SAT (CFDI)

- **PAC**: Proveedor de timbrado (configurable)
- **Tipos**: Ingreso (I), Egreso (E), Pago (P), Traslado (T)
- **Descarga Masiva**: Consulta de CFDIs emitidos/recibidos

### 8.2 WhatsApp Business

- **Webhook**: Recepción de mensajes
- **Templates**: Recordatorios de pago
- **Notificaciones**: Estatus de tickets

### 8.3 Pasarelas de Pago

- **MercadoPago**: Checkout Pro
- **PayPal**: Checkout Web

### 8.4 OAuth

- **Google**: Login con cuenta Google
- **Microsoft**: Login con cuenta Microsoft/Azure

---

## 9. Despliegue

### 9.1 Arquitectura de Producción

```
┌─────────────────────────────────────────┐
│           VPS (191.101.233.82)          │
├─────────────────────────────────────────┤
│  ┌─────────────────────────────────┐    │
│  │           NGINX (Host)          │    │
│  │  - SSL/TLS Termination          │    │
│  │  - Reverse Proxy                │    │
│  └─────────────────────────────────┘    │
│           │                │            │
│           ▼                ▼            │
│  ┌────────────┐    ┌────────────┐       │
│  │   Vircom   │    │   Climas   │       │
│  │ (Coolify)  │    │ (Docker)   │       │
│  │ Port 8081  │    │ Port 8080  │       │
│  └────────────┘    └────────────┘       │
│                                         │
│  ┌─────────────────────────────────┐    │
│  │         PostgreSQL              │    │
│  │    (Base de datos compartida)   │    │
│  └─────────────────────────────────┘    │
└─────────────────────────────────────────┘
```

### 9.2 GitHub Actions (CI/CD)

```yaml
# Trigger: push a VentaEnLinea
on:
  push:
    branches: [VentaEnLinea]

# Despliegue automático a ambos entornos
jobs:
  deploy:
    strategy:
      matrix:
        environment: [vircom, climas]
```

---

## 10. Configuración

### 10.1 Variables de Entorno Críticas

```env
# Aplicación
APP_NAME=CDD
APP_ENV=production
APP_URL=https://admin.example.com

# Base de Datos
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_DATABASE=cdd_app
DB_USERNAME=cdd_user
DB_PASSWORD=****

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# CFDI/SAT
PAC_PROVIDER=finkok
PAC_USERNAME=****
PAC_PASSWORD=****

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
```

### 10.2 Configuración Dinámica (BD)

La tabla `empresa_configuracion` almacena:
- Logos y colores
- Datos fiscales (RFC, razón social)
- Configuración SMTP
- Credenciales OAuth/Pagos
- Horarios de reportes automáticos

---

## 📎 Documentos Relacionados

- [📦 Inventario de Módulos](./INVENTARIO_MODULOS.md)
- [📖 Manual de Usuario](./MANUAL_USUARIO.md)
- [🚀 Guía de Despliegue](../README_DEPLOY.md)
