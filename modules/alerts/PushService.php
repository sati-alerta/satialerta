<?php
/**
 * PushService — Notificações Push via OneSignal
 * Segmentação por região geográfica usando OneSignal Tags
 */

use Core\Config;

class PushService
{
    private static string $appId  = '';
    private static string $apiKey = '';

    private static function init(): void
    {
        if (self::$appId) return;
        $cfg = Config::get('app')['onesignal'];
        self::$appId  = $cfg['app_id'];
        self::$apiKey = $cfg['api_key'];
    }

    /**
     * Envia push notification para todos os usuários de uma região específica
     * Usa OneSignal Tags: {"region_id": "1"}
     */
    public static function sendToRegion(int $regionId, string $title, string $message): array
    {
        self::init();

        $payload = [
            'app_id'   => self::$appId,
            'headings' => ['en' => $title, 'pt' => $title],
            'contents' => ['en' => $message, 'pt' => $message],
            'filters'  => [
                ['field' => 'tag', 'key' => 'region_id', 'relation' => '=', 'value' => (string)$regionId],
            ],
            'priority' => 10,
            'sound'    => 'emergency_alert',
            'data'     => [
                'type'      => 'emergency_alert',
                'region_id' => $regionId,
            ],
        ];

        return self::sendRequest($payload);
    }

    /**
     * Envia push para todos os usuários do aplicativo (broadcast)
     */
    public static function sendBroadcast(string $title, string $message, array $data = []): array
    {
        self::init();

        $payload = [
            'app_id'            => self::$appId,
            'included_segments' => ['All'],
            'headings'          => ['en' => $title, 'pt' => $title],
            'contents'          => ['en' => $message, 'pt' => $message],
            'priority'          => 10,
            'data'              => array_merge(['type' => 'broadcast'], $data),
        ];

        return self::sendRequest($payload);
    }

    /**
     * Envia push para um player_id específico (dispositivo individual)
     */
    public static function sendToDevice(string $playerId, string $title, string $message): array
    {
        self::init();

        $payload = [
            'app_id'             => self::$appId,
            'include_player_ids' => [$playerId],
            'headings'           => ['en' => $title, 'pt' => $title],
            'contents'           => ['en' => $message, 'pt' => $message],
        ];

        return self::sendRequest($payload);
    }

    private static function sendRequest(array $payload): array
    {
        if (empty(self::$appId) || str_starts_with(self::$appId, 'xxxxxxxx')) {
            error_log("[Push] OneSignal não configurado. Push não enviado.");
            return ['status' => 'skipped', 'reason' => 'not_configured'];
        }

        $ch = curl_init('https://onesignal.com/api/v1/notifications');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Basic ' . self::$apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("OneSignal cURL erro: {$error}");
        }

        $body = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new \RuntimeException(
                "OneSignal erro {$httpCode}: " . json_encode($body['errors'] ?? [])
            );
        }

        return ['status' => 'sent', 'id' => $body['id'] ?? null, 'recipients' => $body['recipients'] ?? 0];
    }
}
