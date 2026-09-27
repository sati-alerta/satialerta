<?php
/**
 * Cron Job: Verificador de alertas a cada 1 minuto
 * Adicionar ao crontab: * * * * * php /path/to/appSati-alerta/cron/run_alert_checker.php
 */

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/core/Config.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Helper.php';
require_once ROOT_PATH . '/modules/alerts/AlertChecker.php';
require_once ROOT_PATH . '/modules/alerts/SmsService.php';
require_once ROOT_PATH . '/modules/alerts/PushService.php';

use Core\Config;
use Core\Database;

Config::loadEnv(ROOT_PATH . '/.env');
date_default_timezone_set(Config::get('app')['timezone'] ?? 'America/Manaus');

echo "[" . date('Y-m-d H:i:s') . "] Verificando alertas pendentes...\n";

// Busca alertas com notificações pendentes (falhas anteriores)
$pendingAlerts = Database::fetchAll(
    "SELECT a.id FROM alerts a
     WHERE a.is_active = 1
       AND (a.notified_sms = 0 OR a.notified_push = 0)
       AND a.triggered_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
);

foreach ($pendingAlerts as $alert) {
    try {
        AlertChecker::notifyAlert((int)$alert['id']);
        echo "  → Alerta #{$alert['id']} notificado.\n";
    } catch (\Exception $e) {
        echo "  [ERRO] Alerta #{$alert['id']}: " . $e->getMessage() . "\n";
    }
}

// Auto-resolve alertas de nível de água que caíram abaixo do limiar
$waterSensors = Database::fetchAll(
    "SELECT DISTINCT a.id AS alert_id, a.sensor_id, ar.threshold_value, ar.condition_op
     FROM alerts a
     JOIN alert_rules ar ON ar.id = a.rule_id
     JOIN sensor_types st ON st.id = ar.sensor_type_id
     WHERE st.code = 'WATER_LEVEL' AND a.is_active = 1"
);

foreach ($waterSensors as $wa) {
    $last = Database::fetchOne(
        "SELECT value FROM sensor_readings WHERE sensor_id = ? ORDER BY recorded_at DESC LIMIT 1",
        [$wa['sensor_id']]
    );
    if (!$last) continue;

    // Se o nível caiu abaixo do threshold, resolve o alerta
    if ((float)$last['value'] < (float)$wa['threshold_value'] * 0.85) {
        Database::query(
            "UPDATE alerts SET is_active = 0, resolved_at = NOW() WHERE id = ?",
            [$wa['alert_id']]
        );
        echo "  ✓ Alerta #{$wa['alert_id']} auto-resolvido (nível normalizado).\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Verificação concluída.\n";
