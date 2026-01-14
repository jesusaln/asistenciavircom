---
description: Workflow para gestionar y monitorear el VPS de Hostinger (191.101.233.82)
---

Este workflow permite obtener el estado actual del VPS y sus contenedores Docker.

### Comandos de monitoreo

// turbo
1. Ver contenedores activos y su estado:
   `ssh root@191.101.233.82 "docker ps -a --format 'table {{.Names}}\t{{.Status}}\t{{.Image}}'"`

// turbo
2. Ver uso de recursos (Disco y Memoria):
   `ssh root@191.101.233.82 "df -h / && free -h"`

### Mantenimiento y Limpieza

// turbo
3. Ver imágenes de respaldo creadas:
   `ssh root@191.101.233.82 "docker images | grep backup"`

// turbo
4. Ver espacio en disco:
   `ssh root@191.101.233.82 "df -h /"`

### Información del Servidor
- **IP:** 191.101.233.82
- **Usuario:** root
- **Proveedor:** Hostinger
- **Gestor:** Coolify (v4.0.0-beta.459)

### 📦 Inventario de Contenedores

#### 🚀 Aplicación Principal (CDD Climas)
| Contenedor | Imagen | Estado | Puerto Interno | Notas |
|------------|--------|--------|----------------|-------|
| `climasdeldesierto-app-1` | `climasdeldesierto-app` | **UP** | 8000 | Contenedor principal de la app Laravel 11 + Vue 3 (Renombrado) |
| `climasdeldesierto-web-1` | `climasdeldesierto-web` | **UP** | 8080 | Nginx frontend (Renombrado) |
| `climasdeldesierto-queue-1` | `climasdeldesierto-queue` | **UP** | - | Worker de colas (Renombrado) |
| `climasdeldesierto-db-1` | `postgres:15-alpine` | **UP** | 5432 | Postgres DB (Renombrado) |
| `dgc4wsk44gs0kcoowsogggcw-cdd_redis-1` | `redis:alpine` | **UP** | 6379 | Redis caché para la app |

#### 🤖 Automatización y Chat
| Contenedor | Servicio | Imagen | Estado | Puertos |
|------------|----------|--------|--------|---------|
| `n8n-p048cwo4o4k0k08sssk8g4cg` | **n8n** | `n8nio/n8n:latest` | **UP** | 5678:5678 |
| `rails-d0o0skgwsow0g0w0ks8gog0w` | **Chatwoot App** | `chatwoot/chatwoot` | **UP** | 3000:3000 |
| `sidekiq-d0o0skgwsow0g0w0ks8gog0w` | **Chatwoot Worker** | `chatwoot/chatwoot` | **UP** | - |
| `postgres-d0o0skgwsow0g0w0ks8gog0w` | **Chatwoot DB** | `pgvector/pgvector` | **UP** | 5432 |
| `redis-d0o0skgwsow0g0w0ks8gog0w` | **Chatwoot Redis** | `redis:alpine` | **UP** | 6379 |

#### 🖥️ Acceso Remoto (RustDesk)
| Contenedor | Rol | Estado |
|------------|-----|--------|
| `hbbs-...` | ID Server | **UP** |
| `hbbr-...` | Relay Server | **UP** |
| `api-...` | API Server | **UP** |

#### 🎛️ Infraestructura Coolify
| Contenedor | Rol | Estado | Puertos |
|------------|-----|--------|---------|
| `coolify` | Core | **UP** | 8000:8080 |
| `coolify-proxy` | Traefik Proxy | **UP** | 80, 443 |
| `coolify-db` | Database | **UP** | - |
| `coolify-redis`| Cache | **UP** | - |
| `coolify-realtime` | Websockets | **UP** | 6001 |

#### 💾 Bases de Datos Standalone
- `postgres-new`: Postgres 16 (Puerto 5432 expuesto).

#### 🖥️ Asistencia Vircom (Empresa Independiente - Tecnología/Cómputo/Alarmas/CCTV)
| Contenedor | Imagen | Estado | Puerto Publico | Notas |
|------------|--------|--------|----------------|-------|
| `ionic_vircom-web-1` | `ionic_vircom-web` | **UP** | 3002 | App Móvil/Web Ionic para Vircom |
| `asistenciavircom-web-1` | `asistenciavircom-web` | **UP** | 8080 | Sistema ERP Vircom - Web Nginx |
| `asistenciavircom-app-1` | `asistenciavircom-app` | **UP** | - | Sistema ERP Vircom - App PHP |
| `asistenciavircom-queue-1` | `asistenciavircom-queue` | **UP** | - | Sistema ERP Vircom - Worker |
| `asistenciavircom-db-1` | `postgres:15-alpine` | **UP** | - | Sistema ERP Vircom - DB |


---
*Nota: Este archivo sirve como memoria técnica para Antigravity. Para ejecutarlo, simplemente pide "revisa el VPS" o "dame el estado del servidor".*

**Última actualización:** 12 de Enero de 2026.

