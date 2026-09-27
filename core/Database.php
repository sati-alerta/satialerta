<?php
namespace Core;

use PDO;
use PDOException;

/**
 * Database — PDO Singleton com reconexão automática e logging de erros
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    private static function connect(): void
    {
        $cfg = Config::get('database');
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}";

        try {
            self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], $cfg['options']);
        } catch (PDOException $e) {
            error_log('[SATI-DB] Conexão falhou: ' . $e->getMessage());
            throw new \RuntimeException('Falha na conexão com o banco de dados. Tente novamente.');
        }
    }

    /**
     * Executa uma query preparada e retorna o statement
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $pdo  = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Retorna todos os registros de uma query
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Retorna o primeiro registro de uma query
     */
    public static function fetchOne(string $sql, array $params = []): array|false
    {
        return self::query($sql, $params)->fetch();
    }

    /**
     * Retorna o ID do último INSERT
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    /**
     * Inicia/confirma/reverte transações
     */
    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }
    public static function commit(): void
    {
        self::getInstance()->commit();
    }
    public static function rollback(): void
    {
        self::getInstance()->rollBack();
    }
}
