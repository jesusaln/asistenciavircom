<?php
// Test de generación de PDF
require __DIR__ . '/../vendor/autoload.php';

use Barryvdh\DomPDF\Facade\Pdf;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    Illuminate\Http\Request::capture()
);

try {
    $html = '<html><body><h1>Test PDF</h1><p>Este es un PDF de prueba</p></body></html>';
    $pdf = Pdf::loadHTML($html);
    $pdfContent = $pdf->output();
    
    // Guardar en archivo para verificar
    file_put_contents(__DIR__ . '/test-output.pdf', $pdfContent);
    
    echo "PDF generado correctamente.\n";
    echo "Tamaño: " . strlen($pdfContent) . " bytes\n";
    echo "Archivo guardado en: " . __DIR__ . '/test-output.pdf';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
