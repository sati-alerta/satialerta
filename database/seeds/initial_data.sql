-- ============================================================
-- SATI-Alerta — Dados Iniciais (Seeds)
-- Execute APÓS o schema.sql
-- ============================================================

USE sati_alerta;
SET NAMES utf8mb4;

-- Roles (perfis de usuário RBAC)
INSERT INTO roles (name, label, description) VALUES
('admin_gov',      'Administrador Governamental', 'Secretarias de Proteção Civil, Infraestrutura e Agricultura'),
('rural_producer', 'Produtor Rural',              'Pequenos agricultores e cooperativas'),
('public',         'Usuário Público',             'Comunidade, escolas, cidadãos em geral');

-- Níveis de Alerta
INSERT INTO alert_levels (code, label, color_hex, priority) VALUES
('INFO',      'Informativo', '#17a2b8', 4),
('WARNING',   'Atenção',     '#ffc107', 3),
('CRITICAL',  'Crítico',     '#fd7e14', 2),
('EMERGENCY', 'Emergência',  '#dc3545', 1);

-- Tipos de Sensores (catálogo modular extensível)
INSERT INTO sensor_types (code, name, unit, description, icon, min_safe, max_safe) VALUES
('AIR_QUALITY',   'Qualidade do Ar / Fumaça', 'ppm',  'Detecta partículas de fumaça e qualidade do ar', 'bi-wind',          0,    50),
('WATER_LEVEL',   'Nível de Água',             'cm',   'Nível do leito de rios, canais e bueiros',       'bi-water',         0,   200),
('AIR_HUMIDITY',  'Umidade do Ar',             '%',    'Percentual de umidade relativa do ar',            'bi-cloud-drizzle', 30,   90),
('SOIL_MOISTURE', 'Umidade do Solo',           '%',    'Percentual de umidade volumétrica do solo',       'bi-layers',        20,   80),
('RADIATION',     'Radiação Solar',            'W/m²', 'Irradiação solar global',                        'bi-sun',           0, 1200);
-- Novos tipos podem ser adicionados com INSERT sem alterar o schema:
-- ('SEISMIC',        'Atividade Sísmica',     'Richter', ...)
-- ('COASTAL_EROSION','Erosão Costeira',       'mm/dia',  ...)

-- Regiões de exemplo (Amazonas)
INSERT INTO regions (name, state, city, latitude, longitude) VALUES
('Manaus - Centro',       'AM', 'Manaus',    -3.1190, -60.0217),
('Manaus - Igarapé Zone', 'AM', 'Manaus',    -3.0876, -60.0553),
('Parintins',             'AM', 'Parintins', -2.6286, -56.7358),
('Itacoatiara',           'AM', 'Itacoatiara', -3.1428, -58.4441);

-- Usuário administrador padrão
-- Senha padrão: Admin@2025 (trocar após primeiro acesso)
INSERT INTO users (name, email, phone, password_hash, role_id, region_id, is_active) VALUES
('Administrador SATI',  'admin@sati.gov.br',     '+5592999990001', '$2y$12$sati.admin.hash.placeholder..A', 1, 1, 1),
('João da Silva',       'joao@cooperativa.am',   '+5592999990002', '$2y$12$sati.producer.hash.placeholder', 2, 1, 1),
('Maria Comunidade',    'maria@escola.am.gov.br','+5592999990003', '$2y$12$sati.public.hash.placeholder..', 3, 1, 1);

-- Sensores de exemplo (com DevEUI fictício)
INSERT INTO sensors (eui, name, sensor_type_id, region_id, latitude, longitude, location_desc, installed_at, is_active) VALUES
('A1B2C3D4E5F60001', 'Sensor Igarapé do Mindu - Nível', 2, 2, -3.0921, -60.0489, 'Ponte Nível Igarapé do Mindu', '2025-01-15', 1),
('A1B2C3D4E5F60002', 'Sensor AQ - Porto de Manaus',     1, 1, -3.1307, -60.0231, 'Porto de Manaus - Qualidade Ar', '2025-01-20', 1),
('A1B2C3D4E5F60003', 'Sensor Solo - Km 45 AM-010',      4, 3, -2.7500, -56.9200, 'Lavoura km 45 rodovia AM-010', '2025-02-01', 1),
('A1B2C3D4E5F60004', 'Sensor Umidade - Parintins',      3, 3, -2.6310, -56.7390, 'Estação Meteorológica Parintins', '2025-02-10', 1);

-- Regras de alerta automáticas
INSERT INTO alert_rules (name, sensor_type_id, alert_level_id, condition_op, threshold_value, duration_min, message_template, notify_sms, notify_push) VALUES
('Nível Água Atenção',      2, 2, 'gte', 150.0, 10, 'ATENÇÃO: Nível de água em {sensor} chegou a {value}cm na região {region}. Monitore a situação.', 1, 1),
('Nível Água Crítico',      2, 3, 'gte', 200.0,  5, 'CRÍTICO: Risco de inundação em {region}! Nível de água = {value}cm no sensor {sensor}.', 1, 1),
('Nível Água Emergência',   2, 4, 'gte', 250.0,  2, '🚨 EMERGÊNCIA: INUNDAÇÃO IMINENTE em {region}! Nível = {value}cm. Evacue áreas de risco!', 1, 1),
('Fumaça Detectada',        1, 3, 'gte',  80.0,  5, 'ALERTA: Fumaça detectada em {region} ({value} ppm). Possível foco de incêndio. Verifique imediatamente.', 1, 1),
('Qualidade Ar Emergência', 1, 4, 'gte', 150.0,  2, '🚨 EMERGÊNCIA: Qualidade do ar PERIGOSA em {region} ({value} ppm). Mantenha-se em local fechado!', 1, 1),
('Solo Seco Atenção',       4, 2, 'lte',  25.0, 60, 'ATENÇÃO: Solo muito seco em {region} ({value}%). Risco de seca. Planeje irrigação.', 0, 1),
('Solo Crítico'   ,         4, 3, 'lte',  15.0, 30, 'CRÍTICO: Umidade do solo extremamente baixa em {region} ({value}%). Alto risco de perda de lavoura.', 1, 1);

-- Chave de API para gateway ESP32 (hash SHA-256 de "sati_gateway_secret_key_change_this")
INSERT INTO api_keys (name, key_hash, gateway_id, region_id, is_active) VALUES
('Gateway ESP32 - Manaus Centro', SHA2('sati_gateway_secret_key_change_this', 256), 'GW-MAN-001', 1, 1);
