<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

use DevDesk\Http\Api;

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
if (!str_starts_with($path, '/api/')) {          // servidor embutido do PHP: serve a interface
    readfile(__DIR__ . '/app.html');
    return;
}
$service = makeService('sqlite:' . __DIR__ . '/../storage/devdesk.sqlite', __DIR__ . '/../storage/notifications.log');
$body = json_decode((string) file_get_contents('php://input'), true) ?: [];
[$code, $data] = (new Api($service))->handle($_SERVER['REQUEST_METHOD'], $path, $_GET, $body);

http_response_code($code);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
