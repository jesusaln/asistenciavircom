# 🛡️ PROTOCOLO DE RECUPERACIÓN TOTAL (AI-READY) - Climas del Desierto

> [!IMPORTANT]
> **PARA EL ASISTENTE IA:** Este documento es una guía técnica para restaurar un sistema Laravel gestionado por Coolify en un servidor Ubuntu limpio. Sigue los pasos secuencialmente.

## 🤖 CONTEXTO TÉCNICO
*   **Framework:** Laravel 12+ (PHP 8.3/8.4).
*   **Orquestador:** Coolify (Docker-based).
*   **Base de Datos:** PostgreSQL 15+.
*   **Almacenamiento:** Los archivos persistentes viven en el volumen `/data/coolify/cdd_climas_storage`.
*   **Respaldo:** Un archivo `.tar.gz` en Google Drive que contiene el volcado SQL (`db_*.sql`) y la carpeta `storage/app/public`.

---

## 🛠️ PASO 1: Preparación del Entorno
La IA debe conectarse al nuevo servidor vía SSH y ejecutar:
1.  Instalar Docker.
2.  Instalar Coolify (usando el comando oficial de `curl`).
3.  Instalar `rclone` (`curl https://rclone.org/install.sh | bash`).

## 🛠️ PASO 2: Configuración de Datos (Las Llaves)
Para que la IA pueda bajar el respaldo de Google Drive, debe configurar `rclone` usando los datos del archivo `LLAVES_DE_EMERGENCIA.txt`:
*   **CLIENT_ID**, **CLIENT_SECRET** y **REFRESH_TOKEN**.
*   Comando para configurar: `rclone config create gdrive drive ...`

## 🛠️ PASO 3: Descarga y Extracción
La IA debe buscar en Google Drive la carpeta `Climas_Backups` y descargar el archivo más reciente:
```bash
# Descargar
rclone copy gdrive:Climas_Backups/archivo_mas_reciente.tar.gz /root/

# Crear ruta de volumen y extraer
mkdir -p /data/coolify/cdd_climas_storage
tar -xzf /root/archivo_mas_reciente.tar.gz -C /data/coolify/cdd_climas_storage/
```

## 🛠️ PASO 4: Restauración de Base de Datos
Dentro del nuevo proyecto en Coolify, identificar el contenedor de Postgres y ejecutar:
```bash
# Importar SQL (ubicado en la base de la extracción)
docker exec -i <CONTAINER_ID> psql -U postgres -d cdd_climas < /data/coolify/cdd_climas_storage/db_*.sql
```

## 🛠️ PASO 5: Despliegue de Código
1.  Clonar el repositorio: `git clone https://github.com/jesusaln/cdd_climas.git`.
2.  **CRÍTICO:** Crear el archivo `.env` en la raíz copiando EXACTAMENTE el contenido del apartado 2 del archivo `LLAVES_DE_EMERGENCIA.txt`.
3.  Ejecutar el script de despliegue: `./deploy.sh climas`.

---

## 📈 TAREAS DE VERIFICACIÓN POST-DESASTRE
La IA debe confirmar:
1.  Conexión a Redis exitosa (`php artisan tinker --execute="echo Cache::store('redis')->put('test',1)"`).
2.  Simetría de datos en la tabla `clientes`.
3.  Funcionamiento de Reverb (Websockets) para tiempo real.
