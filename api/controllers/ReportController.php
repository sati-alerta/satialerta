<?php
/**
 * ReportController — Endpoints de relatórios climáticos (públicos e internos)
 */

use Core\Database;
use Core\Helper;

class ReportController
{
    /**
     * GET /api/v1/reports — Lista relatórios disponíveis
     */
    public static function index(): void
    {
        $regionId = (int)($_GET['region_id'] ?? 0);
        $type     = $_GET['type'] ?? '';
        $onlyPublic = ($_GET['public'] ?? '1') === '1';
        $limit    = min((int)($_GET['limit'] ?? 20), 100);

        $sql = "SELECT rp.id, rp.type, rp.title, rp.summary, rp.file_path,
                       rp.generated_by, rp.is_public, rp.views_count, rp.created_at,
                       r.name AS region_name, r.state,
                       u.name AS author_name
                FROM reports rp
                JOIN regions r ON r.id = rp.region_id
                LEFT JOIN users u ON u.id = rp.author_id
                WHERE 1=1";

        $params = [];

        if ($onlyPublic) {
            $sql .= ' AND rp.is_public = 1';
        }
        if ($regionId > 0) {
            $sql    .= ' AND rp.region_id = ?';
            $params[] = $regionId;
        }
        if ($type) {
            $sql    .= ' AND rp.type = ?';
            $params[] = $type;
        }

        $sql .= " ORDER BY rp.created_at DESC LIMIT {$limit}";

        $reports = Database::fetchAll($sql, $params);

        // Incrementa views no primeiro resultado se for um acesso individual
        // (simplificação — em produção usar endpoint /reports/{id})

        Helper::jsonResponse([
            'reports' => $reports,
            'total'   => count($reports),
        ]);
    }
}
