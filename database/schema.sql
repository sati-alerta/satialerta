-- ============================================================
-- SATI-Alerta — Schema SQL Completo
-- MySQL 8.0+ | UTF8MB4 | InnoDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS sati_alerta
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sati_alerta;

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION';

-- ============================================================
-- 1. CONTROLE DE ACESSO — RBAC
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
    id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50)  NOT NULL UNIQUE,
    label       VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(180) NOT NULL UNIQUE,
    phone         VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role_id       TINYINT UNSIGNED NOT NULL,
    region_id     INT UNSIGNED NULL,
    is_active     BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- ============================================================
-- 2. GEOGRAFIA E REGIÕES
-- ============================================================
CREATE TABLE IF NOT EXISTS regions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    state       CHAR(2)     NOT NULL,
    city        VARCHAR(100),
    latitude    DECIMAL(10,7),
    longitude   DECIMAL(10,7),
    geojson     JSON COMMENT 'Polígono GeoJSON da região para o mapa',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 3. CATÁLOGO DE TIPOS DE SENSORES (modular/extensível)
-- Adicionar novo tipo = INSERT, sem alterar o schema
-- ============================================================
CREATE TABLE IF NOT EXISTS sensor_types (
    id           SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(50) NOT NULL UNIQUE,
    name         VARCHAR(100) NOT NULL,
    unit         VARCHAR(20) COMMENT 'Unidade de medida: ppm, cm, %, µSv/h etc.',
    description  TEXT,
    icon         VARCHAR(50)  DEFAULT 'bi-cpu',
    min_safe     DECIMAL(12,4) COMMENT 'Limite mínimo seguro',
    max_safe     DECIMAL(12,4) COMMENT 'Limite máximo seguro',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 4. REGISTRO DE SENSORES IoT (Gateways ESP32/LoRaWAN)
-- ============================================================
CREATE TABLE IF NOT EXISTS sensors (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    eui             VARCHAR(32) NOT NULL UNIQUE COMMENT 'DevEUI LoRaWAN único do sensor',
    name            VARCHAR(100),
    sensor_type_id  SMALLINT UNSIGNED NOT NULL,
    region_id       INT UNSIGNED NOT NULL,
    latitude        DECIMAL(10,7) NOT NULL,
    longitude       DECIMAL(10,7) NOT NULL,
    altitude_m      DECIMAL(8,2),
    location_desc   VARCHAR(255) COMMENT 'Descrição textual da localização',
    installed_at    DATE,
    last_seen_at    TIMESTAMP NULL,
    battery_pct     TINYINT UNSIGNED DEFAULT 100,
    is_active       BOOLEAN DEFAULT TRUE,
    metadata        JSON COMMENT 'Configurações extras por tipo de sensor',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_type_id) REFERENCES sensor_types(id),
    FOREIGN KEY (region_id)      REFERENCES regions(id)
);

-- ============================================================
-- 5. SÉRIE TEMPORAL DE LEITURAS (core da ingestão IoT)
-- Payload recebido via HTTP POST do ESP32 gateway
-- ============================================================
CREATE TABLE IF NOT EXISTS sensor_readings (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor_id   INT UNSIGNED NOT NULL,
    value       DECIMAL(14,4) NOT NULL,
    raw_payload JSON COMMENT 'Payload bruto recebido do ESP32 para auditoria',
    rssi        SMALLINT     COMMENT 'RSSI do sinal LoRa em dBm',
    snr         DECIMAL(5,2) COMMENT 'SNR do sinal LoRa',
    battery_pct TINYINT UNSIGNED,
    recorded_at TIMESTAMP NOT NULL   COMMENT 'Timestamp do sensor (UTC)',
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Quando a API recebeu',
    INDEX idx_sensor_time (sensor_id, recorded_at DESC),
    INDEX idx_recorded_at (recorded_at DESC),
    FOREIGN KEY (sensor_id) REFERENCES sensors(id)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED;

-- ============================================================
-- 6. SISTEMA DE ALERTAS
-- ============================================================
CREATE TABLE IF NOT EXISTS alert_levels (
    id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20) NOT NULL UNIQUE,
    label       VARCHAR(50) NOT NULL,
    color_hex   CHAR(7)     NOT NULL,
    priority    TINYINT UNSIGNED NOT NULL COMMENT '1=mais alto'
);

CREATE TABLE IF NOT EXISTS alert_rules (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    sensor_type_id  SMALLINT UNSIGNED NOT NULL,
    alert_level_id  TINYINT UNSIGNED NOT NULL,
    condition_op    ENUM('gt','lt','gte','lte','eq') NOT NULL,
    threshold_value DECIMAL(14,4) NOT NULL,
    duration_min    SMALLINT DEFAULT 0 COMMENT 'Minutos acima do limiar para disparar',
    message_template TEXT COMMENT 'Template da mensagem: use {value}, {sensor}, {region}',
    notify_sms      BOOLEAN DEFAULT TRUE,
    notify_push     BOOLEAN DEFAULT TRUE,
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_type_id) REFERENCES sensor_types(id),
    FOREIGN KEY (alert_level_id) REFERENCES alert_levels(id)
);

