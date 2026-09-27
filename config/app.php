<?php
// config/app.php — Configurações gerais da aplicação
return [
    'name'     => $_ENV['APP_NAME']     ?? 'SATI-Alerta',
    'env'      => $_ENV['APP_ENV']      ?? 'production',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'      => $_ENV['APP_URL']      ?? 'http://localhost/appSati-alerta',
    'key'      => $_ENV['APP_KEY']      ?? '',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Manaus',

    'session' => [
        'name'     => $_ENV['SESSION_NAME']     ?? 'sati_session',
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
    ],

    'gateway_api_key' => $_ENV['GATEWAY_API_KEY'] ?? '',

    'twilio' => [
        'sid'   => $_ENV['TWILIO_SID']   ?? '',
        'token' => $_ENV['TWILIO_TOKEN'] ?? '',
        'from'  => $_ENV['TWILIO_FROM']  ?? '',
    ],

    'onesignal' => [
        'app_id'  => $_ENV['ONESIGNAL_APP_ID']  ?? '',
        'api_key' => $_ENV['ONESIGNAL_API_KEY'] ?? '',
    ],

    // Limites do motor de risco preditivo
    'risk_engine' => [
        'water_level_trend_window_min' => 30,   // minutos para calcular tendência
        'humidity_drop_threshold'      => 15,    // queda de % em 1h para alerta seca
        'air_quality_ma_window'        => 5,     // media movel: N leituras
    ],
];
