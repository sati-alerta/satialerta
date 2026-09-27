<?php
/**
 * SensorController — Endpoint de ingestão de dados IoT (ESP32/LoRaWAN)
 *
 * Payload esperado do ESP32 (JSON):
 * {
 *   "gateway_id": "GW-MAN-001",
 *   "sensor_eui": "A1B2C3D4E5F60001",
 *   "value": 85.5,
 *   "rssi": -110,
 *   "snr": 7.2,
 *   "battery_pct": 87,
 *   "timestamp": "2025-06-15T14:30:00Z"  // opcional, usa NOW() se ausente
 * }
 */

require_once ROOT_PATH . '/modules/ingest/LoRaWANParser.php';
require_once ROOT_PATH . '/modules/alerts/AlertChecker.php';

use Core\Database;
use Core\Helper;

class SensorController
{
    /**
     * POST /api/v1/ingest — Recebe payload do ESP32 e persiste a leitura
     */
    public static function ingest(): void
    {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            Helper::jsonResponse(['error' => 'Payload JSON inválido ou vazio.'], 400);
        }

        // Validação dos campos obrigatórios
        $required = ['sensor_eui', 'value'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Helper::jsonResponse(['error' => "Campo obrigatório ausente: {$field}"], 422);
            }
        }

        // Parser do payload LoRaWAN
        $parsed = LoRaWANParser::parse($data);

        if (!$parsed['valid']) {
            Helper::jsonResponse(['error' => $parsed['error']], 422);
        }

        // Busca o sensor pelo DevEUI
        $sensor = Database::fetchOne(
            "SELECT id, sensor_type_id, region_id, is_active FROM sensors WHERE eui = ? LIMIT 1",
            [$parsed['sensor_eui']]
        );

        if (!$sensor) {
            Helper::jsonResponse(['error' => "Sensor EUI '{$parsed['sensor_eui']}' não registrado."], 404);
        }

        if (!$sensor['is_active']) {
            Helper::jsonResponse(['error' => 'Sensor está inativo no sistema.'], 403);
        }

        // Persiste a leitura
        Database::query(
            "INSERT INTO sensor_readings
             (sensor_id, value, raw_payload, rssi, snr, battery_pct, recorded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $sensor['id'],
                $parsed['value'],
                json_encode($data),
                $parsed['rssi']     ?? null,
                $parsed['snr']      ?? null,
                $parsed['battery']  ?? null,
                $parsed['timestamp'],
            ]
        );

        $readingId = Database::lastInsertId();

        // Atualiza last_seen_at e bateria do sensor
        Database::query(
            "UPDATE sensors SET last_seen_at = NOW(), battery_pct = COALESCE(?, battery_pct) WHERE id = ?",
            [$parsed['battery'] ?? null, $sensor['id']]
        );

        // Executa o verificador de alertas em background (assíncrono via chamada interna)
        AlertChecker::checkSensorReading((int)$sensor['id'], (float)$parsed['value'], (int)$sensor['region_id']);

        Helper::jsonResponse([
            'success'    => true,
            'reading_id' => (int)$readingId,
            'sensor_id'  => (int)$sensor['id'],
            'value'      => $parsed['value'],
            'recorded_at'=> $parsed['timestamp'],
        ], 201);
    }

    /**
     * GET /api/v1/sensors — Lista sensores com última leitura
     */
    public static function index(): void
    {
        $regionId = (int)($_GET['region_id'] ?? 0);
        $typeCode = $_GET['type'] ?? '';

        $sql = "SELECT s.id, s.eui, s.name, st.code AS type_code, st.name AS type_name,
                       st.unit, s.latitude, s.longitude, s.location_desc,
                       s.battery_pct, s.is_active, s.last_seen_at,
                       r.name AS region_name,
                       (SELECT value FROM sensor_readings
                        WHERE sensor_id = s.id ORDER BY recorded_at DESC LIMIT 1) AS last_value
                FROM sensors s
                JOIN sensor_types st ON st.id = s.sensor_type_id
                JOIN regions r ON r.id = s.region_id
                WHERE s.is_active = 1";

        $params = [];
        if ($regionId > 0) {
            $sql    .= ' AND s.region_id = ?';
            $params[] = $regionId;
        }
        if ($typeCode) {
            $sql    .= ' AND st.code = ?';
            $params[] = $typeCode;
        }

        $sensors = Database::fetchAll($sql, $params);

        Helper::jsonResponse(['sensors' => $sensors, 'total' => count($sensors)]);
    }
}
