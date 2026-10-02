<?php
namespace Core;

/**
 * Helper — Funções utilitárias globais
 */
class Helper
{
    private static string $baseUrl = '';

    public static function setBaseUrl(string $url): void
    {
        self::$baseUrl = rtrim($url, '/');
    }

    public static function getBaseUrl(): string
    {
        if (self::$baseUrl === '') {
            self::detectBaseUrl();
        }
        return self::$baseUrl;
    }

    public static function detectBaseUrl(): string
    {
        // 1. Detecta via servidor (HTTP_HOST)
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!empty($host)) {
            $isHttps = (
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            );
            $scheme = $isHttps ? 'https' : 'http';

            // Servidor com domínio real (ex: app.exemplo.com)
            if (!str_contains($host, 'localhost') && !str_contains($host, '127.0.0.1')) {
                self::$baseUrl = "{$scheme}://{$host}";
                return self::$baseUrl;
            }

            // Localhost com subpasta
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (str_starts_with($requestUri, '/appSati-alerta')) {
                self::$baseUrl = "{$scheme}://{$host}/appSati-alerta";
                return self::$baseUrl;
            }
        }

        // 2. Se tiver APP_URL configurado no .env
        $envUrl = Config::env('APP_URL');
        if (!empty($envUrl)) {
            self::$baseUrl = rtrim($envUrl, '/');
            return self::$baseUrl;
        }

        self::$baseUrl = 'http://localhost/appSati-alerta';
        return self::$baseUrl;
    }

    /**
     * Gera URL completa para uma rota da aplicação
     */
    public static function url(string $path = ''): string
    {
        $base = self::getBaseUrl();
        $path = ltrim($path, '/');
        if ($path === 'public' || $path === 'index.php') {
            $path = '';
        }

        // Se a base já terminar com /public
        if (str_ends_with($base, '/public')) {
            return $path === '' ? $base : $base . '/' . $path;
        }

        // Se estiver rodando em localhost XAMPP em subpasta (/appSati-alerta)
        if (str_contains($base, 'localhost/appSati-alerta') || str_contains($base, '127.0.0.1/appSati-alerta')) {
            return $path === '' ? $base . '/public' : $base . '/public/' . $path;
        }

        // Em servidor de produção com domínio próprio (ex: app.exemplo.com)
        return $path === '' ? $base : $base . '/' . $path;
    }

    /**
     * Gera URL para assets com cache-buster baseado no timestamp do arquivo
     */
    public static function asset(string $path): string
    {
        $base     = self::getBaseUrl();
        $root     = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
        $path     = ltrim($path, '/');
        $filePath = $root . '/public/assets/' . $path;
        $version  = file_exists($filePath) ? '?v=' . filemtime($filePath) : '';

        if (str_ends_with($base, '/public')) {
            return $base . '/assets/' . $path . $version;
        }

        return $base . '/public/assets/' . $path . $version;
    }

    /**
     * Escapa output HTML para prevenir XSS
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Formata timestamp para o fuso horário da aplicação
     */
    public static function formatDate(string $date, string $format = 'd/m/Y H:i'): string
    {
        if (!$date) return '—';
        $dt = new \DateTime($date);
        $tz = Config::get('app')['timezone'] ?? 'America/Manaus';
        $dt->setTimezone(new \DateTimeZone($tz));
        return $dt->format($format);
    }

    /**
     * Tempo relativo (há X min, há X horas etc.)
     */
    public static function timeAgo(string $date): string
    {
        $diff = time() - strtotime($date);
        return match (true) {
            $diff < 60     => 'agora mesmo',
            $diff < 3600   => floor($diff / 60) . ' min atrás',
            $diff < 86400  => floor($diff / 3600) . 'h atrás',
            default        => floor($diff / 86400) . 'd atrás',
        };
    }

    /**
     * Responde com JSON (para endpoints AJAX)
     */
    public static function jsonResponse(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Redireciona para uma URL
     */
    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Badge HTML para nível de alerta
     */
    public static function alertBadge(string $level): string
    {
        $map = [
            'INFO'      => ['badge bg-info',    'Informativo'],
            'WARNING'   => ['badge bg-warning text-dark', 'Atenção'],
            'CRITICAL'  => ['badge bg-orange',  'Crítico'],
            'EMERGENCY' => ['badge bg-danger',   'Emergência'],
        ];
        [$class, $label] = $map[$level] ?? ['badge bg-secondary', $level];
        return "<span class=\"{$class}\">{$label}</span>";
    }

    /**
     * Cor Bootstrap baseada no nível de risco (0-100)
     */
    public static function riskColor(int $level): string
    {
        return match(true) {
            $level >= 80 => 'danger',
            $level >= 60 => 'warning',
            $level >= 40 => 'info',
            default      => 'success',
        };
    }
}
