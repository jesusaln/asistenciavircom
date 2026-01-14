#!/bin/bash

# Script para generar certificados SSL auto-firmados
# Uso: ./docker/generate_ssl.sh [dominio]

set -e

DOMAIN=${1:-portainer.asistenciavircom.com}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "🔐 Generando certificados SSL para $DOMAIN..."
echo "📂 Directorio: $SCRIPT_DIR"

# Crear directorio para certificados
SSL_DIR="$SCRIPT_DIR/ssl"
mkdir -p "$SSL_DIR"

if [ -f "$SSL_DIR/cert.pem" ] && [ -f "$SSL_DIR/key.pem" ]; then
    echo "⚠️ Los certificados ya existen. ¿Deseas reemplazarlos? (y/N)"
    read -r respuesta
    if [[ ! "$respuesta" =~ ^[Yy]$ ]]; then
        echo "✅ Usando certificados existentes"
        exit 0
    fi
fi

echo "🔧 Generando clave privada..."
openssl genrsa -out "$SSL_DIR/key.pem" 2048

echo "📝 Creando solicitud de certificado..."
openssl req -new -key "$SSL_DIR/key.pem" -out "$SSL_DIR/csr.pem" -subj "/C=MX/ST=Sonora/L=Hermosillo/O=Asistencia Vircom/CN=$DOMAIN"

echo "🔐 Generando certificado auto-firmado (válido por 365 días)..."
openssl x509 -req -days 365 -in "$SSL_DIR/csr.pem" -signkey "$SSL_DIR/key.pem" -out "$SSL_DIR/cert.pem"

echo "🧹 Limpiando archivos temporales..."
rm -f "$SSL_DIR/csr.pem"

echo "✅ ¡Certificados SSL generados exitosamente!"
echo ""
echo "📋 Información del certificado:"
openssl x509 -in "$SSL_DIR/cert.pem" -text -noout | grep -E "(Subject:|Issuer:|Not Before|Not After)"

echo ""
echo "📁 Ubicación de los certificados:"
echo "   • Certificado: $SSL_DIR/cert.pem"
echo "   • Clave privada: $SSL_DIR/key.pem"
echo ""
echo "🔧 Para usar HTTPS en producción con Portainer:"
echo "   1. Los certificados están listos en: $SSL_DIR/"
echo "   2. Para producción real, considera usar Let's Encrypt con Traefik"
echo "   3. Configura el dominio $DOMAIN para apuntar a tu servidor"
echo ""
echo "📋 Configuración recomendada para Portainer:"
echo "   • Usa las etiquetas de Traefik en docker-compose.yml"
echo "   • Configura DNS para apuntar a tu servidor"
echo "   • Considera usar Cloudflare para protección adicional"
echo ""
echo "⚠️ Nota: Para producción real, considera usar Let's Encrypt"
echo "   o certificados de una autoridad certificadora reconocida."

# Configurar permisos seguros
chmod 600 "$SSL_DIR/key.pem"
chmod 644 "$SSL_DIR/cert.pem"