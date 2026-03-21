<?php
// php tools/audit_routes.php
require dirname(__DIR__) . '/bootstrap.php';

$lines = file(dirname(__DIR__) . '/routes/web.php', FILE_IGNORE_NEW_LINES);
$routes = [];
foreach ($lines as $line) {
    $line = trim($line);
    if (strpos($line, '$router->') !== 0) {
        continue;
    }
    if (preg_match('#^\$router->(get|post)\(\s*\'([^\']+)\'\s*,\s*\'([^\']+)\'\s*,\s*\'([^\']+)\'\s*\);?\s*$#', $line, $m)) {
        $routes[] = [$m[1], $m[2], $m[3], $m[4]];
    }
}

$errors = [];
foreach ($routes as $r) {
    [, $path, $ctrl, $action] = $r;
    $class = 'App\\Controllers\\' . $ctrl;
    if (!class_exists($class)) {
        $errors[] = "Missing class: $class ($path)";
        continue;
    }
    if (!method_exists($class, $action)) {
        $errors[] = "Missing method: $class::$action ($path)";
    }
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo 'Routes OK: ' . count($routes) . " routes\n";
