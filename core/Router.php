<?php
namespace Core;

/**
 * Router — Roteador simples de páginas da aplicação (views)
 */
class Router
{
    private static array $routes = [];

    /**
     * Registra uma rota GET
     */
    public static function get(string $path, callable|array $handler): void
    {
        self::$routes['GET'][$path] = $handler;
    }

    /**
     * Registra uma rota POST
     */
    public static function post(string $path, callable|array $handler): void
    {
        self::$routes['POST'][$path] = $handler;
    }

    /**
     * Executa o roteamento com base na URI atual
     */
    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Remove prefixo de subpasta (ex: /appSati-alerta)
        $uri = preg_replace('#^/appSati-alerta#i', '', $uri);

        // Remove prefixos /public/index.php, /public ou /index.php
        $uri = preg_replace('#^/public/index\.php#i', '', $uri);
        $uri = preg_replace('#^/index\.php#i', '', $uri);
        $uri = preg_replace('#^/public#i', '', $uri);

        $uri = '/' . ltrim(rtrim($uri, '/'), '/');
        if ($uri === '') $uri = '/';

        $handler = self::$routes[$method][$uri] ?? null;

        if ($handler === null) {
            // Tenta match dinâmico (ex: /admin/sensors/123)
            foreach (self::$routes[$method] ?? [] as $pattern => $h) {
                $regex = preg_replace('#\{[^/]+\}#', '([^/]+)', $pattern);
                if (preg_match("#^{$regex}$#", $uri, $matches)) {
                    array_shift($matches);
                    $handler = $h;
                    break;
                }
            }
        }

        if ($handler === null) {
            http_response_code(404);
            include __DIR__ . '/../public/views/errors/404.php';
            return;
        }

        if (is_callable($handler)) {
            call_user_func($handler);
        } elseif (is_string($handler)) {
            // handler é um caminho de view
            include __DIR__ . '/../public/' . ltrim($handler, '/');
        }
    }
}
