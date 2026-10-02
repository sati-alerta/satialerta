<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

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
if (!empty($appCfg['url'])) {
    Helper::setBaseUrl($appCfg['url']);
}

// Inicia sessão
Session::start();

// ------- Parse da rota -------
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Remove prefixo de subpasta (ex: /appSati-alerta)
$uri = preg_replace('#^/appSati-alerta#i', '', $uri);

// Remove prefixos de pasta pública ou script (/public/index.php, /public, /index.php)
$uri = preg_replace('#^/public/index\.php#i', '', $uri);
$uri = preg_replace('#^/index\.php#i', '', $uri);
$uri = preg_replace('#^/public#i', '', $uri);

$uri = '/' . trim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Garante permissões em pastas de views no Linux (HostGator/cPanel)
if (!is_readable(__DIR__ . '/views/public/index.php')) {
    @chmod(__DIR__ . '/views', 0755);
    @chmod(__DIR__ . '/views/public', 0755);
    @chmod(__DIR__ . '/views/public/index.php', 0644);
    @chmod(__DIR__ . '/views/errors', 0755);
    @chmod(__DIR__ . '/views/auth', 0755);
    @chmod(__DIR__ . '/views/admin', 0755);
    @chmod(__DIR__ . '/views/producer', 0755);
    @chmod(__DIR__ . '/assets', 0755);
    if (is_dir(__DIR__ . '/views')) {
        try {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(__DIR__ . '/views', \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($it as $item) {
                @chmod($item->getPathname(), $item->isDir() ? 0755 : 0644);
            }
        } catch (\Throwable $t) {}
    }
}

try {
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
} catch (\Throwable $e) {
    error_log('[SATI-APP] Erro: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());

    if (
        str_contains($e->getMessage(), 'banco de dados') ||
        str_contains($e->getMessage(), 'database') ||
        str_contains($e->getMessage(), 'SQLSTATE') ||
        $e instanceof \PDOException
    ) {
        $dbError = $e->getMessage();
        http_response_code(500);
        include __DIR__ . '/views/errors/db_error.php';
        exit;
    }

    $errorMessage = (!empty($appCfg['debug'])) ? $e->getMessage() . "\n" . $e->getTraceAsString() : '';
    http_response_code(500);
    include __DIR__ . '/views/errors/500.php';
    exit;
}