CREATE TABLE IF NOT EXISTS alerts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_id         INT UNSIGNED NULL COMMENT 'NULL = disparo manual',
    sensor_id       INT UNSIGNED NULL,
    region_id       INT UNSIGNED NOT NULL,
    alert_level_id  TINYINT UNSIGNED NOT NULL,
    trigger_value   DECIMAL(14,4) NULL,
    title           VARCHAR(255) NOT NULL,
    message         TEXT NOT NULL,
    triggered_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at     TIMESTAMP NULL,
    is_active       BOOLEAN DEFAULT TRUE,
    notified_sms    BOOLEAN DEFAULT FALSE,
    notified_push   BOOLEAN DEFAULT FALSE,
    created_by      INT UNSIGNED NULL COMMENT 'NULL = automático, ID = usuário manual',
    INDEX idx_region_active (region_id, is_active),
    INDEX idx_triggered_at (triggered_at DESC),
    FOREIGN KEY (rule_id)        REFERENCES alert_rules(id),
    FOREIGN KEY (sensor_id)      REFERENCES sensors(id),
    FOREIGN KEY (region_id)      REFERENCES regions(id),
    FOREIGN KEY (alert_level_id) REFERENCES alert_levels(id),
    FOREIGN KEY (created_by)     REFERENCES users(id)
);

-- ============================================================
-- 7. LOG DE NOTIFICAÇÕES / MENSAGERIA
-- ============================================================
CREATE TABLE IF NOT EXISTS notification_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alert_id    BIGINT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NULL COMMENT 'NULL = broadcast de região',
    channel     ENUM('sms','push','email') NOT NULL,
    destination VARCHAR(255) NOT NULL COMMENT 'Número, FCM token ou e-mail',
    status      ENUM('pending','sent','failed') DEFAULT 'pending',
    provider_id VARCHAR(100) COMMENT 'SID Twilio ou ID OneSignal',
    sent_at     TIMESTAMP NULL,
    error_msg   TEXT,
    FOREIGN KEY (alert_id) REFERENCES alerts(id)
);

-- ============================================================
-- 8. MAPAS DE RISCO PREDITIVOS (output do analytics)
-- ============================================================
CREATE TABLE IF NOT EXISTS risk_maps (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_id       INT UNSIGNED NOT NULL,
    risk_type       ENUM('flood','drought','fire','combined') NOT NULL,
    risk_level      TINYINT UNSIGNED NOT NULL COMMENT '0-100',
    risk_label      VARCHAR(50) COMMENT 'baixo / moderado / alto / crítico',
    geojson_data    LONGTEXT COMMENT 'Polígonos de risco calculados em GeoJSON',
    model_version   VARCHAR(20) DEFAULT '1.0',
    confidence_pct  TINYINT UNSIGNED DEFAULT 70 COMMENT 'Confiança do modelo %',
    generated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_until     TIMESTAMP NULL,
    INDEX idx_region_type (region_id, risk_type),
    FOREIGN KEY (region_id) REFERENCES regions(id)
);

-- ============================================================
-- 9. RELATÓRIOS CLIMÁTICOS
-- ============================================================
CREATE TABLE IF NOT EXISTS reports (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_id     INT UNSIGNED NOT NULL,
    type          ENUM('flood','drought','fire','infrastructure','agriculture','general') NOT NULL,
    title         VARCHAR(255) NOT NULL,
    summary       TEXT,
    content       LONGTEXT,
    file_path     VARCHAR(500) COMMENT 'Caminho para PDF gerado',
    generated_by  ENUM('auto','manual') DEFAULT 'auto',
    author_id     INT UNSIGNED NULL,
    is_public     BOOLEAN DEFAULT FALSE COMMENT 'Visível na plataforma pública',
    views_count   INT UNSIGNED DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_region_type (region_id, type),
    FOREIGN KEY (region_id) REFERENCES regions(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- ============================================================
-- 10. API KEYS (para gateways ESP32)
-- ============================================================
CREATE TABLE IF NOT EXISTS api_keys (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    key_hash    VARCHAR(255) NOT NULL UNIQUE COMMENT 'SHA-256 da API key',
    gateway_id  VARCHAR(50) COMMENT 'Identificador do gateway ESP32',
    region_id   INT UNSIGNED NULL,
    is_active   BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (region_id) REFERENCES regions(id)
);

-- ============================================================
-- 11. AUDITORIA COMPLETA
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(100) NOT NULL,
    table_name  VARCHAR(64),
    record_id   BIGINT UNSIGNED,
    old_value   JSON,
    new_value   JSON,
    ip_address  VARCHAR(45),
    user_agent  VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_action (user_id, action),
    INDEX idx_created_at (created_at DESC)
);

SET FOREIGN_KEY_CHECKS = 1;
