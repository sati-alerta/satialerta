<?php
/**
 * SmsService — Envio de SMS emergenciais via Twilio
 * Crucial para áreas rurais com baixa conectividade de internet
 */

use Core\Config;

class SmsService
{
    private static string $sid   = '';
    private static string $token = '';
    private static string $from  = '';

    private static function init(): void
    {
        if (self::$sid) return;
        $cfg = Config::get('app')['twilio'];
        self::$sid   = $cfg['sid'];
        self::$token = $cfg['token'];
        self::$from  = $cfg['from'];
    }

    /**
     * Envia SMS para um número de telefone via Twilio API
     *
     * @param string $to   Número E.164: +5592999990001
     * @param string $body Mensagem (máx 160 chars recomendado para SMS simples)
     */
    public static function send(string $to, string $body): array
    {
        self::init();

        if (empty(self::$sid) || self::$sid === 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx') {
            error_log("[SMS] Twilio não configurado. SMS para {$to} não enviado.");
            return ['status' => 'skipped', 'reason' => 'not_configured'];
        }

        // Trunca mensagem para 160 chars (SMS padrão)
        $body = mb_substr($body, 0, 160);

        $url  = "https://api.twilio.com/2010-04-01/Accounts/" . self::$sid . "/Messages.json";
        $payload = http_build_query([
            'To'   => $to,
            'From' => self::$from,
            'Body' => $body,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERPWD        => self::$sid . ':' . self::$token,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("Twilio cURL erro: {$error}");
        }

        $body = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new \RuntimeException(
                "Twilio erro {$httpCode}: " . ($body['message'] ?? 'Erro desconhecido')
            );
        }

        return [
            'status' => 'sent',
            'sid'    => $body['sid'] ?? null,
        ];
    }

    /**
     * Envia SMS em lote para múltiplos números
     */
    public static function sendBulk(array $phones, string $message): array
    {
        $results = [];
        foreach ($phones as $phone) {
            try {
                $results[$phone] = self::send($phone, $message);
            } catch (\Exception $e) {
                $results[$phone] = ['status' => 'failed', 'error' => $e->getMessage()];
            }
        }
        return $results;
    }
}
