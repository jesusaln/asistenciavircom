# Guía de Assets - Desarrollo vs Producción

## 🎯 Comportamiento Actual

La aplicación **siempre usa archivos compilados** si existen, incluso si `npm run dev` está corriendo.

### Ventajas:
- ✅ No hay problemas con bloqueadores de anuncios
- ✅ Más rápido (no necesita Vite dev server)
- ✅ Funciona igual en todos los navegadores

## 🔧 Para Desarrollo Normal

```bash
# Compila los assets una vez
npm run build

# La aplicación usará estos archivos compilados
# No necesitas npm run dev corriendo
```

## 🔥 Para Desarrollo con Hot Reload (Opcional)

Si necesitas que los cambios se reflejen automáticamente:

```bash
# 1. Elimina los archivos compilados
rm -rf public/build
# En Windows PowerShell:
Remove-Item -Recurse -Force public\build

# 2. Inicia el servidor de desarrollo
npm run dev

# 3. Ahora los cambios se actualizan automáticamente
```

**Importante:** Cuando termines el desarrollo con hot reload:
```bash
# Detén npm run dev (Ctrl+C)

# Compila para producción
npm run build
```

## 📦 Para Producción

```bash
# Compila los assets optimizados
npm run build

# Sube los archivos de public/build/ al servidor
```

## ⚠️ Prevención de Errores

El archivo `vite.config.js` tiene una protección:

```javascript
if (process.env.APP_ENV === 'production') {
  throw new Error('❌ NO EJECUTES npm run dev EN PRODUCCIÓN');
}
```

Esto previene ejecutar `npm run dev` en producción accidentalmente.

## 🔍 Verificar qué está usando la aplicación

Abre el navegador y ve a:
- **DevTools → Network**
- Busca `app.js`
- Si carga desde `/build/assets/app-[hash].js` → Usando compilados ✅
- Si carga desde `localhost:5173` → Usando dev server 🔥

## 📝 Resumen

| Escenario | Comando | Resultado |
|---|---|---|
| Desarrollo normal | `npm run build` | Archivos compilados, sin hot reload |
| Desarrollo activo | `rm -rf public/build && npm run dev` | Hot reload activo |
| Producción | `npm run build` | Archivos optimizados |

## ❓ Preguntas Frecuentes

**P: ¿Por qué no usar siempre `npm run dev`?**
R: Porque los bloqueadores de anuncios bloquean `localhost:5173` y causa errores.

**P: ¿Los cambios se reflejan sin `npm run dev`?**
R: No automáticamente. Debes ejecutar `npm run build` después de cada cambio.

**P: ¿Cuándo usar hot reload?**
R: Solo cuando estés haciendo muchos cambios de CSS/JS y quieras ver resultados inmediatos.

**P: ¿Qué pasa si olvido hacer `npm run build`?**
R: Los cambios no se verán reflejados en el navegador.
