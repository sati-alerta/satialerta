<?php
/**
 * AuthMiddleware — Validação de API Key (ESP32 gateway) e sessão web
 */

use Core\Config;
use Core\Database;
use Core\Helper;
use Core\Session;
use Core\Auth;

class AuthMiddleware
{
    /**
     * Valida API Key enviada pelo ESP32 no header X-API-Key
     * Compara via hash SHA-256 para não armazenar a chave em texto puro
     */
    public static function requireApiKey(): void
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

        if (empty($apiKey)) {
            Helper::jsonResponse(['error' => 'API Key necessária (header X-API-Key)'], 401);
        }

        $keyHash = hash('sha256', $apiKey);

        $record = Database::fetchOne(
            "SELECT id, gateway_id, region_id FROM api_keys
             WHERE key_hash = ? AND is_active = 1 LIMIT 1",
            [$keyHash]
        );

        if (!$record) {
            Helper::jsonResponse(['error' => 'API Key inválida ou inativa'], 403);
        }

        // Atualiza last_used_at
        Database::query(
            "UPDATE api_keys SET last_used_at = NOW() WHERE id = ?",
            [$record['id']]
        );

        // Disponibiliza contexto do gateway para os controllers
        $GLOBALS['_gateway'] = $record;
    }

    /**
     * Valida sessão web ativa (para endpoints chamados pelo front-end autenticado)
     */
    public static function requireSession(): void
    {
        Session::start();
        if (!Auth::check()) {
            Helper::jsonResponse(['error' => 'Sessão inválida. Faça login.'], 401);
        }
    }

    /**
     * Valida sessão e papel de administrador governamental
     */
    public static function requireAdminSession(): void
    {
        self::requireSession();
        if (!Auth::hasRole('admin_gov')) {
            Helper::jsonResponse(['error' => 'Acesso negado para este papel de usuário.'], 403);
        }
    }
}
