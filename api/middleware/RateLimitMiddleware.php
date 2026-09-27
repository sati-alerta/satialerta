<?php
/**
 * RateLimitMiddleware — Limita requisições por IP para proteger a API
 */

use Core\Helper;

class RateLimitMiddleware
{
    private const LIMIT_PER_MINUTE = 120;
    private const WINDOW_SECONDS   = 60;

    /**
     * Limita requisições usando arquivos temporários (sem Redis/Memcached)
     * Para produção, substituir por Redis.
     */
    public static function check(): void
    {
        $ip  = self::getClientIp();
        $key = 'rl_' . md5($ip);
        $dir = sys_get_temp_dir() . '/sati_rl/';

        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $file = $dir . $key . '.json';
        $now  = time();

        $data = ['count' => 0, 'window_start' => $now];

        if (file_exists($file)) {
            $stored = json_decode(file_get_contents($file), true);
            if ($stored && ($now - $stored['window_start']) < self::WINDOW_SECONDS) {
                $data = $stored;
            }
        }

        $data['count']++;

        file_put_contents($file, json_encode($data));

        if ($data['count'] > self::LIMIT_PER_MINUTE) {
            header('Retry-After: ' . self::WINDOW_SECONDS);
            throw new \RuntimeException(
                "Muitas requisições. Limite: " . self::LIMIT_PER_MINUTE . "/min por IP."
            );
        }
    }

    private static function getClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $h) {
            if (!empty($_SERVER[$h])) {
                return explode(',', $_SERVER[$h])[0];
            }
        }
        return '0.0.0.0';
    }
}
