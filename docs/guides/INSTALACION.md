# Guía de Instalación - CDD Sistema de Gestión

## Requisitos del Sistema

### Software Requerido
- **PHP** 8.1 o superior
- **PostgreSQL** 14+ (o MySQL 8+)
- **Composer** 2.x
- **Node.js** 18+ y npm
- **Git**

### Extensiones PHP Requeridas
```
php-pgsql, php-mbstring, php-xml, php-curl, php-zip, php-gd, php-bcmath
```

---

## Instalación Paso a Paso

### 1. Clonar el Repositorio
```bash
git clone https://github.com/tu-usuario/cdd_app.git
cd cdd_app
```

### 2. Instalar Dependencias
```bash
# Dependencias PHP
composer install --optimize-autoloader --no-dev

# Dependencias JavaScript
npm install
npm run build
```

### 3. Configurar Variables de Entorno
```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con tus valores:
```env
APP_NAME="Tu Empresa"
APP_URL=https://tu-dominio.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cdd
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 4. Configurar Base de Datos
```bash
# Crear tablas
php artisan migrate

# (Opcional) Datos de prueba
php artisan db:seed
```

### 5. Configurar Almacenamiento
```bash
php artisan storage:link
```

### 6. Permisos de Carpetas (Linux/Mac)
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Configuración del Servidor Web

### Nginx (Recomendado)
```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/cdd_app/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Apache
Asegúrate de que `mod_rewrite` esté habilitado:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### IIS (Windows)
El archivo `web.config` ya está incluido en el proyecto.

---

## Primer Acceso

1. Accede a `https://tu-dominio.com`
2. Crea el primer usuario administrador
3. Ve a **Configuración > Empresa** para personalizar

---

## Tareas Programadas (Cron)

```bash
* * * * * cd /ruta/a/cdd_app && php artisan schedule:run >> /dev/null 2>&1
```

---

## Comandos Útiles

| Comando | Descripción |
|---------|-------------|
| `php artisan cache:clear` | Limpiar caché |
| `php artisan config:cache` | Cachear configuración |
| `php artisan migrate:status` | Ver estado de migraciones |
| `php artisan backup:run` | Crear respaldo manual |
