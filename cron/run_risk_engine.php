<?php
/**
 * Cron Job: Recalcula mapas de risco preditivos a cada 15 minutos
 * Crontab: *\/15 * * * * php /path/to/appSati-alerta/cron/run_risk_engine.php
 */

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/core/Config.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Helper.php';
require_once ROOT_PATH . '/modules/analytics/TrendAnalyzer.php';
require_once ROOT_PATH . '/modules/analytics/RiskEngine.php';

use Core\Config;

Config::loadEnv(ROOT_PATH . '/.env');
date_default_timezone_set(Config::get('app')['timezone'] ?? 'America/Manaus');

echo "[" . date('Y-m-d H:i:s') . "] Recalculando mapas de risco preditivos...\n";

try {
    RiskEngine::recalculateAll();
    echo "[" . date('Y-m-d H:i:s') . "] Mapas de risco atualizados com sucesso.\n";
} catch (\Exception $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    exit(1);
}
