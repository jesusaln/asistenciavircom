# 🛠️ Guía de RustDesk - Vircom Remote

Esta guía contiene todo lo necesario para gestionar tu propio servidor de soporte remoto, migrado a **Coolify**.

---

## 🚀 1. El "Cliente Mágico" (Zero Config)
Para que tus clientes no tengan que configurar nada, descarga el instalador oficial de Windows y cámbiale el nombre a:

`rustdesk-host=191.101.233.82,key=nWZn0wE7Gq6meimntlv0G8usBkxDjoR0+OTgUh76WEU=.exe`

> **¿Cómo funciona?** Al abrirlo, el programa lee la IP y la llave desde el nombre del archivo y se conecta solo a tu servidor.

---

## 🎨 2. Vircom Remote (Versión con Logo)
Si prefieres un instalador profesional con el logo de Vircom inyectado:

1.  Ve a tu repositorio privado: [jesusaln/RustDesk-Vircom](https://github.com/jesusaln/RustDesk-Vircom).
2.  Entra en la pestaña **Actions**.
3.  Descarga el archivo generado en la sección **Artifacts** de la última compilación.
4.  Este archivo ya viene "soldado" a tu servidor y tiene tu identidad visual.

---

## ⚙️ 3. Configuración Manual (Móviles o Técnicos)
Si necesitas configurar un dispositivo manualmente:

- **ID Server:** `191.101.233.82`
- **Relay Server:** `191.101.233.82`
- **API Server:** `http://191.101.233.82:21114`
- **Key:** `nWZn0wE7Gq6meimntlv0G8usBkxDjoR0+OTgUh76WEU=`

---

## 🖥️ 4. Infraestructura en el Servidor (Coolify)
El servidor corre bajo Docker en Coolify con los siguientes puertos abiertos:

| Puerto | Protocolo | Servicio |
| :--- | :--- | :--- |
| `21115` | TCP | P2P Connection |
| `21116` | TCP/UDP | ID Server |
| `21117` | TCP | Relay Server |
| `21114` | TCP | API Server |
| `21118/19` | TCP | Web Sockets (Soporte vía Web) |

**Ubicación de llaves:** `/data/coolify/rustdesk/keys`

---

## 💡 Tips de Uso
- **Contraseña Fija:** En el equipo del cliente, puedes establecer una contraseña fija en el menú de seguridad para entrar siempre sin pedir permiso.
- **Libreta de Direcciones:** Al usar el servidor de API (`21114`), puedes iniciar sesión en tus clientes para guardar una lista de todos tus PCs gestionados.

---

## 🌐 5. Cliente Web y Landing Page (Nuevo)
Acceso universal vía navegador sin instalar nada.

### URLs
- **Landing Page (Clientes):** [https://remoto.asistenciavircom.com](https://remoto.asistenciavircom.com)
  - *Instrucciones claras y descargas directas.*
- **Cliente Web (Directo):** [https://remoto.asistenciavircom.com/webclient/](https://remoto.asistenciavircom.com/webclient/)
  - *Cliente Flutter completo en el navegador.*
- **Panel Administrativo:** [https://remoto.asistenciavircom.com/_admin/](https://remoto.asistenciavircom.com/_admin/)

### Arquitectura Técnica (Híbrida)
Para que convivan la Landing Page personalizada y el Cliente Web de RustDesk (Flutter), configuramos Nginx de forma especial:

1. **Separación de Archivos:**
   - `landing.html`: Es la página de inicio (TailwindCSS) creada por nosotros. Se sirve cuando entras a la raíz `/`.
   - `index.html`: Es el punto de entrada de la app Flutter. Se sirve **SOLO** bajo `/webclient/`.
   - *Ubicación en Servidor:* `/var/www/rustdesk-web/`

2. **Nginx Trucos:**
   - Rewrite Rule: `rewrite ^/webclient$ /webclient/ permanent;` (Fuerza la barra al final para evitar errores de carga de assets relativos).
   - WebSocket Proxy: Los puertos `21118` y `21119` están proxeados internamente por Nginx bajo `/ws` para permitir conexiones seguras (WSS) sin abrir puertos extra en el firewall público.
