<?php
/**
 * AlertController — Endpoints de alertas (listagem e disparo manual)
 */

use Core\Database;
use Core\Helper;
use Core\Auth;

class AlertController
{
    /**
     * GET /api/v1/alerts — Lista alertas ativos
     */
    public static function index(): void
    {
        $regionId = (int)($_GET['region_id'] ?? 0);
        $limit    = min((int)($_GET['limit'] ?? 20), 100);
        $onlyActive = ($_GET['active'] ?? '1') === '1';

        $sql = "SELECT a.id, a.title, a.message, a.trigger_value,
                       a.triggered_at, a.resolved_at, a.is_active,
                       al.code AS level_code, al.label AS level_label, al.color_hex,
                       r.name AS region_name,
                       st.name AS sensor_type, st.unit,
                       s.name AS sensor_name, s.latitude, s.longitude
                FROM alerts a
                JOIN alert_levels al ON al.id = a.alert_level_id
                JOIN regions r ON r.id = a.region_id
                LEFT JOIN sensors s ON s.id = a.sensor_id
                LEFT JOIN sensor_types st ON st.id = s.sensor_type_id
                WHERE 1=1";

        $params = [];

        if ($regionId > 0) {
            $sql    .= ' AND a.region_id = ?';
            $params[] = $regionId;
        }
        if ($onlyActive) {
            $sql .= ' AND a.is_active = 1';
        }

        $sql .= " ORDER BY a.triggered_at DESC LIMIT {$limit}";

        $alerts = Database::fetchAll($sql, $params);

        Helper::jsonResponse([
            'alerts' => $alerts,
            'total'  => count($alerts),
        ]);
    }

    /**
     * POST /api/v1/alerts — Disparo manual de alerta pelo painel administrativo
     */
    public static function create(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $required = ['region_id', 'alert_level_id', 'title', 'message'];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                Helper::jsonResponse(['error' => "Campo obrigatório: {$f}"], 422);
            }
        }

        $user = Auth::user();

        Database::query(
            "INSERT INTO alerts (region_id, alert_level_id, title, message, created_by, is_active)
             VALUES (?, ?, ?, ?, ?, 1)",
            [
                (int)$data['region_id'],
                (int)$data['alert_level_id'],
                trim($data['title']),
                trim($data['message']),
                $user['id'],
            ]
        );

        $alertId = (int)Database::lastInsertId();

        // Dispara notificações imediatamente
        require_once ROOT_PATH . '/modules/alerts/AlertChecker.php';
        AlertChecker::notifyAlert($alertId);

        Helper::jsonResponse(['success' => true, 'alert_id' => $alertId], 201);
    }

    /**
     * POST /api/v1/alerts/{id}/resolve — Encerra um alerta ativo
     */
    public static function resolve(int $id): void
    {
        Database::query(
            "UPDATE alerts SET is_active = 0, resolved_at = NOW() WHERE id = ?",
            [$id]
        );
        Helper::jsonResponse(['success' => true]);
    }
}
