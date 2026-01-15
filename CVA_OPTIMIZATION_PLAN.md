# Plan de Optimización CVA y Estrategia Comercial

Este documento detalla las fases para perfeccionar la integración con CVA, mejorar el rendimiento del sistema y maximizar las ventas.

## 📊 Propuesta de Márgenes Actualizada (Agresiva + Servicio)

Hemos ajustado los márgenes para reflejar el valor añadido de tu servicio y protección del negocio. Ya no competimos solo por precio.

| Rango de Precio (Costo Base) | Margen Nuevo | Ejemplo (Costo -> Venta) | Ganancia Bruta | Análisis |
| :--- | :--- | :--- | :--- | :--- |
| **$0 - $500 MXN** | **50%** | $100 -> $150 | $50 | ✅ **Indispensable.** Cables y adaptadores deben subsidiar la operación. |
| **$501 - $1,500 MXN** | **35%** | $1,000 -> $1,350 | $350 | ✅ **Excelente.** Teclados y mouses de buena gama. |
| **$1,501 - $4,000 MXN** | **25%** | $3,000 -> $3,750 | $750 | 🆗 **Sólido.** Monitores. |
| **$4,001 - $8,000 MXN** | **20%** | $6,000 -> $7,200 | $1,200 | 🆗 **Estándar.** Componentes clave. |
| **$8,001 - $15,000 MXN** | **15%** | $12,000 -> $13,800 | $1,800 | ⚠️ **Saludable.** Laptops de hogar. |
| **$15,001 - $30,000 MXN** | **12%** | $25,000 -> $28,000 | $3,000 | ⚠️ **Competitivo.** Laptops Gamer. |
| **$30,001 - $60,000 MXN** | **10%** | $50,000 -> $55,000 | $5,000 | 🔥 **Especializado.** Workstations. |
| **Más de $60,000 MXN** | **8%** | $100,000 -> $108,000 | $8,000 | 💼 **Proyectos.** Infraestructura. |

---

## � Esquema de Comisiones para Vendedores

**RECOMENDACIÓN CRÍTICA:** Paga comisiones sobre la **UTILIDAD (GANANCIA)**, nunca sobre la venta total.
*   *Razón:* En tecnología los márgenes varían mucho. Si pagas sobre venta total, perderás dinero en laptops caras y pagarás miserias en cables baratos.

### Propuesta de Porcentajes (Sobre Utilidad Bruta)

**1. Vendedor Junior / De Piso:**
*   **Comisión:** **10%** de la utilidad.
*   *Ejemplo Laptop Gamer ($3,000 ganancia):* Se lleva **$300**.
*   *Ejemplo Cable HDMI ($50 ganancia):* Se lleva **$5**.

**2. Vendedor Senior / Ejecutivo de Cuenta:**
*   **Comisión:** **15%** de la utilidad.
*   *Motivo:* Cierra proyectos, busca clientes fuera, hace seguimiento.
*   *Ejemplo Proyecto Servidor ($8,000 ganancia):* Se lleva **$1,200**.

**3. Gerente Comercial / Socio:**
*   **Comisión:** **20% - 25%** de la utilidad.

### ¿Cómo calcularlo fácil?
El sistema ya tiene el `precio_compra` y `precio_venta`.
`Utilidad = Precio Venta (Sin IVA) - Precio Compra (Sin IVA)`
`Comisión = Utilidad * 0.15` (Para Senior).

---

## 🚀 Fases de Optimización Técnica

### Fase 1: Inmediata (Correcciones y Ajustes) ✅ COMPLETADA
*   **Configuración de Envío Local:** Ya es dinámico y configurable en BD.
*   **Márgenes Actualizados:** Código ajustado con la nueva tabla agresiva.

### Fase 2: Experiencia de Usuario (UX) ✅ COMPLETADA
*   **Stock "En Tránsito":** Implementado. Ahora se muestra "Próximamente" y permite pre-venta.
*   **Visualización:** Badges de colores y lógica de botones actualizada.

### Fase 3: Automatización Avanzada ✅ COMPLETADA
*   **Sincronización de Pedidos:** Comando `cva:sync-orders` creado.
*   **Rastreo:** El sistema busca guías automáticamente en CVA cada hora.

---

## 🛠️ Instrucciones de Despliegue Final

Para aplicar todos estos cambios (Márgenes, UX, Sync) en producción:

1.  **Git Push:**
    ```bash
    git add .
    git commit -m "Márgenes agresivos, UX de tránsito y sincronización de pedidos"
    git push origin main
    ```

2.  **Deploy en VPS:**
    ```bash
    ssh root@191.101.233.82
    cd /var/www/asistenciavircom
    git pull origin main
    composer install --optimize-autoloader --no-dev
    php artisan migrate --force
    php artisan config:cache
    # Reiniciar colas y programador si es necesario
    php artisan queue:restart
    npm run build
    ```
