# 📥 Instalación RustDesk para Clientes de Vircom

## Opción 1: Enlace Mágico (MÁS FÁCIL)

Envía este enlace a tu cliente. Al abrirlo, RustDesk se configura automáticamente:

```
rustdesk://config=eyJyZW5kZXp2b3VzX3NlcnZlciI6IjE5MS4xMDEuMjMzLjgyIiwia2V5IjoibldabjB3RTdHcTZtZWltbnRsdjBHOHVzQmt4RGpvUjArT1RnVWg3NldFVT0ifQ==
```

> **Nota:** El cliente debe tener RustDesk instalado primero. Luego abre el enlace y listo.

---

## Opción 2: Script de Instalación Windows (AUTOMÁTICO)

Descarga este script y ejecútalo como administrador:

### `instalar_vircom_remote.bat`
```batch
@echo off
echo ====================================
echo   Instalador Vircom Remote
echo ====================================
echo.

:: Descargar RustDesk
echo Descargando RustDesk...
curl -L -o "%TEMP%\rustdesk.exe" "https://github.com/rustdesk/rustdesk/releases/download/1.3.1/rustdesk-1.3.1-x86_64.exe"

:: Instalar RustDesk silenciosamente
echo Instalando...
start /wait "" "%TEMP%\rustdesk.exe" --silent-install

:: Configurar servidor
echo Configurando servidor Vircom...
timeout /t 5 >nul

:: Crear archivo de configuración
set APPDATA_RUSTDESK=%APPDATA%\RustDesk\config
if not exist "%APPDATA_RUSTDESK%" mkdir "%APPDATA_RUSTDESK%"

(
echo rendezvous_server = "191.101.233.82"
echo key = "nWZn0wE7Gq6meimntlv0G8usBkxDjoR0+OTgUh76WEU="
echo allow-auto-disconnect = ""
echo allow-always-software-render = ""
) > "%APPDATA_RUSTDESK%\RustDesk.toml"

echo.
echo ====================================
echo   ¡Instalación completada!
echo   Ya puedes usar Vircom Remote
echo ====================================
pause
```

---

## Opción 3: Archivo de Configuración Manual

Si el cliente ya tiene RustDesk instalado, solo necesita este archivo:

### Ubicación del archivo:
- **Windows:** `%APPDATA%\RustDesk\config\RustDesk.toml`
- **macOS:** `~/Library/Application Support/RustDesk/config/RustDesk.toml`
- **Linux:** `~/.config/rustdesk/RustDesk.toml`

### Contenido del archivo `RustDesk.toml`:
```toml
rendezvous_server = "191.101.233.82"
key = "nWZn0wE7Gq6meimntlv0G8usBkxDjoR0+OTgUh76WEU="
```

---

## Datos de Conexión

| Parámetro | Valor |
|-----------|-------|
| **Servidor** | `191.101.233.82` |
| **Llave Pública** | `nWZn0wE7Gq6meimntlv0G8usBkxDjoR0+OTgUh76WEU=` |
| **Puerto ID** | 21116 |
| **Puerto Relay** | 21117 |

---

## Instrucciones para el Cliente

1. Ejecutar el instalador o abrir el enlace mágico
2. RustDesk se abre automáticamente configurado
3. Anota el **ID** que aparece (ej: `123456789`)
4. Comparte el ID con soporte técnico
5. ¡Listo! El técnico puede conectarse remotamente

---

*Generado automáticamente para Vircom - Navidad 2025*
