# 🚀 Advanced Adaptive Trading Intelligence System

## 📌 Visión General
Este sistema es un simulador de trading de alto rendimiento (Paper Trading) integrado en una plataforma Laravel/Vue. Utiliza Inteligencia Artificial híbrida (Ensemble Learning + Optimización Genética) para analizar el mercado de criptomonedas en tiempo real y aprender de forma persistente.

---

## 🏗️ Arquitectura Técnica Actual

### 1. Frontend (Vue.js 3 + Composition API)
- **Gráfico**: Implementado con `Lightweight Charts` de TradingView.
- **WebSocket**: Conexión directa a Binance (`wss://stream.binance.com:9443`) para datos en tiempo real.
- **Persistencia Local**: Uso de `localStorage` para recordar configuración (Símbolo, Temporalidad, Balance).

### 2. Backend (Laravel 12 + PostgreSQL)
- **Persistencia de Experiencia**: Tabla `trading_experience` que almacena velas y resultados de señales.
- **Background Trainer**: Comando `trading:background-train` vía Cron cada minuto.

---

## ✅ Funcionalidades Implementadas (Lo que HACE)

1. **Ensemble Strategy**: Combina EMA, RSI, BB, MACD y Volumen con pesos adaptativos.
2. **Optimización Genética**: Evoluciona los pesos de la estrategia basándose en datos históricos.
3. **Flash Training**: Ajuste ultrarrápido a la volatilidad de las últimas 4 horas.
4. **HTF Confluence**: Bloqueo de operaciones si la tendencia 4H no está alineada.
5. **Aprendizaje Autónomo**: El servidor recolecta datos y entrena la IA sin necesidad de tener el navegador abierto.
6. **Gestión de Riesgo**: Circuit Breaker tras pérdidas consecutivas y Stop Loss/Take Profit dinámicos basados en ATR.

---

## ⚠️ Limitaciones Actuales (Lo que NO hace)

1. **Ejecución de Órdenes Reales**: No tiene conexión con el motor de órdenes de Binance (solo simulación).
2. **Manejo de Comisiones**: El simulador asume trading sin comisiones (en la realidad Binance cobra entre 0.02% y 0.1%).
3. **Slippage (Deslizamiento)**: Asume que las órdenes se ejecutan al precio exacto de cierre, ignorando la profundidad del libro de órdenes (*Order Book*).
4. **Seguridad de Llaves**: No hay sistema de almacenamiento seguro (Vault/KMS) para API Keys.
5. **Análisis Fundamental**: Ignora noticias externas, eventos macroeconómicos o anuncios de la Fed que pueden invalidar el análisis técnico.

---

## 🌉 Puente de Datos IA (Local <-> Producción)

El sistema cuenta con un puente de datos bidireccional protegido por un Token Secreto (`X-Trading-Token`), permitiendo separar el entorno de recolección (Frontend Local/Navegador) del entorno de entrenamiento (VPS).

### 1. Sincronización a Producción (Local -> VPS)
- El navegador envía cada vela cerrada y señal calculada al VPS usando la ruta:
  `Route::post('/trading/bulk-save-experience')`
- Esto permite al VPS recolectar experiencia 24/7 de forma pasiva.

### 2. Sincronización a Local (VPS -> Local)
- El frontend puede consultar el avance de la IA mediante los endpoints:
  `Route::get('/trading/get-weights')` y `Route::get('/trading/get-history')`
- Para forzar la sincronización local desde la terminal y descargar todo el progreso del servidor:
  ```bash
  php artisan trading:pull-from-prod {symbol} {timeframe}
  ```

---

## 🗺️ Hoja de Ruta para Trading Real (Roadmap a Producción)

### Fase 1: Infraestructura y Seguridad
- [ ] **Encriptación AES-256**: Implementar un sistema para guardar API Keys de forma segura en la base de datos.
- [ ] **2FA (Doble Factor)**: Requerir autenticación para habilitar el trading con dinero real.
- [ ] **Motor de Órdenes Backend**: Crear servicios en Laravel/Node.js que realicen la ejecución real vía REST/WebSocket.

### Fase 2: Realismo del Simulador
- [ ] **Modelado de Slippage**: Añadir un factor de error del 0.05% - 0.1% a cada entrada para simular el mercado real.
- [ ] **Cálculo de Fees**: Restar automáticamente las comisiones de Binance del balance.
- [ ] **Pruebas de Monte Carlo**: Ejecutar simulaciones de 10,000 iteraciones con variables aleatorias para medir la probabilidad de ruina.

### Fase 3: Inteligencia Avanzada
- [ ] **Sentiment Analysis**: Integrar APIs de noticias (LunarCrush, CryptoPanic) para ponderar las señales de la IA según el sentimiento del mercado.
- [ ] **Arbitraje Interno**: Comparar precios entre diferentes temporalidades para detectar micro-divergencias.
- [ ] **Sistema de Alertas**: Notificaciones vía Telegram/WhatsApp sobre operaciones y estado del servidor.

---

## 🛠️ Ciclo de Vida del Despliegue y Caché (PWA)

### 1. Gestión de Caché (Cache-Busting)
Para evitar que el navegador use lógica de IA obsoleta, el sistema implementa una **Bomba de Caché** en `app.js`.
- **APP_VERSION**: Cada cambio mayor en la IA debe ir acompañado de un incremento en la constante `APP_VERSION`.
- **Efecto**: Si hay desajuste de versión, el sistema desregistra el Service Worker, limpia la caché de activos y fuerza un `window.location.reload()`.

### 2. Resolución de Problemas (Troubleshooting)
- **Error 502 (Bad Gateway)**: Ocurre si Nginx pierde el rastro del contenedor de la aplicación tras un despliegue. Solución: `docker restart climasdeldesierto-web-1`.
- **Error 404 (Assets)**: El Service Worker busca archivos con hashes viejos. Solución: Forzar limpieza de caché desde el panel de Aplicación del navegador o mediante el script de autolimpieza.

### 3. Procedimiento de Despliegue Seguro
1. Ejecutar `./deploy.sh`.
2. Verificar que el contenedor de la App esté `Up`.
3. Ejecutar migraciones si hay cambios en `trading_experience`.
4. Verificar logs: `docker exec climasdeldesierto-app-1 tail -f storage/logs/laravel.log`.

---

## 🚨 Advertencia Crítica para IAs Futuras
El paso de simulación a dinero real es el punto de mayor riesgo. **NUNCA** se debe habilitar el trading real sin haber pasado por al menos 30 días de rentabilidad consistente en el simulador con un *Drawdown* máximo controlado. La IA debe ser validada contra periodos de alta volatilidad y mercados laterales antes de manejar capital del usuario.

