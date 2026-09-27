<?php
/**
 * api/index.php — Entry point da API REST do SATI-Alerta
 * Recebe dados do ESP32 gateway e responde a consultas do front-end
 *
 * Endpoint principal de ingestão:
 *   POST /api/v1/ingest
 *   Header: X-API-Key: {gateway_api_key}
 *   Body: JSON com dados do sensor LoRaWAN
 */

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/core/Config.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Helper.php';
require_once ROOT_PATH . '/api/middleware/AuthMiddleware.php';
require_once ROOT_PATH . '/api/middleware/RateLimitMiddleware.php';
require_once ROOT_PATH . '/api/controllers/SensorController.php';
require_once ROOT_PATH . '/api/controllers/AlertController.php';
require_once ROOT_PATH . '/api/controllers/RiskMapController.php';
require_once ROOT_PATH . '/api/controllers/ReportController.php';
require_once ROOT_PATH . '/modules/ingest/LoRaWANParser.php';
require_once ROOT_PATH . '/modules/alerts/AlertChecker.php';

use Core\Config;
use Core\Helper;

// Carrega variáveis de ambiente
Config::loadEnv(ROOT_PATH . '/.env');

// Headers da API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Parse da rota da API
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Remove prefixo /appSati-alerta/api
$uri = preg_replace('#^/appSati-alerta/api#', '', $uri);
$uri = '/' . trim($uri, '/');

// Rate Limiting global
try {
    RateLimitMiddleware::check();
} catch (\Exception $e) {
    helper::jsonResponse(['error' => $e->getMessage()], 429);
}

// ============================================================
// ROTEAMENTO DA API
// ============================================================

// POST /v1/ingest — Ingestão de dados do ESP32 gateway
if ($method === 'POST' && $uri === '/v1/ingest') {
    AuthMiddleware::requireApiKey();
    SensorController::ingest();
    exit;
}

// POST /v1/alerts — Disparo manual de alerta (requer JWT)
if ($method === 'POST' && $uri === '/v1/alerts') {
    AuthMiddleware::requireSession();
    AlertController::create();
    exit;
}

// GET /v1/alerts — Lista alertas ativos por região
if ($method === 'GET' && ($uri === '/v1/alerts' || str_starts_with($uri, '/v1/alerts'))) {
    AlertController::index();
    exit;
}

// GET /v1/sensors — Lista sensores
if ($method === 'GET' && str_starts_with($uri, '/v1/sensors')) {
    SensorController::index();
    exit;
}

// GET /v1/risk-maps — Mapas de risco
if ($method === 'GET' && str_starts_with($uri, '/v1/risk-maps')) {
    RiskMapController::index();
    exit;
}

// GET /v1/reports — Relatórios públicos
if ($method === 'GET' && str_starts_with($uri, '/v1/reports')) {
    ReportController::index();
    exit;
}

// GET /v1/health — Health check do servidor
if ($method === 'GET' && $uri === '/v1/health') {
    Helper::jsonResponse([
        'status'  => 'ok',
        'service' => 'SATI-Alerta API',
        'version' => '1.0.0',
        'time'    => date('c'),
    ]);
}

// 404 — Rota não encontrada
Helper::jsonResponse(['error' => 'Endpoint não encontrado', 'path' => $uri], 404);
