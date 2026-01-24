# 🖥️ VPS Information - Asistencia Vircom

Este archivo contiene la información técnica del servidor VPS para referencia de los agentes de IA.

## 📍 Detalles de Conexión
- **IP Pública:** `191.101.233.82`
- **Dominio Principal (Mail):** `mail.asistenciavircom.com`
- **Usuario SSH:** `root`
- **Ubicación Física de Datos:** `/opt/`

## 📨 Servidor de Correos (Mail Server)
- **Software:** **Mailcow Dockerized** (`/opt/mailcow-dockerized/`)
- **Gestión:** Administrado vía Docker y accesible usualmente vía interfaz web en el dominio de correo.
- **Capacidad:** Sin límites de envío artificiales (basado solo en la reputación de la IP y recursos del VPS).
- **Servicios Relacionados:** Postfix, Dovecot, SOGo, Rspamd.

## 🛠️ Herramientas de Gestión
- **Panel:** Portainer (`/opt/portainer/`) para gestión de contenedores Docker.
- **Backups:** Ubicados en `/opt/backups/`.

---
*Nota: Este servidor es utilizado para el envío de boletines y correos masivos a los +600 clientes de Vircom sin costos externos de API.*
