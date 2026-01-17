<?php

use App\Models\PlanPoliza;
use App\Models\PolizaServicio;

// 1. Definir Cláusulas Estándar
$clausulasEstandar = "1. OBJETO DEL SERVICIO: VIRCOM brindará soporte técnico preventivo y correctivo según los límites del plan contratado. El servicio incluye asistencia remota y visitas presenciales programadas.\n\n" .
    "2. TIEMPOS DE RESPUESTA (SLA): El tiempo de atención estimado para tickets de soporte es de 4 a 8 horas hábiles. Las emergencias críticas que afecten la operación total serán priorizadas.\n\n" .
    "3. EXCLUSIONES: No se incluye el costo de refacciones de hardware, licencias de software comercial de terceros, ni reparaciones por daños físicos provocados por variaciones de voltaje, líquidos o mal uso.\n\n" .
    "4. CONFIDENCIALIDAD: Toda la información técnica y comercial del CLIENTE será tratada con estricta confidencialidad por el personal de VIRCOM.\n\n" .
    "5. VIGENCIA Y CANCELACIÓN: Este contrato tiene una vigencia mensual con renovación automática. Para cancelaciones, se requiere aviso previo de 15 días naturales.";

$terminosPagoEstandar = "El pago mensual deberá realizarse de forma anticipada dentro de los primeros 5 días naturales del mes vía transferencia electrónica o depósito bancario.";

// 2. Definir Beneficios Reales de Judith (Plan Pyme Tech)
$beneficiosJudith = [
    "Soporte Técnico Remoto (Software, Impresoras, Redes)",
    "1 Visita Presencial Mensual Programada",
    "Asesoría Telefónica y WhatsApp Ilimitada",
    "Optimización y Limpieza Periódica de Equipos",
    "Gestión de Garantías de Equipos VIRCOM",
    "Instalación de Actualizaciones Críticas de Seguridad"
];

// 3. Actualizar el Plan de Judith
$planJudith = PlanPoliza::where('nombre', 'like', '%Judith%')->first();
if ($planJudith) {
    $planJudith->update([
        'beneficios' => $beneficiosJudith,
        'clausulas' => $clausulasEstandar,
        'terminos_pago' => $terminosPagoEstandar,
        'visitas_sitio_mensuales' => 1,
        'costo_visita_sitio_extra' => 350.00
    ]);
    echo "Plan de Judith actualizado con beneficios reales y cláusulas.\n";
} else {
    echo "No se encontró el Plan de Judith.\n";
}

// 4. Actualizar todos los demás planes con cláusulas estándar si están vacías
$planesActivos = PlanPoliza::whereNull('clausulas')->orWhere('clausulas', '')->get();
foreach ($planesActivos as $plan) {
    $plan->update([
        'clausulas' => $clausulasEstandar,
        'terminos_pago' => $terminosPagoEstandar
    ]);
}
echo "Se actualizaron " . $planesActivos->count() . " planes adicionales con cláusulas estándar.\n";
