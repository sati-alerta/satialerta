<?php
namespace Core;

/**
 * Config — Carregador de variáveis de ambiente e arquivos de configuração
 */
class Config
{
    private static array $loaded = [];
    private static bool  $envLoaded = false;

    /**
     * Carrega o arquivo .env do projeto
     */
    public static function loadEnv(string $path): void
    {
        if (self::$envLoaded || !file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if (!isset($_ENV[$key])) {
                $_ENV[$key]    = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        self::$envLoaded = true;
    }

    /**
     * Obtém um arquivo de configuração (config/app.php, config/database.php, etc.)
     */
    public static function get(string $name): array
    {
        if (!isset(self::$loaded[$name])) {
            $path = dirname(__DIR__) . "/config/{$name}.php";
            if (!file_exists($path)) {
                throw new \RuntimeException("Config file não encontrado: {$name}");
            }
            self::$loaded[$name] = require $path;
        }
        return self::$loaded[$name];
    }

    /**
     * Obtém uma variável de ambiente com valor padrão
     */
    public static function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}
