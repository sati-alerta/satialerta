<?php
namespace Core;

/**
 * Session — Gerenciamento seguro de sessões PHP
 */
class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $cfg = Config::get('app');

        session_name($cfg['session']['name'] ?? 'sati_session');

        session_set_cookie_params([
            'lifetime' => $cfg['session']['lifetime'] ?? 7200,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;

        // Regenera ID após login (segurança contra session fixation)
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        self::set("_flash_{$key}", $value);
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $val = self::get("_flash_{$key}", $default);
        self::remove("_flash_{$key}");
        return $val;
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
        self::$started = false;
    }

    public static function token(): string
    {
        return self::get('_token', '');
    }

    public static function verifyToken(string $token): bool
    {
        return hash_equals(self::token(), $token);
    }
}
