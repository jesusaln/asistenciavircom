# 📄 Estrategia y Control de Pólizas - Vircom 2026

Este documento resume la lógica de negocio y configuraciones implementadas para maximizar la rentabilidad del módulo de Pólizas en Hermosillo, Sonora.

## 1. 🛡️ Protección de Rentabilidad (El Caso del Servidor)
**Pregunta:** *¿Si cobro $1,000 al mes por el servidor pero tardo 5 horas en arreglarlo, qué pasa?*

**Lógica Implementada:**
*   **La Póliza no es "Horas Ilimitadas":** El pago mensual ($1,000) es por **disponibilidad y monitoreo preventivo**.
*   **Exclusión del Banco de Horas:** Los servicios especializados (Servidores, CONTPAQi avanzado) se configuran para **NO** consumir las horas de la póliza básica ($1,500).
*   **Cobro de Excedente:** Si arreglarlo te tomó 5 horas:
    *   Si está excluido: Cobras **$1,000 (mensualidad) + 5 horas x $500 (tarifa preferente)** = **$3,500 MXN**.
    *   Si el cliente a fuerza quiere que incluya horas, su **mensualidad debe subir** (usando el Asistente Pro) para cubrir ese riesgo.

## 2. ⚡ Herramientas Creadas
### A. Administrador: Asistente de Precios Pro (Wizard)
Ubicado en la edición de Planes de Póliza. Permite:
*   Calcular precio mensual según el número de PCs (Escala: $250/PC PyME, $200/PC Corporativo).
*   Sumar cargos automáticos por especialidad (Sargos de seguridad) si se incluyen Servidores o CONTPAQi.
*   Generar automáticamente la lista de beneficios para el catálogo.

### B. Público: Simulador de Costos
Ubicado en el catálogo público para que el cliente:
*   Vea transparencia en los precios.
*   Se auto-califique (si tiene 20 PCs, el sistema no le ofrece la Mini de $1,500).
*   Aumente el valor del contrato al añadir "Add-ons" (CCTV, Redes, etc.).

### C. Servicios de Prepago (Anti-Fraude)
Se crearon en el catálogo de servicios para clientes de poca confianza o eventuales:
*   **1 Hora de Soporte (Prepago):** $650 MXN.
*   **Paquete 10 Horas (Prepago):** $5,850 MXN (10% de descuento / 1 hora gratis).
*   *Regla:* No se inicia el soporte hasta que el sistema detecte el pago.

## 3. 📊 Estándares Configurados (Hermosillo, Sonora)
*   **Hora vircom (Normal):** $650 MXN.
*   **Hora Póliza (Preferente):** $500 MXN.
*   **Póliza Mini:** $1,500 MXN (Límite 5 PCs / 3 Horas incl. / Solo soporte básico).
*   **Deducción Fiscal:** Recordar siempre al cliente que es **100% deducible de ISR**.

---
*Documento generado por Antigravity para Asistencia Vircom.*
