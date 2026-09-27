<?php
/**
 * ReportGenerator — Gerador automático de relatórios climáticos
 */

require_once __DIR__ . '/../analytics/RiskEngine.php';
require_once __DIR__ . '/../analytics/TrendAnalyzer.php';

use Core\Database;

class ReportGenerator
{
    /**
     * Gera relatório climático diário automático para todas as regiões
     */
    public static function generateDailyAll(): void
    {
        $regions = Database::fetchAll("SELECT id, name, state FROM regions");
        foreach ($regions as $region) {
            self::generateDailyForRegion((int)$region['id'], $region['name']);
        }
    }

    /**
     * Gera relatório diário para uma região específica
     */
    public static function generateDailyForRegion(int $regionId, string $regionName = ''): int
    {
        if (!$regionName) {
            $r = Database::fetchOne("SELECT name FROM regions WHERE id = ?", [$regionId]);
            $regionName = $r['name'] ?? "Região #{$regionId}";
        }

        $risks = RiskEngine::getLatestRisk($regionId);
        $stats = self::getRegionStats($regionId);
        $alerts = self::getRecentAlerts($regionId);

        $title   = "Relatório Climático Diário — {$regionName} — " . date('d/m/Y');
        $content = self::buildReportContent($regionName, $risks, $stats, $alerts);

        Database::query(
            "INSERT INTO reports (region_id, type, title, summary, content, generated_by, is_public, created_at)
             VALUES (?, 'general', ?, ?, ?, 'auto', 1, NOW())",
            [$regionId, $title, "Relatório automático diário para {$regionName}.", $content]
        );

        return (int)Database::lastInsertId();
    }

    private static function getRegionStats(int $regionId): array
    {
        $stats = [];

        $types = ['WATER_LEVEL', 'AIR_QUALITY', 'SOIL_MOISTURE', 'AIR_HUMIDITY'];
        foreach ($types as $type) {
            $row = Database::fetchOne(
                "SELECT
                   AVG(sr.value) AS avg_val,
                   MAX(sr.value) AS max_val,
                   MIN(sr.value) AS min_val
                 FROM sensor_readings sr
                 JOIN sensors s ON s.id = sr.sensor_id
                 JOIN sensor_types st ON st.id = s.sensor_type_id
                 WHERE st.code = ? AND s.region_id = ?
                   AND sr.recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                [$type, $regionId]
            );
            $stats[$type] = $row;
        }

        return $stats;
    }

    private static function getRecentAlerts(int $regionId): array
    {
        return Database::fetchAll(
            "SELECT a.title, a.message, al.label AS level, a.triggered_at
             FROM alerts a
             JOIN alert_levels al ON al.id = a.alert_level_id
             WHERE a.region_id = ? AND a.triggered_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY a.triggered_at DESC LIMIT 10",
            [$regionId]
        );
    }

    private static function buildReportContent(
        string $regionName, array $risks, array $stats, array $alerts
    ): string {
        $date    = date('d/m/Y H:i');
        $flood   = $risks['flood']['risk_label']   ?? 'N/D';
        $drought = $risks['drought']['risk_label']  ?? 'N/D';
        $fire    = $risks['fire']['risk_label']     ?? 'N/D';

        $alertList = '';
        foreach ($alerts as $a) {
            $alertList .= "• [{$a['level']}] {$a['title']} — " . date('d/m H:i', strtotime($a['triggered_at'])) . "\n";
        }
        if (!$alertList) $alertList = "Nenhum alerta nas últimas 24h.\n";

        $waterAvg  = number_format((float)($stats['WATER_LEVEL']['avg_val']   ?? 0), 1);
        $soilAvg   = number_format((float)($stats['SOIL_MOISTURE']['avg_val'] ?? 0), 1);
        $airPpmAvg = number_format((float)($stats['AIR_QUALITY']['avg_val']   ?? 0), 1);
        $humAvg    = number_format((float)($stats['AIR_HUMIDITY']['avg_val']  ?? 0), 1);

        return <<<REPORT
RELATÓRIO CLIMÁTICO DIÁRIO — SATI-ALERTA
Região: {$regionName}
Gerado em: {$date}
========================================

1. ÍNDICES DE RISCO PREDITIVO
- Risco de Inundação : {$flood}
- Risco de Seca      : {$drought}
- Risco de Incêndio  : {$fire}

2. MÉDIAS DAS ÚLTIMAS 24 HORAS
- Nível de Água    : {$waterAvg} cm
- Umidade do Solo  : {$soilAvg} %
- Qualidade do Ar  : {$airPpmAvg} ppm
- Umidade do Ar    : {$humAvg} %

3. ALERTAS EMITIDOS (últimas 24h)
{$alertList}

4. RECOMENDAÇÕES
- Verificar sensores com última leitura há mais de 2h.
- Monitorar pontos de alagamento identificados nos mapas de risco.
- Acionar equipes de manutenção preventiva de drenagem em áreas críticas.

---
Relatório gerado automaticamente pelo SATI-Alerta.
Plataforma de Dados Abertos | ODS 3, 11, 13, 17
REPORT;
    }
}
