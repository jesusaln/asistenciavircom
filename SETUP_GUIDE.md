# Guía de Configuración del Proyecto - Climas del Desierto

Este documento proporciona instrucciones completas para configurar el entorno de desarrollo.

## 📋 Requisitos Previos

- **PHP 8.2+** con extensiones: pdo_pgsql, mbstring, xml, curl, gd, zip
- **PostgreSQL 14+**
- **Node.js 20+** y **npm 10+**
- **Composer 2.5+**
- **Git**

## 🚀 Inicio Rápido

### 1. Clonar el Repositorio

```bash
git clone https://github.com/tu-org/climasdeldesierto.git
cd climasdeldesierto
```

### 2. Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Generar clave JWT (si aplica)
php artisan jwt:secret
```

### 3. Configurar Base de Datos

Edita el archivo `.env` con tus credenciales de PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=climasdeldesierto
DB_USERNAME=postgres
DB_PASSWORD=tu_password
```

### 4. Instalar Dependencias de PHP

```bash
composer install
```

### 5. Instalar Dependencias de Node.js

```bash
npm install
```

### 6. Configurar Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate --seed

# Opcional: ejecutar migraciones específicas para testing
php artisan migrate --env=testing
```

### 7. Compilar Assets

```bash
# Desarrollo
npm run dev

# Producción
npm run build
```

### 8. Iniciar Servidor de Desarrollo

```bash
# Servidor de Laravel
php artisan serve

# O usar Laravel Sail (Docker)
./vendor/bin/sail up -d
```

## 🔧 Configuración Adicional

### Variables de Entorno Requeridas

```env
# Base de datos
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=climasdeldesierto
DB_USERNAME=postgres
DB_PASSWORD=

# Aplicación
APP_NAME="Climas del Desierto"
APP_URL=http://localhost:8000
APP_ENV=local
APP_DEBUG=true

# Seguridad
APP_KEY=base64:xxxxx

# Email (para notificaciones)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=

# CDN (opcional)
CDN_ENABLED=false
CDN_BASE_URL=
CDN_PROVIDER=local

# APIs Externas
CVA_API_KEY=
CVA_API_SECRET=
FACTURAPI_API_KEY=
```

### Habilitar Extensión pg_trgm (PostgreSQL)

Para búsquedas optimizadas:

```sql
CREATE EXTENSION IF NOT EXISTS pg_trgm;
```

## 🐳 Usando Docker (Laravel Sail)

```bash
# Instalar dependencias de Docker
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs

# Levantar contenedores
./vendor/bin/sail up -d

# Ejecutar migraciones
./vendor/bin/sail artisan migrate --seed

# Compilar assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

## 🧪 Testing

```bash
# Ejecutar tests unitarios
php artisan test

# Tests con coverage
php artisan test --coverage
```

## 📦 Comandos Útiles

```bash
# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Forzar limpieza completa de cache
php artisan cache:force-clear

# Limpiar storage
php artisan storage:cleanup

# Verificar columnas huérfanas
php artisan db:check-orphan-columns

# Versionado de builds
./scripts/version-build.sh --build
./scripts/version-build.sh --rollback

# Verificar estado del sistema
php artisan tinker
>>> app(\App\Services\SystemCheckService::class)->run();
```

## 🔍 Solución de Problemas

### Error de Conexión a DB

```bash
# Verificar que PostgreSQL esté ejecutándose
sudo systemctl status postgresql

# Probar conexión
php artisan db:connect
```

### Error de Permisos

```bash
# Dar permisos a storage y bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R $USER:www-data .
```

### Error de Memoria en npm

```bash
# Aumentar límite de memoria de Node
NODE_OPTIONS="--max-old-space-size=4096" npm run build
```

### Error de Composer

```bash
# Limpiar cache de Composer
composer clear-cache

# Reinstalar dependencias
rm -rf vendor composer.lock
composer install
```

## 📚 Documentación Adicional

- [Guía de Instalación](.gemini/artifacts/guides/INSTALACION.md)
- [Guía de Pruebas Observer](.gemini/artifacts/guides/GUIA_PRUEBAS_OBSERVER.md)
- [Guía de Assets](.gemini/artifacts/guides/ASSETS_GUIDE.md)
- [README Híbrido](.gemini/artifacts/guides/README_HYBRID.md)

## 🤝 Contribuir

1. Fork el repositorio
2. Crea una rama (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crea un Pull Request

## 📄 Licencia

Este proyecto está bajo la licencia MIT.
