#!/bin/bash

# Script para configurar Git y subir a repositorio
# Uso: ./docker/setup_git.sh [github|gitlab|bitbucket]

set -e

PLATFORM=${1:-github}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "🚀 Configurando Git para $PLATFORM..."
echo "📂 Proyecto: $PROJECT_ROOT"

cd "$PROJECT_ROOT"

# Verificar si ya existe .git
if [ -d ".git" ]; then
    echo "📁 Repositorio Git ya existe"
else
    echo "🔧 Inicializando repositorio Git..."
    git init
    git add .
    git commit -m "Initial commit: Climas del Desierto - Docker stack for Portainer"
fi

echo ""
echo "📋 Pasos para crear repositorio en $PLATFORM:"
echo ""

if [ "$PLATFORM" = "github" ]; then
    echo "🌐 GITHUB:"
    echo "1. Ve a: https://github.com/new"
    echo "2. Repository name: cdd-app o climas-del-desierto"
    echo "3. Description: Aplicación Climas del Desierto - Laravel + Docker"
    echo "4. Choose: Public o Private (recomiendo Private para producción)"
    echo "5. NO marques: 'Add a README file' (ya tenemos código)"
    echo "6. Crea el repositorio"
    echo "7. Copia la URL del repositorio"
    echo ""
    echo "📋 Luego ejecuta:"
    echo "git remote add origin [URL_DEL_REPOSITORIO]"
    echo "git branch -M main"
    echo "git push -u origin main"

elif [ "$PLATFORM" = "gitlab" ]; then
    echo "🌐 GITLAB:"
    echo "1. Ve a: https://gitlab.com/projects/new"
    echo "2. Project name: cdd-app"
    echo "3. Project description: Aplicación Climas del Desierto"
    echo "4. Visibility Level: Private"
    echo "5. Crea el proyecto"
    echo "6. Copia la URL del repositorio"
    echo ""
    echo "📋 Luego ejecuta:"
    echo "git remote add origin [URL_DEL_REPOSITORIO]"
    echo "git branch -M main"
    echo "git push -u origin main"

elif [ "$PLATFORM" = "bitbucket" ]; then
    echo "🌐 BITBUCKET:"
    echo "1. Ve a: https://bitbucket.org/repo/create"
    echo "2. Repository name: cdd-app"
    echo "3. Access level: Private"
    echo "4. Crea el repositorio"
    echo "5. Copia la URL del repositorio"
    echo ""
    echo "📋 Luego ejecuta:"
    echo "git remote add origin [URL_DEL_REPOSITORIO]"
    echo "git branch -M main"
    echo "git push -u origin main"
fi

echo ""
echo "💡 EJEMPLOS DE URLS:"
echo ""
echo "GitHub:     https://github.com/tuusuario/cdd-app.git"
echo "GitLab:     https://gitlab.com/tuusuario/cdd-app.git"
echo "Bitbucket:  https://tuusuario@bitbucket.org/tuusuario/cdd-app.git"
echo ""
echo "🔧 COMANDOS ÚTILES:"
echo "   • Ver estado: git status"
echo "   • Ver remote: git remote -v"
echo "   • Hacer push: git push"
echo "   • Hacer pull: git pull"
echo ""
echo "⚠️ IMPORTANTE:"
echo "   • Nunca subas archivos .env con datos reales"
echo "   • El archivo .env.production es seguro para subir"
echo "   • Usa .gitignore para excluir archivos sensibles"