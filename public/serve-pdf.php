<?php
// Servir PDF directamente con cabeceras explícitas
$file = __DIR__ . '/test-output.pdf';

if (!file_exists($file)) {
    http_response_code(404);
    die('PDF no encontrado');
}

$content = file_get_contents($file);

// Limpiar cualquier salida previa
while (ob_get_level()) {
    ob_end_clean();
}

// Cabeceras explícitas
header('Content-Type: application/pdf');
header('Content-Length: ' . strlen($content));
header('Content-Disposition: inline; filename="test.pdf"');
header('Cache-Control: private, max-age=0');
header('Accept-Ranges: bytes');

// Enviar contenido
echo $content;
exit;
