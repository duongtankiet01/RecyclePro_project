<?php
declare(strict_types=1);

// Set UTF-8 encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Suppress PHP warnings/errors from being output (log them instead)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/database/Database.php';
require __DIR__ . '/app/controllers/BaseController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(function (string $className) {
    $paths = [
        __DIR__ . '/app/controllers/' . $className . '.php',
        __DIR__ . '/app/controllers/api/' . $className . '.php',
        __DIR__ . '/app/controllers/web/' . $className . '.php',
        __DIR__ . '/app/controllers/webhook/' . $className . '.php',
        __DIR__ . '/app/models/' . $className . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});

$controllerParam = $_GET['controller'] ?? 'home';
$actionParam = $_GET['action'] ?? 'index';

// Set Content-Type based on controller type
// API controllers will override this with application/json
if (strpos($controllerParam, 'api_') !== 0) {
    header('Content-Type: text/html; charset=UTF-8');
}

$controllerClass = convertToControllerClass($controllerParam);

if (!class_exists($controllerClass)) {
    http_response_code(404);
    echo "Controller not found: {$controllerClass}";
    exit;
}

$controller = new $controllerClass();

if (!method_exists($controller, $actionParam)) {
    http_response_code(404);
    echo "Action not found: {$actionParam}";
    exit;
}

$controller->$actionParam();

function convertToControllerClass(string $controllerParam): string
{
    $segments = preg_split('/[-_]/', $controllerParam);
    $segments = array_map('ucfirst', $segments);
    return implode('', $segments) . 'Controller';
}
