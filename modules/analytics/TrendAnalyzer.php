<?php
/**
 * TrendAnalyzer — Análise de tendências para predição de enchentes e secas
 */

use Core\Database;

class TrendAnalyzer
{
    /**
     * Detecta elevação rápida do nível de água (risco de inundação/bueiro)
     * Retorna taxa de variação por hora em cm/h
     */
    public static function waterLevelTrend(int $sensorId, int $windowMinutes = 30): array
    {
        $readings = Database::fetchAll(
            "SELECT value, recorded_at FROM sensor_readings
             WHERE sensor_id = ?
               AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
             ORDER BY recorded_at ASC",
            [$sensorId, $windowMinutes]
        );

        if (count($readings) < 2) {
            return ['trend' => 'insufficient_data', 'rate_per_hour' => 0, 'readings' => count($readings)];
        }

        $first = $readings[0];
        $last  = $readings[count($readings) - 1];

        $deltaValue   = (float)$last['value'] - (float)$first['value'];
        $deltaSec     = strtotime($last['recorded_at']) - strtotime($first['recorded_at']);
        $ratePerHour  = $deltaSec > 0 ? ($deltaValue / $deltaSec) * 3600 : 0;

        $trend = match(true) {
            $ratePerHour >= 50  => 'rapid_rise',     // >50 cm/h: emergência
            $ratePerHour >= 20  => 'fast_rise',      // 20-50 cm/h: crítico
            $ratePerHour >= 10  => 'moderate_rise',  // 10-20 cm/h: atenção
            $ratePerHour <= -10 => 'falling',
            default             => 'stable',
        };

        return [
            'trend'        => $trend,
            'rate_per_hour'=> round($ratePerHour, 2),
            'delta_value'  => round($deltaValue, 2),
            'window_min'   => $windowMinutes,
            'current_value'=> (float)$last['value'],
            'readings'     => count($readings),
        ];
    }

    /**
     * Detecta queda crítica de umidade do solo associada à qualidade do ar
     * para previsão de secas e risco de incêndio
     */
    public static function droughtRisk(int $regionId): array
    {
        // Última leitura de umidade do solo na região
        $soilMoisture = Database::fetchOne(
            "SELECT sr.value, sr.recorded_at, s.name AS sensor_name
             FROM sensor_readings sr
             JOIN sensors s ON s.id = sr.sensor_id
             JOIN sensor_types st ON st.id = s.sensor_type_id
             WHERE st.code = 'SOIL_MOISTURE' AND s.region_id = ?
             ORDER BY sr.recorded_at DESC LIMIT 1",
            [$regionId]
        );

        // Queda nas últimas 24h de umidade do solo
        $moistureDrop = Database::fetchOne(
            "SELECT
               MAX(sr.value) AS max_24h,
               MIN(sr.value) AS min_24h,
               AVG(sr.value) AS avg_24h
             FROM sensor_readings sr
             JOIN sensors s ON s.id = sr.sensor_id
             JOIN sensor_types st ON st.id = s.sensor_type_id
             WHERE st.code = 'SOIL_MOISTURE' AND s.region_id = ?
               AND sr.recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$regionId]
        );

        // Qualidade do ar atual na região
        $airQuality = Database::fetchOne(
            "SELECT sr.value FROM sensor_readings sr
             JOIN sensors s ON s.id = sr.sensor_id
             JOIN sensor_types st ON st.id = s.sensor_type_id
             WHERE st.code = 'AIR_QUALITY' AND s.region_id = ?
             ORDER BY sr.recorded_at DESC LIMIT 1",
            [$regionId]
        );

        $currentSoil  = (float)($soilMoisture['value'] ?? 50);
        $drop24h      = ($moistureDrop['max_24h'] ?? $currentSoil) - $currentSoil;
        $airPpm       = (float)($airQuality['value'] ?? 0);

        // Score de risco de seca (0-100)
        $score = 0;
        if ($currentSoil < 15)  $score += 40; // solo muito seco
        elseif ($currentSoil < 25) $score += 20;
        if ($drop24h > 15) $score += 30;       // queda rápida de umidade
        if ($airPpm > 60)  $score += 30;       // fumaça presente

        $level = match(true) {
            $score >= 80 => 'critical',
            $score >= 60 => 'high',
            $score >= 40 => 'moderate',
            default      => 'low',
        };

        return [
            'drought_risk_score' => min(100, $score),
            'risk_level'         => $level,
            'soil_moisture_pct'  => $currentSoil,
            'moisture_drop_24h'  => round($drop24h, 2),
            'air_quality_ppm'    => $airPpm,
        ];
    }

    /**
     * Obtém série histórica de uma variável para um sensor
     */
    public static function getTimeSeries(int $sensorId, int $hours = 24): array
    {
        return Database::fetchAll(
            "SELECT value, recorded_at FROM sensor_readings
             WHERE sensor_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
             ORDER BY recorded_at ASC",
            [$sensorId, $hours]
        );
    }
}
