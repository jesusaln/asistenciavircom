<?php

$files = [
    'routes/admin.php',
    'routes/api.php',
    'routes/console.php',
    'routes/ecommerce.php',
    'routes/portal.php',
    'routes/public.php',
    'routes/web.php',
    'routes/admin/crm.php',
    'routes/admin/empresa.php',
    'routes/admin/marketing.php',
    'routes/admin/soporte.php',
];

$routes = [];
$duplicates = [];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Simple regex to match Route::method('uri'
    // This won't handle all cases (like variables or dynamic calls) but should catch most literal duplicates
    preg_match_all('/Route::(get|post|put|delete|patch|match|any|resource|apiResource)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches, PREG_SET_ORDER);
    
    $prefix = '';
    // API routes are prefixed with 'api' by default in Laravel
    if ($file === 'routes/api.php') {
        $prefix = 'api/';
    }
    
    foreach ($matches as $match) {
        $method = strtoupper($match[1]);
        $uri = $prefix . ltrim($match[2], '/');
        
        $key = "$method $uri";
        if (isset($routes[$key])) {
            $duplicates[] = [
                'route' => $key,
                'first_seen' => $routes[$key],
                'second_seen' => $file
            ];
        } else {
            $routes[$key] = $file;
        }
    }
}

echo "Found " . count($duplicates) . " potential literal duplicates:\n";
foreach ($duplicates as $dup) {
    echo "- {$dup['route']} (First in {$dup['first_seen']}, then in {$dup['second_seen']})\n";
}
