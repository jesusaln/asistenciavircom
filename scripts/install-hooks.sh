#!/bin/bash
# Instala los git hooks del proyecto en .git/hooks/
# Ejecutar UNA VEZ después de clonar el repo:
#   bash scripts/install-hooks.sh
#
# Esto configura el pre-commit hook que valida el fix de broadcasting
# antes de cada commit.

set -e

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOOKS_DIR="$REPO_ROOT/.git/hooks"
SOURCE_DIR="$REPO_ROOT/scripts/git-hooks"

echo "🔧 Instalando git hooks..."

if [ ! -d "$HOOKS_DIR" ]; then
    echo "❌ Error: no se encontró .git/hooks/"
    echo "   ¿Estás en la raíz del repo?"
    exit 1
fi

if [ ! -d "$SOURCE_DIR" ]; then
    echo "❌ Error: no se encontró $SOURCE_DIR"
    exit 1
fi

# Instalar/symlink cada hook
for hook_file in "$SOURCE_DIR"/*; do
    hook_name=$(basename "$hook_file")
    target="$HOOKS_DIR/$hook_name"

    # Si ya existe y NO es un symlink, hacer backup
    if [ -f "$target" ] && [ ! -L "$target" ]; then
        backup="$target.user-backup-$(date +%s)"
        echo "  ⚠ Backup de hook existente: $target → $backup"
        mv "$target" "$backup"
    fi

    # Crear symlink
    ln -sf "../../scripts/git-hooks/$hook_name" "$target"
    chmod +x "$hook_file"
    echo "  ✓ $hook_name instalado"
done

echo ""
echo "✅ Git hooks instalados correctamente"
echo ""
echo "Los siguientes archivos se validan antes de cada commit:"
echo "  - composer.json / composer.lock"
echo "  - app/Providers/AppServiceProvider.php"
echo "  - config/broadcasting.php / config/reverb.php"
echo "  - tests/Feature/BroadcastingReverbTest.php"
echo "  - scripts/verify-broadcast.php"
echo "  - docs/broadcasting-reverb.md"
echo ""
echo "Para saltar el hook (emergencias):"
echo "  git commit --no-verify"
echo ""
echo "Para desinstalar:"
echo "  rm .git/hooks/pre-commit"
