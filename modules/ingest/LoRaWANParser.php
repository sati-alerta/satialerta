<?php
/**
 * LoRaWANParser — Decodifica e valida payloads do ESP32 gateway
 *
 * O ESP32 atua como concentrador LoRaWAN e envia os dados via WiFi HTTP POST.
 * Suporta tanto payload direto (JSON limpo) quanto base64 encoded.
 */
class LoRaWANParser
{
    /**
     * Parseia e valida o payload recebido do ESP32
     *
     * @param array $raw Payload bruto decodificado do JSON
     * @return array {valid: bool, error?: string, sensor_eui, value, rssi, snr, battery, timestamp}
     */
    public static function parse(array $raw): array
    {
        // Suporta dados diretos (ESP32 enviando JSON) ou encapsulados (TTN/ChirpStack)
        $data = self::normalize($raw);

        if (empty($data['sensor_eui'])) {
            return ['valid' => false, 'error' => 'sensor_eui ausente ou inválido.'];
        }

        if (!isset($data['value']) || !is_numeric($data['value'])) {
            return ['valid' => false, 'error' => 'Valor numérico ausente ou inválido.'];
        }

        // Valida EUI (16 hex chars ou 8 bytes hex)
        $eui = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $data['sensor_eui']));
        if (strlen($eui) !== 16) {
            return ['valid' => false, 'error' => "DevEUI inválido: {$data['sensor_eui']} (esperado 16 hex chars)."];
        }

        // Timestamp: usa o do sensor se fornecido, senão NOW()
        $timestamp = date('Y-m-d H:i:s');
        if (!empty($data['timestamp'])) {
            $ts = strtotime($data['timestamp']);
            if ($ts !== false) {
                $timestamp = date('Y-m-d H:i:s', $ts);
            }
        }

        return [
            'valid'      => true,
            'sensor_eui' => $eui,
            'value'      => round((float)$data['value'], 4),
            'rssi'       => isset($data['rssi'])    ? (int)$data['rssi']       : null,
            'snr'        => isset($data['snr'])     ? (float)$data['snr']      : null,
            'battery'    => isset($data['battery']) ? (int)$data['battery']    : null,
            'gateway_id' => $data['gateway_id']     ?? null,
            'timestamp'  => $timestamp,
        ];
    }

    /**
     * Normaliza diferentes formatos de payload:
     * 1. Payload direto ESP32: {"sensor_eui":"...","value":...}
     * 2. Formato TTN v3: {"end_device_ids":{"dev_eui":"..."},"uplink_message":{"decoded_payload":{"value":...}}}
     * 3. Formato ChirpStack: {"devEUI":"...","object":{"value":...}}
     */
    private static function normalize(array $raw): array
    {
        // Formato direto do ESP32 (padrão do projeto)
        if (isset($raw['sensor_eui'])) {
            return $raw;
        }

        // Formato TTN v3
        if (isset($raw['end_device_ids']['dev_eui'])) {
            $eui     = $raw['end_device_ids']['dev_eui'] ?? '';
            $payload = $raw['uplink_message']['decoded_payload'] ?? [];
            return array_merge($payload, [
                'sensor_eui' => $eui,
                'rssi'       => $raw['uplink_message']['rx_metadata'][0]['rssi'] ?? null,
                'snr'        => $raw['uplink_message']['rx_metadata'][0]['snr']  ?? null,
                'timestamp'  => $raw['uplink_message']['received_at'] ?? null,
            ]);
        }

        // Formato ChirpStack
        if (isset($raw['devEUI'])) {
            $obj = $raw['object'] ?? [];
            return array_merge($obj, [
                'sensor_eui' => $raw['devEUI'] ?? '',
                'rssi'       => $raw['rxInfo'][0]['loRaSNR'] ?? null,
                'gateway_id' => $raw['gatewayID'] ?? null,
            ]);
        }

        return $raw;
    }
}
