# Script de respaldo para Windows con PostgreSQL
# Uso: .\backup_windows.ps1

Write-Host "=== CREANDO RESPALDO DE PRODUCCIÓN (Windows) ===" -ForegroundColor Green
Write-Host "Fecha: $(Get-Date)" -ForegroundColor Yellow

# Variables de configuración
$dbName = "cdd_app_prod"
$dbUser = "cdd_user"
# Unificar destino con Laravel: storage/app/backups/database
$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$backupDir = Join-Path $projectRoot "storage\app\backups\database"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

# Crear directorio de respaldos si no existe
if (!(Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir | Out-Null
}

$backupFile = Join-Path $backupDir "backup_migracion_$timestamp.sql"

Write-Host "Creando respaldo de base de datos..." -ForegroundColor Cyan

# Crear respaldo usando pg_dump (debe estar en el PATH)
try {
    & pg_dump -h localhost -U $dbUser -d $dbName -F c -f $backupFile
    Write-Host "✅ Respaldo creado exitosamente: $backupFile" -ForegroundColor Green

    # Verificar tamaño del archivo
    $fileSize = (Get-Item $backupFile).Length / 1MB
    Write-Host "📦 Tamaño del respaldo: $([math]::Round($fileSize, 2)) MB" -ForegroundColor Yellow
}
catch {
    Write-Host "❌ Error al crear respaldo: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Verifica que PostgreSQL esté instalado y pg_dump esté en el PATH" -ForegroundColor Red
    exit 1
}

# Crear paquete de aplicación
$appPackage = Join-Path $projectRoot "cdd_app_migracion_$timestamp.tar.gz"

Write-Host "Creando paquete de aplicación..." -ForegroundColor Cyan

try {
    # Usar tar si está disponible (Git Bash, WSL, o instalar tar para Windows)
    if (Get-Command tar -ErrorAction SilentlyContinue) {
        tar -czf $appPackage `
            --exclude=node_modules `
            --exclude=.git `
            --exclude=backup_env `
            --exclude=*.log `
            --exclude=.env.local* `
            --exclude=backup* `
            "cdd_app"
    }
    else {
        Write-Host "Tar no encontrado. Instalando alternativa..." -ForegroundColor Yellow
        # Usar PowerShell para comprimir
        Compress-Archive -Path "cdd_app\*" -DestinationPath $appPackage -CompressionLevel Optimal
    }

    $packageSize = (Get-Item $appPackage).Length / 1MB
    Write-Host "✅ Paquete creado: $([math]::Round($packageSize, 2)) MB" -ForegroundColor Green
}
catch {
    Write-Host "❌ Error al crear paquete: $($_.Exception.Message)" -ForegroundColor Red
}

# Crear configuración de producción
$envProd = "cdd_app\.env.produccion"
Copy-Item "cdd_app\.env" $envProd

Write-Host "✅ Archivo de configuración de producción creado: $envProd" -ForegroundColor Green

# Mostrar resumen
Write-Host ""
Write-Host "=== RESUMEN DE RESPALDO ===" -ForegroundColor Cyan
Write-Host "📦 Base de datos: $backupFile" -ForegroundColor White
Write-Host "📦 Aplicación: $appPackage" -ForegroundColor White
Write-Host "📦 Configuración: $envProd" -ForegroundColor White
Write-Host ""
Write-Host "=== PRÓXIMOS PASOS ===" -ForegroundColor Yellow
Write-Host "1. Transferir archivos al VPS Ubuntu" -ForegroundColor White
Write-Host "2. Ejecutar instalación automática en VPS" -ForegroundColor White
Write-Host "3. Restaurar base de datos en VPS" -ForegroundColor White
Write-Host ""
Write-Host "✅ RESPALDO COMPLETADO EN WINDOWS" -ForegroundColor Green
