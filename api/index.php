<?php
// index.php
declare(strict_types=1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/middleware/RoleMiddleware.php';

// Parsing URI e metodo
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Rimuovi il base path (es. /PhpApi/api) per ottenere solo la risorsa
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$path     = substr($uri, strlen($basePath));
$parts    = array_values(array_filter(explode('/', $path)));

// Body JSON
$body = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Router principale ──
$resource = $parts[0] ?? '';
$id       = isset($parts[1]) ? (int)$parts[1] : null;

match (true) {
    $resource === 'auth'   => (new AuthController())->handle($method, $parts, $body),
    $resource === 'utenti' => (new UserController())->handle($method, $id, $body),
    $resource === ''       => Response::success(['endpoints' => ['/utenti', '/auth']], 'PhpApi attiva'),
    default                => Response::error('Risorsa non trovata', 404),
};
