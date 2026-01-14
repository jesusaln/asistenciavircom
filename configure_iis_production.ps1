# Script de PowerShell para configurar IIS para producción Laravel
# Ejecutar como Administrador

Import-Module WebAdministration

Write-Host "=== CONFIGURACIÓN IIS PARA PRODUCCIÓN ===" -ForegroundColor Green

try {
    # Crear grupo de aplicaciones si no existe
    $appPoolName = "cdd_app"
    $siteName = "cdd_app"
    $sitePath = "C:\inetpub\wwwroot\cdd_app"

    if (!(Test-Path IIS:\AppPools\$appPoolName)) {
        Write-Host "Creando grupo de aplicaciones: $appPoolName" -ForegroundColor Yellow
        New-Item IIS:\AppPools\$appPoolName
        Set-ItemProperty IIS:\AppPools\$appPoolName -name processModel.identityType -value 2
        Set-ItemProperty IIS:\AppPools\$appPoolName -name recycling.periodicRestart.time -value '00:00:00'
        Set-ItemProperty IIS:\AppPools\$appPoolName -name recycling.periodicRestart.requests -value 10000
        Write-Host "✅ Grupo de aplicaciones creado" -ForegroundColor Green
    } else {
        Write-Host "✅ Grupo de aplicaciones ya existe" -ForegroundColor Green
    }

    # Crear sitio web si no existe
    if (!(Test-Path IIS:\Sites\$siteName)) {
        Write-Host "Creando sitio web: $siteName" -ForegroundColor Yellow
        New-Website -Name $siteName -PhysicalPath $sitePath -Port 80 -ApplicationPool $appPoolName -Force
        Write-Host "✅ Sitio web creado" -ForegroundColor Green
    } else {
        Write-Host "✅ Sitio web ya existe" -ForegroundColor Green
    }

    # Configurar controlador PHP para el sitio específico
    Write-Host "Configurando controlador PHP..." -ForegroundColor Yellow

    # Remover configuración existente si existe
    Remove-WebHandler -Name 'PHP_via_FastCGI' -PSPath "MACHINE/WEBROOT/APPHOST/$siteName" -ErrorAction SilentlyContinue

    # Agregar controlador PHP
    Add-WebHandler -Name 'PHP_via_FastCGI' -Path '*.php' -Verb '*' -Modules 'FastCgiModule' -ScriptProcessor 'C:\php84\php-cgi.exe' -PSPath "MACHINE/WEBROOT/APPHOST/$siteName" -ErrorAction SilentlyContinue
    Write-Host "✅ Controlador PHP configurado" -ForegroundColor Green

    # Configurar documento predeterminado
    Write-Host "Configurando documento predeterminado..." -ForegroundColor Yellow

    # Remover documentos predeterminados existentes
    Clear-WebConfiguration -Filter '/system.webServer/defaultDocument/files' -PSPath "MACHINE/WEBROOT/APPHOST/$siteName" -ErrorAction SilentlyContinue

    # Agregar index.php como documento predeterminado
    Add-WebConfiguration -Filter '/system.webServer/defaultDocument/files' -PSPath "MACHINE/WEBROOT/APPHOST/$siteName" -Value @{value='index.php'}
    Write-Host "✅ Documento predeterminado configurado" -ForegroundColor Green

    # Configurar límites de ejecución
    Write-Host "Configurando límites de ejecución..." -ForegroundColor Yellow
    Set-WebConfiguration -Filter '/system.webServer/security/requestFiltering/requestLimits' -PSPath "MACHINE/WEBROOT/APPHOST/$siteName" -Value @{maxAllowedContentLength=104857600}
    Write-Host "✅ Límites configurados" -ForegroundColor Green

    Write-Host ""
    Write-Host "=== CONFIGURACIÓN COMPLETADA ===" -ForegroundColor Green
    Write-Host ""
    Write-Host "Sitio web disponible en:" -ForegroundColor Cyan
    Write-Host "  http://localhost/" -ForegroundColor White
    Write-Host "  http://127.0.0.1/" -ForegroundColor White
    Write-Host ""
    Write-Host "Grupo de aplicaciones: $appPoolName" -ForegroundColor Cyan
    Write-Host "Ruta física: $sitePath" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Para aplicar cambios, reiniciar IIS:" -ForegroundColor Yellow
    Write-Host "  iisreset" -ForegroundColor White

} catch {
    Write-Host "❌ Error durante la configuración:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}