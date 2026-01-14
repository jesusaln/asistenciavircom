# Script de preparación para GitHub en Windows PowerShell
# Uso: .\prepare_github_windows.ps1

Write-Host "🚀 PREPARANDO PROYECTO PARA GITHUB (Windows)" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Yellow

# Crear .gitignore específico para Laravel + Docker
Write-Host "1/4 - Creando .gitignore..." -ForegroundColor Cyan

$gitignoreContent = @"
/node_modules
/vendor
/storage/app/*
/storage/framework/*
/storage/logs/*
/bootstrap/cache/*
/.env
/.env.local
/.env.remote
/.env.network
/.env.zerotier
/.env.hybrid.backup
.env.docker
backup_env/
*.log
.DS_Store
Thumbs.db
.vscode/
.idea/
*.zip
backup_*.sql
"@

Set-Content -Path ".gitignore" -Value $gitignoreContent
Write-Host "✅ .gitignore actualizado" -ForegroundColor Green

# Crear README para GitHub
Write-Host "2/4 - Creando README.md..." -ForegroundColor Cyan

$readmeContent = @"
# CDD App - Sistema de Gestión

Aplicación Laravel para gestión empresarial de Climas del Desierto.

## 🚀 Despliegue Automático con Docker

Esta aplicación se despliega automáticamente usando Docker en cualquier VPS Ubuntu.

### Requisitos Previos
- VPS Ubuntu 20.04 o superior
- Git instalado

### Despliegue en un Solo Comando
```bash
# Clonar repositorio
git clone https://github.com/TU-USUARIO/cdd-app.git
cd cdd-app

# Desplegar automáticamente
./deploy_github.sh
```

### Servicios Incluidos
- **Laravel** (PHP 8.1-FPM) - Aplicación principal
- **PostgreSQL 15** - Base de datos optimizada
- **Redis 7** - Cache y sesiones
- **Nginx** - Servidor web

### Acceso Después del Despliegue
- 🌐 **Aplicación principal**: http://TU-IP
- 📊 **Estado de servicios**: docker-compose ps
- 🔧 **Logs**: docker-compose logs -f

## Características
- ✅ Gestión de clientes y proveedores
- ✅ Control de inventario
- ✅ Sistema de ventas y cotizaciones
- ✅ Reportes avanzados
- ✅ Optimizado para alto volumen de datos
- ✅ Configuración de producción lista

## Mantenimiento
```bash
# Ver estado
docker-compose ps

# Ver logs
docker-compose logs -f

# Hacer respaldo
docker-compose exec db pg_dump -U cdd_user cdd_app_prod > backup.sql

# Detener aplicación
docker-compose down
```
"@

Set-Content -Path "README.md" -Value $readmeContent
Write-Host "✅ README.md creado" -ForegroundColor Green

# Crear script de instalación automática para VPS
Write-Host "3/4 - Creando script de instalación..." -ForegroundColor Cyan

$installScript = @"
#!/bin/bash
# INSTALACIÓN AUTOMÁTICA DESDE GITHUB

echo "🐳 INSTALANDO DESDE GITHUB..."

# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh && sudo sh get-docker.sh

# Instalar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose && sudo chmod +x /usr/local/bin/docker-compose

# Clonar repositorio (cambiar TU-USUARIO por tu usuario de GitHub)
git clone https://github.com/TU-USUARIO/cdd-app.git /var/www/cdd_app

# Desplegar aplicación
cd /var/www/cdd_app
chmod +x deploy_github.sh
./deploy_github.sh

echo "✅ APLICACIÓN DESPLEGADA!"
"@

Set-Content -Path "install_from_github.sh" -Value $installScript
Write-Host "✅ install_from_github.sh creado" -ForegroundColor Green

# Crear paquete para GitHub
Write-Host "4/4 - Creando paquete para GitHub..." -ForegroundColor Cyan

# Crear lista de archivos a excluir
$excludePatterns = @(
    "node_modules",
    "vendor",
    "storage/app/*",
    "storage/framework/*",
    "storage/logs/*",
    "bootstrap/cache/*",
    ".git",
    "*.log",
    ".env*",
    "backup*",
    "__pycache__",
    "*.pyc",
    ".vscode",
    "backup_env",
    "*.zip",
    "backup_*.sql"
)

# Crear comando tar para Windows
$excludeArgs = $excludePatterns | ForEach-Object { "--exclude=$_" }
$excludeString = $excludeArgs -join " "

# Ejecutar tar si está disponible
try {
    # Usar tar si está disponible (Git Bash, WSL, etc.)
    $tarCommand = "tar $excludeString -czf cdd_app_github.tar.gz cdd_app/"
    Invoke-Expression $tarCommand
    Write-Host "✅ Paquete creado: cdd_app_github.tar.gz" -ForegroundColor Green
}
catch {
    Write-Host "❌ Error con tar. Usando alternativa..." -ForegroundColor Red
    # Usar PowerShell para comprimir
    $compressArgs = @{
        Path = "cdd_app\*"
        DestinationPath = "cdd_app_github.zip"
        Force = $true
    }
    Compress-Archive @compressArgs
    Write-Host "✅ Paquete alternativo creado: cdd_app_github.zip" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== PASOS PARA SUBIR A GITHUB ===" -ForegroundColor Yellow
Write-Host ""
Write-Host "1️⃣ Crear repositorio en GitHub:" -ForegroundColor White
Write-Host "   📍 Ir a: https://github.com/new" -ForegroundColor Gray
Write-Host "   📝 Nombre del repositorio: cdd-app" -ForegroundColor Gray
Write-Host "   📝 Descripción: Sistema de gestión Laravel con Docker" -ForegroundColor Gray
Write-Host "   ✅ Marcar 'Add a README file'" -ForegroundColor Gray
Write-Host ""
Write-Host "2️⃣ Subir archivos:" -ForegroundColor White
Write-Host "   💻 Ejecutar estos comandos:" -ForegroundColor Gray
Write-Host ""
Write-Host "   # Crear directorio temporal" -ForegroundColor Gray
Write-Host "   mkdir temp_github" -ForegroundColor Gray
Write-Host "   cd temp_github" -ForegroundColor Gray
Write-Host ""
Write-Host "   # Extraer paquete" -ForegroundColor Gray
Write-Host "   tar -xzf ../cdd_app_github.tar.gz  # O unzip si usaste .zip" -ForegroundColor Gray
Write-Host ""
Write-Host "   # Inicializar git" -ForegroundColor Gray
Write-Host "   git init" -ForegroundColor Gray
Write-Host "   git add ." -ForegroundColor Gray
Write-Host "   git commit -m `"Initial commit - CDD App Laravel`"" -ForegroundColor Gray
Write-Host ""
Write-Host "   # Conectar con GitHub (cambiar TU-USUARIO)" -ForegroundColor Gray
Write-Host "   git remote add origin https://github.com/TU-USUARIO/cdd-app.git" -ForegroundColor Gray
Write-Host "   git branch -M main" -ForegroundColor Gray
Write-Host "   git push -u origin main" -ForegroundColor Gray
Write-Host ""
Write-Host "3️⃣ Desplegar en VPS:" -ForegroundColor White
Write-Host "   🖥️ En el VPS Ubuntu ejecutar:" -ForegroundColor Gray
Write-Host ""
Write-Host "   # Clonar desde GitHub" -ForegroundColor Gray
Write-Host "   git clone https://github.com/TU-USUARIO/cdd-app.git" -ForegroundColor Gray
Write-Host "   cd cdd-app" -ForegroundColor Gray
Write-Host ""
Write-Host "   # Desplegar automáticamente" -ForegroundColor Gray
Write-Host "   ./deploy_github.sh" -ForegroundColor Gray
Write-Host ""

Write-Host "✅ PREPARACIÓN COMPLETADA!" -ForegroundColor Green
Write-Host ""
Write-Host "🎯 VENTAJAS DE ESTE ENFOQUE:" -ForegroundColor Yellow
Write-Host "✅ Despliegue ultra-rápido" -ForegroundColor White
Write-Host "✅ Sin transferencia manual" -ForegroundColor White
Write-Host "✅ Control de versiones completo" -ForegroundColor White
Write-Host "✅ Fácil mantenimiento" -ForegroundColor White
Write-Host "✅ Trabajo colaborativo" -ForegroundColor White

Write-Host ""
Write-Host "🚀 COMANDO FINAL EN VPS:" -ForegroundColor Yellow
Write-Host "git clone https://github.com/TU-USUARIO/cdd-app.git && cd cdd-app && ./deploy_github.sh" -ForegroundColor White

Write-Host ""
Write-Host "¡TODO LISTO PARA DESPLEGAR! 🎉" -ForegroundColor Green