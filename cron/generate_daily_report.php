<?php
/**
 * Cron Job: Gera relatórios climáticos automáticos diários
 * Crontab: 0 6 * * * php /path/to/appSati-alerta/cron/generate_daily_report.php
 */

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/core/Config.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Helper.php';
require_once ROOT_PATH . '/modules/analytics/TrendAnalyzer.php';
require_once ROOT_PATH . '/modules/analytics/RiskEngine.php';
require_once ROOT_PATH . '/modules/reports/ReportGenerator.php';

use Core\Config;

Config::loadEnv(ROOT_PATH . '/.env');
date_default_timezone_set(Config::get('app')['timezone'] ?? 'America/Manaus');

echo "[" . date('Y-m-d H:i:s') . "] Gerando relatórios climáticos diários...\n";

try {
    ReportGenerator::generateDailyAll();
    echo "[" . date('Y-m-d H:i:s') . "] Relatórios gerados com sucesso.\n";
} catch (\Exception $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    exit(1);
}
