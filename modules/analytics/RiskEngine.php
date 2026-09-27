<?php
/**
 * RiskEngine — Gera mapas de risco preditivos por região
 * Combina dados em tempo real + tendências para calcular nível de risco (0-100)
 */

require_once __DIR__ . '/TrendAnalyzer.php';

use Core\Database;

class RiskEngine
{
    /**
     * Recalcula o mapa de risco para todas as regiões ativas
     */
    public static function recalculateAll(): void
    {
        $regions = Database::fetchAll("SELECT id, name FROM regions");
        foreach ($regions as $region) {
            self::recalculateForRegion((int)$region['id']);
        }
    }

    /**
     * Calcula e persiste o mapa de risco para uma região
     */
    public static function recalculateForRegion(int $regionId): void
    {
        $floodRisk   = self::calculateFloodRisk($regionId);
        $droughtRisk = self::calculateDroughtRisk($regionId);
        $fireRisk    = self::calculateFireRisk($regionId);

        // Risco combinado (média ponderada)
        $combined = (int)round(($floodRisk * 0.4) + ($droughtRisk * 0.35) + ($fireRisk * 0.25));

        $risks = [
            'flood'    => compact('floodRisk')   + ['type' => 'flood'],
            'drought'  => compact('droughtRisk') + ['type' => 'drought'],
            'fire'     => compact('fireRisk')    + ['type' => 'fire'],
            'combined' => ['level' => $combined, 'type' => 'combined'],
        ];

        foreach ($risks as $type => $data) {
            $level    = match($type) {
                'flood'    => $floodRisk,
                'drought'  => $droughtRisk,
                'fire'     => $fireRisk,
                'combined' => $combined,
            };
            $label = self::levelToLabel($level);

            // Upsert (INSERT ou UPDATE se já existe)
            Database::query(
                "INSERT INTO risk_maps (region_id, risk_type, risk_level, risk_label, model_version, valid_until)
                 VALUES (?, ?, ?, ?, '1.0', DATE_ADD(NOW(), INTERVAL 1 HOUR))
                 ON DUPLICATE KEY UPDATE
                   risk_level = VALUES(risk_level),
                   risk_label = VALUES(risk_label),
                   generated_at = NOW(),
                   valid_until = VALUES(valid_until)",
                [$regionId, $type, $level, $label]
            );
        }
    }

    /**
     * Calcula risco de inundação (0-100)
     */
    private static function calculateFloodRisk(int $regionId): int
    {
        $score = 0;

        // Nível de água atual dos sensores
        $waterSensors = Database::fetchAll(
            "SELECT s.id, sr.value FROM sensor_readings sr
             JOIN sensors s ON s.id = sr.sensor_id
             JOIN sensor_types st ON st.id = s.sensor_type_id
             WHERE st.code = 'WATER_LEVEL' AND s.region_id = ?
               AND sr.recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             ORDER BY sr.recorded_at DESC",
            [$regionId]
        );

        foreach ($waterSensors as $ws) {
            $value = (float)$ws['value'];
            if ($value >= 250)      $score += 50;
            elseif ($value >= 200)  $score += 35;
            elseif ($value >= 150)  $score += 20;

            // Analisa tendência de subida
            $trend = TrendAnalyzer::waterLevelTrend((int)$ws['id']);
            if ($trend['trend'] === 'rapid_rise')    $score += 30;
            elseif ($trend['trend'] === 'fast_rise') $score += 20;
        }

        // Umidade do ar alta contribui para risco de chuva
        $airHumidity = Database::fetchOne(
            "SELECT AVG(sr.value) AS avg_humidity FROM sensor_readings sr
             JOIN sensors s ON s.id = sr.sensor_id
             JOIN sensor_types st ON st.id = s.sensor_type_id
             WHERE st.code = 'AIR_HUMIDITY' AND s.region_id = ?
               AND sr.recorded_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR)",
            [$regionId]
        );

        if (($airHumidity['avg_humidity'] ?? 0) >= 85) $score += 15;

        return min(100, $score);
    }

    /**
     * Calcula risco de seca (0-100)
     */
    private static function calculateDroughtRisk(int $regionId): int
    {
        $drought = TrendAnalyzer::droughtRisk($regionId);
        return min(100, $drought['drought_risk_score']);
    }

    /**
     * Calcula risco de incêndio (0-100)
     */
    private static function calculateFireRisk(int $regionId): int
    {
        $score = 0;

        $airQuality = Database::fetchOne(
            "SELECT MAX(sr.value) AS max_ppm FROM sensor_readings sr
             JOIN sensors s ON s.id = sr.sensor_id
             JOIN sensor_types st ON st.id = s.sensor_type_id
             WHERE st.code = 'AIR_QUALITY' AND s.region_id = ?
               AND sr.recorded_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)",
            [$regionId]
        );

        $maxPpm = (float)($airQuality['max_ppm'] ?? 0);
        if ($maxPpm >= 150)      $score += 50;
        elseif ($maxPpm >= 80)   $score += 30;
        elseif ($maxPpm >= 50)   $score += 10;

        // Umidade do solo baixa + fumaça = alto risco de incêndio
        $drought = TrendAnalyzer::droughtRisk($regionId);
        if ($drought['soil_moisture_pct'] < 15 && $maxPpm > 40) $score += 40;

        return min(100, $score);
    }

    /**
     * Obtém o último mapa de risco de uma região
     */
    public static function getLatestRisk(int $regionId): array
    {
        $maps = Database::fetchAll(
            "SELECT risk_type, risk_level, risk_label, generated_at, valid_until
             FROM risk_maps
             WHERE region_id = ?
             ORDER BY generated_at DESC",
            [$regionId]
        );

        $result = [];
        foreach ($maps as $m) {
            $result[$m['risk_type']] = $m;
        }
        return $result;
    }

    private static function levelToLabel(int $level): string
    {
        return match(true) {
            $level >= 80 => 'Crítico',
            $level >= 60 => 'Alto',
            $level >= 40 => 'Moderado',
            $level >= 20 => 'Baixo',
            default      => 'Mínimo',
        };
    }
}
