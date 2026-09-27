<?php
/**
 * AlertChecker — Motor de gatilhos: verifica regras e dispara alertas automáticos
 */

require_once ROOT_PATH . '/modules/alerts/SmsService.php';
require_once ROOT_PATH . '/modules/alerts/PushService.php';

use Core\Database;

class AlertChecker
{
    /**
     * Verifica todas as regras de alerta para uma nova leitura de sensor
     */
    public static function checkSensorReading(int $sensorId, float $value, int $regionId): void
    {
        // Busca tipo do sensor
        $sensor = Database::fetchOne(
            "SELECT sensor_type_id FROM sensors WHERE id = ?",
            [$sensorId]
        );
        if (!$sensor) return;

        // Busca regras ativas para este tipo de sensor
        $rules = Database::fetchAll(
            "SELECT ar.*, al.code AS level_code, al.label AS level_label, al.color_hex, al.priority
             FROM alert_rules ar
             JOIN alert_levels al ON al.id = ar.alert_level_id
             WHERE ar.sensor_type_id = ? AND ar.is_active = 1
             ORDER BY al.priority ASC",
            [$sensor['sensor_type_id']]
        );

        foreach ($rules as $rule) {
            if (!self::evaluateCondition($value, $rule['condition_op'], (float)$rule['threshold_value'])) {
                continue;
            }

            // Verifica se já existe alerta ativo para este sensor + regra
            $existing = Database::fetchOne(
                "SELECT id FROM alerts WHERE rule_id = ? AND sensor_id = ? AND is_active = 1 LIMIT 1",
                [$rule['id'], $sensorId]
            );

            if ($existing) continue; // Evita duplicatas

            // Busca nome do sensor e região para a mensagem
            $sensorData = Database::fetchOne(
                "SELECT s.name AS sensor_name, r.name AS region_name FROM sensors s JOIN regions r ON r.id = s.region_id WHERE s.id = ?",
                [$sensorId]
            );

            $sensorName = $sensorData['sensor_name']   ?? "Sensor #{$sensorId}";
            $regionName = $sensorData['region_name']   ?? "Região";

            // Monta a mensagem com o template
            $message = self::buildMessage(
                $rule['message_template'] ?? 'Alerta: {value} detectado em {sensor} na {region}.',
                $value,
                $sensorName,
                $regionName
            );

            // Insere o alerta
            Database::query(
                "INSERT INTO alerts
                 (rule_id, sensor_id, region_id, alert_level_id, trigger_value, title, message, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)",
                [
                    $rule['id'],
                    $sensorId,
                    $regionId,
                    $rule['alert_level_id'],
                    $value,
                    $rule['level_label'] . ' — ' . $regionName,
                    $message,
                ]
            );

            $alertId = (int)Database::lastInsertId();

            // Dispara notificações
            self::notifyAlert($alertId, $rule);
        }
    }

    /**
     * Dispara notificações para um alerta existente
     */
    public static function notifyAlert(int $alertId, array $rule = []): void
    {
        $alert = Database::fetchOne(
            "SELECT a.*, al.code AS level_code, r.name AS region_name, r.id AS region_id
             FROM alerts a
             JOIN alert_levels al ON al.id = a.alert_level_id
             JOIN regions r ON r.id = a.region_id
             WHERE a.id = ?",
            [$alertId]
        );

        if (!$alert) return;

        // Busca usuários da região para notificar
        $users = Database::fetchAll(
            "SELECT id, name, phone FROM users WHERE region_id = ? AND is_active = 1",
            [$alert['region_id']]
        );

        $notifiedSms  = false;
        $notifiedPush = false;

        foreach ($users as $user) {
            // SMS via Twilio
            if ((!empty($rule['notify_sms']) || $rule === []) && !empty($user['phone'])) {
                try {
                    SmsService::send($user['phone'], $alert['message']);
                    self::logNotification($alertId, $user['id'], 'sms', $user['phone'], 'sent');
                    $notifiedSms = true;
                } catch (\Exception $e) {
                    self::logNotification($alertId, $user['id'], 'sms', $user['phone'], 'failed', $e->getMessage());
                }
            }
        }

        // Push para toda a região (OneSignal segmentado por região)
        if (!empty($rule['notify_push']) || $rule === []) {
            try {
                PushService::sendToRegion(
                    (int)$alert['region_id'],
                    $alert['title'],
                    $alert['message']
                );
                $notifiedPush = true;
                self::logNotification($alertId, null, 'push', "region:{$alert['region_id']}", 'sent');
            } catch (\Exception $e) {
                error_log("[AlertChecker] Push falhou: " . $e->getMessage());
            }
        }

        // Atualiza flags de notificação
        Database::query(
            "UPDATE alerts SET notified_sms = ?, notified_push = ? WHERE id = ?",
            [(int)$notifiedSms, (int)$notifiedPush, $alertId]
        );
    }

    /**
     * Avalia uma condição simples (operator + threshold)
     */
    private static function evaluateCondition(float $value, string $op, float $threshold): bool
    {
        return match($op) {
            'gt'  => $value >  $threshold,
            'lt'  => $value <  $threshold,
            'gte' => $value >= $threshold,
            'lte' => $value <= $threshold,
            'eq'  => abs($value - $threshold) < 0.0001,
            default => false,
        };
    }

    private static function buildMessage(string $tpl, float $value, string $sensor, string $region): string
    {
        return str_replace(
            ['{value}', '{sensor}', '{region}'],
            [$value, $sensor, $region],
            $tpl
        );
    }

    private static function logNotification(
        int $alertId, ?int $userId, string $channel,
        string $destination, string $status, ?string $error = null
    ): void {
        Database::query(
            "INSERT INTO notification_log (alert_id, user_id, channel, destination, status, sent_at, error_msg)
             VALUES (?, ?, ?, ?, ?, NOW(), ?)",
            [$alertId, $userId, $channel, $destination, $status, $error]
        );
    }
}
