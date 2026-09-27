<?php
namespace Core;

/**
 * Auth — Autenticação e Controle de Acesso Baseado em Funções (RBAC)
 */
class Auth
{
    private static ?array $currentUser = null;

    /**
     * Tenta autenticar o usuário com email/senha
     */
    public static function attempt(string $email, string $password): bool
    {
        $user = Database::fetchOne(
            "SELECT u.*, r.name AS role_name FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? AND u.is_active = 1 AND u.deleted_at IS NULL
             LIMIT 1",
            [$email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Atualiza último login
        Database::query("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$user['id']]);

        // Regenera sessão por segurança
        session_regenerate_id(true);

        Session::set('user', [
            'id'        => $user['id'],
            'name'      => $user['name'],
            'email'     => $user['email'],
            'role'      => $user['role_name'],
            'role_id'   => $user['role_id'],
            'region_id' => $user['region_id'],
        ]);

        self::$currentUser = Session::get('user');
        return true;
    }

    /**
     * Retorna o usuário logado ou null
     */
    public static function user(): ?array
    {
        if (self::$currentUser === null) {
            self::$currentUser = Session::get('user');
        }
        return self::$currentUser;
    }

    /**
     * Verifica se há usuário logado
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Retorna o papel (role) do usuário logado
     */
    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    /**
     * Verifica se o usuário tem um papel específico (ou um dos papéis)
     */
    public static function hasRole(string|array $roles): bool
    {
        $current = self::role();
        if ($current === null) return false;
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($current, $roles, true);
    }

    /**
     * Garante que o usuário está logado — redireciona ao login se não estiver
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Faça login para continuar.');
            header('Location: ' . Helper::url('login'));
            exit;
        }
    }

    /**
     * Garante que o usuário tem o papel necessário
     */
    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            http_response_code(403);
            include __DIR__ . '/../public/views/errors/403.php';
            exit;
        }
    }

    /**
     * Faz logout do usuário
     */
    public static function logout(): void
    {
        self::$currentUser = null;
        Session::destroy();
    }

    /**
     * Retorna a URL do dashboard baseada no papel do usuário
     */
    public static function dashboardUrl(): string
    {
        return match (self::role()) {
            'admin_gov'      => Helper::url('admin/dashboard'),
            'rural_producer' => Helper::url('producer/dashboard'),
            'public'         => Helper::url('public'),
            default          => Helper::url('login'),
        };
    }

    /**
     * Cria um hash seguro de senha
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
