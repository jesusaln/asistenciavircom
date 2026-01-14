#!/bin/bash
# Script para restaurar configuraciones de Nginx perdidas

# 1. Configuración de n8n (Puerto 5678)
cat > /etc/nginx/sites-available/n8n.asistenciavircom.com << 'EOF'
server {
    listen 80;
    server_name n8n.asistenciavircom.com;

    location / {
        proxy_pass http://127.0.0.1:5678;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Websockets para n8n
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
EOF

# 2. Configuración de Portainer (Puerto 9000 - HTTP)
cat > /etc/nginx/sites-available/portainer.asistenciavircom.com << 'EOF'
server {
    listen 80;
    server_name portainer.asistenciavircom.com;

    location / {
        proxy_pass http://127.0.0.1:9000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF

# 3. Configuración de Nginx Admin (Puerto 81 - Asumiendo Proxy Manager o similar, o redirección)
# Ojo: ngnix.asistenciavircom.com es confuso. Si es Nginx Proxy Manager suele ser puerto 81.
# Si era la página default, apuntamos a HTML estático.
cat > /etc/nginx/sites-available/ngnix.asistenciavircom.com << 'EOF'
server {
    listen 80;
    server_name ngnix.asistenciavircom.com;

    # Si tienes NPM en puerto 81:
    location / {
        proxy_pass http://127.0.0.1:81;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF

# Activar sitios
ln -sf /etc/nginx/sites-available/n8n.asistenciavircom.com /etc/nginx/sites-enabled/
ln -sf /etc/nginx/sites-available/portainer.asistenciavircom.com /etc/nginx/sites-enabled/
ln -sf /etc/nginx/sites-available/ngnix.asistenciavircom.com /etc/nginx/sites-enabled/

# Verificar y recargar
echo "Verificando sintaxis..."
nginx -t

echo "Recargando Nginx..."
systemctl reload nginx

echo "¡Sitios restaurados!"
