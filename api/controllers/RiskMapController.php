<?php
/**
 * RiskMapController — Endpoints de mapas de risco preditivos
 */

use Core\Database;
use Core\Helper;

class RiskMapController
{
    /**
     * GET /api/v1/risk-maps — Retorna os mapas de risco mais recentes por região
     */
    public static function index(): void
    {
        $regionId = (int)($_GET['region_id'] ?? 0);
        $type     = $_GET['type'] ?? ''; // flood|drought|fire|combined

        $sql = "SELECT rm.id, rm.risk_type, rm.risk_level, rm.risk_label,
                       rm.model_version, rm.confidence_pct, rm.geojson_data,
                       rm.generated_at, rm.valid_until,
                       r.id AS region_id, r.name AS region_name, r.state,
                       r.latitude, r.longitude
                FROM risk_maps rm
                JOIN regions r ON r.id = rm.region_id
                WHERE rm.generated_at = (
                    SELECT MAX(rm2.generated_at)
                    FROM risk_maps rm2
                    WHERE rm2.region_id = rm.region_id AND rm2.risk_type = rm.risk_type
                )";

        $params = [];

        if ($regionId > 0) {
            $sql    .= ' AND rm.region_id = ?';
            $params[] = $regionId;
        }

        if ($type) {
            $sql    .= ' AND rm.risk_type = ?';
            $params[] = $type;
        }

        $sql .= ' ORDER BY rm.risk_level DESC, r.state, r.name';

        $maps = Database::fetchAll($sql, $params);

        // Remove geojson_data da listagem geral (pesado) — retorna só se ?geojson=1
        if (!isset($_GET['geojson'])) {
            $maps = array_map(function ($m) {
                unset($m['geojson_data']);
                return $m;
            }, $maps);
        }

        Helper::jsonResponse([
            'risk_maps'    => $maps,
            'total'        => count($maps),
            'generated_at' => date('c'),
        ]);
    }

    /**
     * GET /api/v1/risk-maps/summary — Resumo de risco por região (para o mapa)
     */
    public static function summary(): void
    {
        $regions = Database::fetchAll(
            "SELECT r.id, r.name, r.state, r.latitude, r.longitude,
                    rm.risk_level, rm.risk_label, rm.risk_type
             FROM regions r
             LEFT JOIN risk_maps rm ON rm.region_id = r.id AND rm.risk_type = 'combined'
             ORDER BY rm.risk_level DESC"
        );

        Helper::jsonResponse(['regions' => $regions, 'total' => count($regions)]);
    }
}
