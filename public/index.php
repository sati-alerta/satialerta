<?php
/**
 * public/index.php — Ponto de entrada da aplicação web
 * Roteador simples de páginas com autenticação RBAC
 */

define('ROOT_PATH', dirname(__DIR__));

header('Content-Type: text/html; charset=UTF-8');

// Autoload das classes core
spl_autoload_register(function (string $class): void {
    $file = ROOT_PATH . '/' . str_replace(['\\', 'Core/'], ['/', 'core/'], $class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once ROOT_PATH . '/core/Config.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Session.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/core/Helper.php';
require_once ROOT_PATH . '/core/Router.php';

use Core\Config;
use Core\Session;
use Core\Auth;
use Core\Helper;

// Carrega .env
Config::loadEnv(ROOT_PATH . '/.env');

// Configura timezone e URL base
$appCfg = Config::get('app');
date_default_timezone_set($appCfg['timezone'] ?? 'America/Manaus');
Helper::setBaseUrl($appCfg['url'] ?? 'http://localhost/appSati-alerta');

// Inicia sessão
Session::start();

// ------- Parse da rota -------
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = preg_replace('#^/appSati-alerta/public#', '', $uri);
$uri = '/' . trim($uri, '/');
if ($uri === '') $uri = '/';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ------- Rotas Públicas -------
if ($uri === '/' || $uri === '/public') {
    include __DIR__ . '/views/public/index.php';
    exit;
}

if ($uri === '/login') {
    if (Auth::check()) {
        Helper::redirect(Auth::dashboardUrl());
    }
    if ($method === 'POST') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (Auth::attempt($email, $password)) {
            Helper::redirect(Auth::dashboardUrl());
        } else {
            Session::flash('error', 'E-mail ou senha inválidos.');
        }
    }
    include __DIR__ . '/views/auth/login.php';
    exit;
}

if ($uri === '/logout') {
    Auth::logout();
    Helper::redirect(Helper::url('login'));
}

// ------- Rotas Privadas — Admin Governamental -------
if (str_starts_with($uri, '/admin')) {
    Auth::requireRole('admin_gov');

    match ($uri) {
        '/admin', '/admin/dashboard' => include(__DIR__ . '/views/admin/dashboard.php'),
        '/admin/sensors'             => include(__DIR__ . '/views/admin/sensors.php'),
        '/admin/alerts'              => include(__DIR__ . '/views/admin/alerts.php'),
        '/admin/reports'             => include(__DIR__ . '/views/admin/reports.php'),
        default                      => include(__DIR__ . '/views/errors/404.php'),
    };
    exit;
}

// ------- Rotas Privadas — Produtor Rural -------
if (str_starts_with($uri, '/producer')) {
    Auth::requireRole(['admin_gov', 'rural_producer']);

    match ($uri) {
        '/producer', '/producer/dashboard' => include(__DIR__ . '/views/producer/dashboard.php'),
        default                             => include(__DIR__ . '/views/errors/404.php'),
    };
    exit;
}

// ------- 404 -------
http_response_code(404);
include __DIR__ . '/views/errors/404.php';
