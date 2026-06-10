#!/bin/bash

# Script para generar APP_KEY válida para Laravel
# Uso: ./docker/generate_app_key.sh

set -e

echo "🔑 Generando APP_KEY válida para Laravel..."

# Verificar si tenemos PHP disponible
if ! command -v php &> /dev/null; then
    echo "❌ PHP no está disponible. Instalando php-cli..."
    apt-get update && apt-get install -y php-cli
fi

# Generar APP_KEY usando el comando estándar de Laravel
echo "🔧 Generando nueva APP_KEY..."
NEW_APP_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")

echo ""
echo "✅ APP_KEY generada exitosamente!"
echo ""
echo "📋 Nueva APP_KEY:"
echo "$NEW_APP_KEY"
echo ""
echo "💡 Copia esta key y úsala en tu archivo .env:"
echo "APP_KEY=$NEW_APP_KEY"
echo ""
echo "📝 Esta key es:"
echo "   • ✅ Válida para Laravel"
echo "   • ✅ Única y segura"
echo "   • ✅ En formato correcto base64"
echo ""
echo "🔒 IMPORTANTE: Usa esta misma key en todos tus entornos"
echo "   para mantener las sesiones y datos encriptados consistentes."

# Si se ejecuta desde el directorio del proyecto, ofrecer actualizar archivos
if [ -f ".env.production" ]; then
    echo ""
    read -p "¿Deseas actualizar .env.production con esta nueva key? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        # Crear respaldo del archivo original
        cp .env.production .env.production.backup.$(date +%Y%m%d_%H%M%S)

        # Actualizar la APP_KEY en el archivo
        sed -i.bak "s/APP_KEY=.*/APP_KEY=$NEW_APP_KEY/" .env.production

        echo "✅ Archivo .env.production actualizado"
        echo "📁 Respaldo creado: .env.production.backup.$(date +%Y%m%d_%H%M%S)"
    fi
fi

echo ""
echo "🎯 Key generada lista para usar!"