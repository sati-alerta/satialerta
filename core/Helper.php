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

    /**
     * Gera URL completa para uma rota da aplicação
     */
    public static function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return self::$baseUrl . '/public/' . $path;
    }

    /**
     * Gera URL para assets com cache-buster baseado no timestamp do arquivo
     */
    public static function asset(string $path): string
    {
        $root     = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
        $filePath = $root . '/public/assets/' . ltrim($path, '/');
        $version  = file_exists($filePath) ? '?v=' . filemtime($filePath) : '';
        return self::$baseUrl . '/public/assets/' . ltrim($path, '/') . $version;
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
    public static function jsonResponse(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Redireciona para uma URL
     */
    public static function redirect(string $url): never
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
