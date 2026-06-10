<?php
/**
 * Script para añadir el botón "Crear Cuenta" en el modal de Staging
 */

$filePath = __DIR__ . '/resources/js/Pages/Cfdi/Index.vue';
$content = file_get_contents($filePath);

// El botón original en staging (usa doc.uuid)
$originalButton = '<button @click="verPdf(doc.uuid)"';

// El botón nuevo para staging
$newButton = <<<'HTML'
<button @click="abrirModalCrearCuenta(doc)" 
                                                        class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                                        title="Crear Cuenta">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                </button>
HTML;

// Insertar el nuevo botón después del original
$pos = strpos($content, $originalButton);
if ($pos !== false) {
    // Encontrar el cierre </button> de este botón específico
    $endPos = strpos($content, '</button>', $pos);
    if ($endPos !== false) {
        $insertAt = $endPos + strlen('</button>');
        $content = substr($content, 0, $insertAt) . "\n                                                " . $newButton . substr($content, $insertAt);
        echo "OK - Botón de staging insertado\n";
    }
} else {
    echo "ERROR - No se encontró el botón original en staging\n";
}

file_put_contents($filePath, $content);

// Limpiar
unlink(__FILE__);
