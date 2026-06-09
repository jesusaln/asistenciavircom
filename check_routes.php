<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$routes = Route::getRoutes();
foreach ($routes as $route) {
    $action = $route->getAction();
    if (isset($action['controller'])) {
        $controller = explode('@', $action['controller'])[0];
        if (!class_exists($controller)) {
            echo "BROKEN ROUTE: " . $route->uri() . " -> " . $controller . "\n";
        }
    }
}
