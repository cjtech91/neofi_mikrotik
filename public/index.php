<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Http\Request;
use App\Http\Response;
use App\Router;

$request = Request::fromGlobals();

$router = new Router();

$router->get('/health', function () {
    return Response::json(['ok' => true]);
});

if (str_starts_with($request->path, '/admin')) {
    $expectedUser = (string) getenv('ADMIN_USER');
    $expectedPass = (string) getenv('ADMIN_PASS');
    $user = isset($_SERVER['PHP_AUTH_USER']) ? (string) $_SERVER['PHP_AUTH_USER'] : '';
    $pass = isset($_SERVER['PHP_AUTH_PW']) ? (string) $_SERVER['PHP_AUTH_PW'] : '';

    if ($expectedUser === '' || $expectedPass === '' || !hash_equals($expectedUser, $user) || !hash_equals($expectedPass, $pass)) {
        header('WWW-Authenticate: Basic realm="Admin"');
        http_response_code(401);
        echo 'Unauthorized';
        exit;
    }
}

if (str_starts_with($request->path, '/api')) {
    $expected = (string) getenv('API_KEY');
    $provided = $request->headers['x-api-key'] ?? '';
    if ($expected === '' || !hash_equals($expected, (string) $provided)) {
        Response::json(['error' => 'Unauthorized'], 401)->send();
        exit;
    }
}

$router->get('/admin', [App\Controllers\AdminController::class, 'dashboard']);
$router->get('/admin/overview', [App\Controllers\AdminController::class, 'overview']);
$router->get('/admin/{page}', [App\Controllers\AdminController::class, 'page']);
$router->post('/admin/{page}', [App\Controllers\AdminController::class, 'savePage']);

$router->get('/api/devices', [App\Controllers\DeviceController::class, 'index']);
$router->post('/api/devices', [App\Controllers\DeviceController::class, 'store']);
$router->post('/api/devices/{id}/test-connection', [App\Controllers\DeviceController::class, 'testConnection']);

$router->post('/api/devices/{id}/hotspot/users', [App\Controllers\HotspotController::class, 'createUser']);
$router->get('/api/devices/{id}/hotspot/users', [App\Controllers\HotspotController::class, 'listUsers']);
$router->delete('/api/devices/{id}/hotspot/users/{name}', [App\Controllers\HotspotController::class, 'removeUser']);
$router->post('/api/devices/{id}/hotspot/users/{name}/disable', [App\Controllers\HotspotController::class, 'disableUser']);
$router->post('/api/devices/{id}/hotspot/users/{name}/enable', [App\Controllers\HotspotController::class, 'enableUser']);
$router->get('/api/devices/{id}/hotspot/active', [App\Controllers\HotspotController::class, 'listActive']);
$router->post('/api/devices/{id}/hotspot/active/{activeId}/remove', [App\Controllers\HotspotController::class, 'removeActive']);

$router->post('/api/devices/{id}/pppoe/secrets', [App\Controllers\PPPoEController::class, 'createSecret']);
$router->get('/api/devices/{id}/pppoe/secrets', [App\Controllers\PPPoEController::class, 'listSecrets']);
$router->delete('/api/devices/{id}/pppoe/secrets/{name}', [App\Controllers\PPPoEController::class, 'removeSecret']);
$router->post('/api/devices/{id}/pppoe/secrets/{name}/disable', [App\Controllers\PPPoEController::class, 'disableSecret']);
$router->post('/api/devices/{id}/pppoe/secrets/{name}/enable', [App\Controllers\PPPoEController::class, 'enableSecret']);
$router->get('/api/devices/{id}/pppoe/active', [App\Controllers\PPPoEController::class, 'listActive']);
$router->post('/api/devices/{id}/pppoe/active/{activeId}/disconnect', [App\Controllers\PPPoEController::class, 'disconnectActive']);

try {
    $response = $router->dispatch($request);
} catch (Throwable $e) {
    $debug = (bool) getenv('APP_DEBUG');
    $payload = ['error' => 'Internal Server Error'];
    if ($debug) {
        $payload['exception'] = get_class($e);
        $payload['message'] = $e->getMessage();
    }
    $response = Response::json($payload, 500);
}

$response->send();
