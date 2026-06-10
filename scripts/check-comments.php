#!/usr/bin/env php
<?php
/**
 * Code Comment Quality Checker
 *
 * Detecta comentarios problemáticos en el código PHP:
 * - Comentarios ofensivos o frustrados
 * - Hacks sin documentación
 * - TODOs sin referencia a issues
 * - Comentarios de debug残留
 *
 * Uso: php scripts/check-comments.php [--fix] [--path=app/]
 */

$options = getopt('', ['fix', 'path:', 'verbose', 'json']);
$path = $options['path'] ?? 'app';
$fix = isset($options['fix']);
$verbose = isset($options['verbose']);
$json = isset($options['json']);

$problematicPatterns = [
    // Patrones problemáticos (ofensivos/frustrados)
    '/(?<!\w)fuck(?!\w)/i' => 'Lenguaje inapropiado',
    '/(?<!\w)shit(?!\w)/i' => 'Lenguaje inapropiado',
    '/(?<!\w)damn(?!\w)/i' => 'Lenguaje inapropiado',
    '/(?<!\w)hell(?!\w)/i' => 'Lenguaje inapropiado',
    '/(?<!\w)stupid(?!\w)/i' => 'Lenguaje inapropiado',
    '/(?<!\w)idiot(?!\w)/i' => 'Lenguaje inapropiado',
    '/(?<!\w)ugly(?!\w)/i' => 'Código descrito como feo (mejorar)',

    // Hacks problemáticos
    '/HACK(?:\s+para)?(?:\s+romper|\s+quebrar|\s+break)/i' => 'HACK para romper recursión - necesita refactorización',
    '/HACK(?:\s+para)?(?:\s+evitar|\s+avoid)/i' => 'HACK para evitar problema - documentar razón',
    '/(?<!\w)ugly fix(?!\w)/i' => 'Fix "feo" - necesita mejora',
    '/(?<!\w)quick fix(?!\w)/i' => 'Quick fix - necesita solución permanente',
    '/(?<!\w)temporary fix(?!\w)/i' => 'Temporary fix - necesita solución permanente',

    // Debug残留
    '/var_dump\(/' => 'var_dump()残留 - usar logger',
    '/dd\(/' => 'dd()残留 - eliminar en producción',
    '/dump\(/' => 'dump()残留 - eliminar',
    '/console\.log\(/' => 'console.log()残留 en PHP',

    // TODOs problemáticos
    '/TODO(?!\s*\(?:issue|ticket|#)/i' => 'TODO sin referencia a issue',
    '/FIXME(?!\s*\(?:issue|ticket|#)/i' => 'FIXME sin referencia a issue',
    '/XXX(?!\s*\(?:issue|ticket|#)/i' => 'XXX sin referencia a issue',
];

$allowedPatterns = [
    '/TODO:\s*(?:implement|add|create|fix|refactor|update|remove|optimize|test)\s+.*\(#\d+\)/i',
    '/FIXME:\s*(?:pending|blocked|waiting)\s+\(#\d+\)/i',
    '/HACK:\s*reason\s*=\s*[\'"].*[\'"]/i',
    '/DEPRECATED:/i',
];

$issues = [];
$filesChecked = 0;
$filesWithIssues = 0;

function scanDirectory(string $dir, array &$files): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'vue', 'js', 'ts'])) {
            $files[] = $file->getPathname();
        }
    }
}

function checkFile(string $filepath, array $patterns, array $allowed, bool $verbose): array
{
    $content = file_get_contents($filepath);
    $lines = explode("\n", $content);
    $issues = [];

    foreach ($patterns as $pattern => $message) {
        foreach ($lines as $lineNum => $line) {
            if (preg_match($pattern, $line)) {
                // Verificar si está en patrones permitidos
                $isAllowed = false;
                foreach ($allowed as $allowedPattern) {
                    if (preg_match($allowedPattern, $line)) {
                        $isAllowed = true;
                        break;
                    }
                }

                if (!$isAllowed) {
                    $issues[] = [
                        'file' => $filepath,
                        'line' => $lineNum + 1,
                        'pattern' => $message,
                        'content' => trim($line),
                    ];

                    if ($verbose) {
                        echo "  Line " . ($lineNum + 1) . ": {$message}\n";
                        echo "    " . trim($line) . "\n";
                    }
                }
            }
        }
    }

    return $issues;
}

function fixFile(string $filepath, array $patterns): int
{
    $content = file_get_contents($filepath);
    $original = $content;
    $lines = explode("\n", $content);
    $fixed = [];

    foreach ($lines as $line) {
        $newLine = $line;

        foreach ($patterns as $pattern => $message) {
            // Reemplazar comentarios de debug
            if (preg_match('/var_dump\(/', $line)) {
                $newLine = '// TODO: Remove debug code - ' . $line;
            }
            if (preg_match('/dd\(/', $line)) {
                $newLine = '// TODO: Remove dd() - ' . $line;
            }
        }

        $fixed[] = $newLine;
    }

    $newContent = implode("\n", $fixed);

    if ($newContent !== $original) {
        file_put_contents($filepath, $newContent);
        return 1;
    }

    return 0;
}

// Escanear archivos
$files = [];
scanDirectory($path, $files);

echo "Checking {$path} for problematic comments...\n\n";

foreach ($files as $filepath) {
    $filesChecked++;
    $fileIssues = checkFile($filepath, $problematicPatterns, $allowedPatterns, $verbose);

    if (!empty($fileIssues)) {
        $filesWithIssues++;
        $issues = array_merge($issues, $fileIssues);

        if (!$verbose) {
            echo "{$filepath}:\n";
            foreach ($fileIssues as $issue) {
                echo "  Line {$issue['line']}: {$issue['pattern']}\n";
            }
            echo "\n";
        }
    }
}

// Resumen
$summary = [
    'files_checked' => $filesChecked,
    'files_with_issues' => $filesWithIssues,
    'total_issues' => count($issues),
    'issues_by_type' => [],
];

foreach ($issues as $issue) {
    $type = $issue['pattern'];
    if (!isset($summary['issues_by_type'][$type])) {
        $summary['issues_by_type'][$type] = 0;
    }
    $summary['issues_by_type'][$type]++;
}

if ($json) {
    echo json_encode($summary, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "========================================\n";
    echo "Summary:\n";
    echo "  Files checked: {$summary['files_checked']}\n";
    echo "  Files with issues: {$summary['files_with_issues']}\n";
    echo "  Total issues: {$summary['total_issues']}\n";
    echo "\nBy type:\n";
    foreach ($summary['issues_by_type'] as $type => $count) {
        echo "  - {$type}: {$count}\n";
    }
}

if ($fix && !empty($issues)) {
    echo "\nFixing issues...\n";
    $fixedCount = 0;
    $processedFiles = [];

    foreach ($issues as $issue) {
        $file = $issue['file'];
        if (!isset($processedFiles[$file])) {
            $fixedCount += fixFile($file, $problematicPatterns);
            $processedFiles[$file] = true;
        }
    }

    echo "Fixed {$fixedCount} files.\n";
}

exit(count($issues) > 0 ? 1 : 0);
