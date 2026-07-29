<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$base = dirname(__DIR__);

require_once $base . '/CONFIG/db.php';
require_once $base . '/APP/CONTROLLER/AuthController.php';
require_once $base . '/APP/CONTROLLER/PlantaController.php';
require_once $base . '/APP/CONTROLLER/RegaController.php';
require_once $base . '/APP/CONTROLLER/IAController.php';

$database = new Database();
$db = $database->getConnection();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = basename($path);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($route) {

        case 'health':
            echo json_encode(['status' => 'ok']);
            exit;

        case 'login':
            if ($method === 'POST') {
                (new AuthController($db))->login();
            }
            break;

        case 'cadastro':
            if ($method === 'POST') {
                (new AuthController($db))->cadastro();
            }
            break;

        case 'logout':
            if ($method === 'POST') {
                (new AuthController($db))->logout();
            }
            break;

        case 'plantas':
            if ($method === 'GET') {
                (new PlantaController($db))->listar();
            }
            if ($method === 'POST') {
                (new PlantaController($db))->salvar();
            }
            if ($method === 'DELETE') {
                (new PlantaController($db))->deletar();
            }
            break;

        case 'identificar':
            if ($method === 'POST') {
                (new PlantaController($db))->identificar();
            }
            break;

        case 'regar':
            if ($method === 'POST') {
                (new RegaController($db))->regar();
            }
            break;

        case 'chat':
            if ($method === 'POST') {
                (new IAController($db))->chat();
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Rota não encontrada.']);
            exit;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno.', 'detalhe' => $e->getMessage()]);
}
