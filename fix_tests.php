<?php

$dir = new RecursiveDirectoryIterator('/home/vircom/.gemini/antigravity/scratch/climasdeldesierto/tests');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);

        if (str_contains($content, '/** @test */') || str_contains($content, '/**\n     * @test')) {
            echo "Processing $path...\n";

            // Add import if missing
            if (!str_contains($content, 'use PHPUnit\Framework\Attributes\Test;')) {
                $content = preg_replace('/namespace .*;/a', "$0\n\nuse PHPUnit\Framework\Attributes\Test;", $content);
            }

            // Replace /** @test */ and variants
            $content = preg_replace('/\/\*\* @test \*\//', '#[Test]', $content);
            $content = preg_replace('/\/\*\*[\s\*]+@test[\s\*]+\*\//', '#[Test]', $content);

            // Also handle multi-line docblocks where @test is just one of the annotations
            $content = preg_replace('/(\s*)\* @test(\s*\n)/', '$1$2', $content); // Remove @test from multi-line
            $content = preg_replace('/(public|protected|private) function/', "#[Test]\n    $0", $content); // Add #[Test] to all methods (oops, too aggressive)

            // Let's be more precise
            // Revert the aggressive one
        }
    }
}
