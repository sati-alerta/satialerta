-- ============================================================
-- SATI-Alerta — Dados Demo / Fictícios para Demonstração
-- Execute APÓS o schema.sql e initial_data.sql
-- ============================================================

USE sati_alerta;
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- REGIÕES — Bauru/SP + mais regiões do Amazonas
-- ============================================================
INSERT INTO regions (name, state, city, latitude, longitude) VALUES
('Bauru - Centro',         'SP', 'Bauru',       -22.3147, -49.0625),
('Bauru - Vila Falcão',    'SP', 'Bauru',       -22.3362, -49.0778),
('Bauru - Vargem Limpa',   'SP', 'Bauru',       -22.2897, -49.0414),
('Manaus - Zona Norte',    'AM', 'Manaus',      -3.0347,  -60.0278),
('Manaus - Zona Sul',      'AM', 'Manaus',      -3.1574,  -60.0189),
('Itacoatiara - Centro',   'AM', 'Itacoatiara', -3.1448,  -58.4421);

-- ============================================================
-- SENSORES — Bauru + expansão Amazonas
-- ============================================================
INSERT INTO sensors (eui, name, sensor_type_id, region_id, latitude, longitude, location_desc, installed_at, battery_pct, last_seen_at, is_active) VALUES
('B1B2C3D4E5F60001', 'Sensor Nível - Rio Bauru Centro',        2, 5, -22.3201, -49.0690, 'Leito do Ribeirão Bauru - Ponte da Av. Rodrigues Alves', '2025-03-10', 87, DATE_SUB(NOW(), INTERVAL 12 MINUTE), 1),
('B1B2C3D4E5F60002', 'Sensor AQ - Terminal Rodoviário Bauru',  1, 5, -22.3147, -49.0625, 'Av. Rodrigues Alves 6-50 - Terminal Rodoviário',        '2025-03-15', 92, DATE_SUB(NOW(), INTERVAL  8 MINUTE), 1),
('B1B2C3D4E5F60003', 'Sensor Umidade Solo - Fazenda Vargem',   4, 7, -22.2897, -49.0414, 'Fazenda Modelo - Zona Rural Vargem Limpa',              '2025-04-01', 76, DATE_SUB(NOW(), INTERVAL 25 MINUTE), 1),
('B1B2C3D4E5F60004', 'Sensor Umidade Ar - Bauru Vila Falcão',  3, 6, -22.3362, -49.0778, 'Praça da Vila Falcão - Estação Meteorológica',          '2025-04-05', 63, DATE_SUB(NOW(), INTERVAL  5 MINUTE), 1),
('B1B2C3D4E5F60005', 'Sensor Radiação Solar - Bauru UNESP',    5, 5, -22.3207, -49.0636, 'Campus UNESP Bauru - Telhado Bloco Engenharia',         '2025-04-10', 99, DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1),
('A1B2C3D4E5F60005', 'Sensor Nível - Igarapé Tarumã',          2, 8, -3.0347,  -60.0278, 'Igarapé do Tarumã - Ponte da AM-070',                   '2025-02-20', 54, DATE_SUB(NOW(), INTERVAL 40 MINUTE), 1),
('A1B2C3D4E5F60006', 'Sensor AQ - Zona Sul Manaus',            1, 9, -3.1574,  -60.0189, 'Av. Constantino Nery - Zona Sul Industrial',            '2025-02-25', 71, DATE_SUB(NOW(), INTERVAL 18 MINUTE), 1),
('A1B2C3D4E5F60007', 'Sensor Umidade Solo - Itacoatiara',      4, 10,-3.1448,  -58.4421, 'Lavoura Experimental AM-010 km 270',                    '2025-03-01', 88, DATE_SUB(NOW(), INTERVAL 22 MINUTE), 1);

-- ============================================================
-- LEITURAS DE SENSORES — últimas 48h
-- Sensor id 5 = Nível Rio Bauru  |  6 = AQ Bauru
-- 7 = Umidade Solo Vargem | 8 = Umidade Ar Vila Falcão
-- 9 = Nível Igarapé Manaus Norte | 10 = AQ Zona Sul Manaus
-- ============================================================

-- Nível de Água - Rio Bauru (sensor 5) — simulando curva de enchente e descida
INSERT INTO sensor_readings (sensor_id, value, rssi, snr, battery_pct, recorded_at) VALUES
(5,  42.0, -87, 7.2, 87, DATE_SUB(NOW(), INTERVAL 47 HOUR)),
(5,  45.5, -85, 7.5, 87, DATE_SUB(NOW(), INTERVAL 46 HOUR)),
(5,  51.2, -84, 7.8, 87, DATE_SUB(NOW(), INTERVAL 45 HOUR)),
(5,  58.7, -83, 8.0, 87, DATE_SUB(NOW(), INTERVAL 44 HOUR)),
(5,  67.3, -82, 8.2, 87, DATE_SUB(NOW(), INTERVAL 43 HOUR)),
(5,  79.1, -81, 8.5, 87, DATE_SUB(NOW(), INTERVAL 42 HOUR)),
(5,  91.4, -80, 8.7, 87, DATE_SUB(NOW(), INTERVAL 41 HOUR)),
(5, 108.6, -79, 9.0, 87, DATE_SUB(NOW(), INTERVAL 40 HOUR)),
(5, 125.2, -79, 9.1, 87, DATE_SUB(NOW(), INTERVAL 39 HOUR)),
(5, 139.8, -78, 9.3, 87, DATE_SUB(NOW(), INTERVAL 38 HOUR)),
(5, 152.4, -77, 9.5, 87, DATE_SUB(NOW(), INTERVAL 37 HOUR)),
(5, 163.7, -77, 9.4, 87, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(5, 178.9, -76, 9.2, 87, DATE_SUB(NOW(), INTERVAL 35 HOUR)),
(5, 190.1, -76, 9.0, 87, DATE_SUB(NOW(), INTERVAL 34 HOUR)),
(5, 201.5, -75, 8.8, 87, DATE_SUB(NOW(), INTERVAL 33 HOUR)),
(5, 213.8, -75, 8.6, 87, DATE_SUB(NOW(), INTERVAL 32 HOUR)),
(5, 224.3, -74, 8.4, 87, DATE_SUB(NOW(), INTERVAL 31 HOUR)),
(5, 218.6, -74, 8.5, 87, DATE_SUB(NOW(), INTERVAL 30 HOUR)),
(5, 207.2, -75, 8.7, 87, DATE_SUB(NOW(), INTERVAL 28 HOUR)),
(5, 192.4, -76, 9.0, 87, DATE_SUB(NOW(), INTERVAL 26 HOUR)),
(5, 174.1, -77, 9.2, 87, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(5, 158.9, -78, 9.4, 87, DATE_SUB(NOW(), INTERVAL 22 HOUR)),
(5, 143.7, -79, 9.5, 87, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(5, 128.3, -80, 9.3, 87, DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(5, 112.6, -81, 9.1, 87, DATE_SUB(NOW(), INTERVAL 16 HOUR)),
(5,  98.4, -82, 8.9, 87, DATE_SUB(NOW(), INTERVAL 14 HOUR)),
(5,  85.2, -83, 8.7, 87, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(5,  74.8, -84, 8.5, 87, DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(5,  65.3, -85, 8.3, 87, DATE_SUB(NOW(), INTERVAL  8 HOUR)),
(5,  57.9, -86, 8.1, 87, DATE_SUB(NOW(), INTERVAL  6 HOUR)),
(5,  52.4, -87, 7.9, 87, DATE_SUB(NOW(), INTERVAL  4 HOUR)),
(5,  48.7, -87, 7.7, 87, DATE_SUB(NOW(), INTERVAL  2 HOUR)),
(5,  45.1, -88, 7.5, 87, DATE_SUB(NOW(), INTERVAL  1 HOUR)),
(5,  43.2, -88, 7.4, 87, DATE_SUB(NOW(), INTERVAL 30 MINUTE));

-- Qualidade do Ar - Bauru Terminal (sensor 6) — pico de fumaça
INSERT INTO sensor_readings (sensor_id, value, rssi, snr, battery_pct, recorded_at) VALUES
(6, 12.3, -90, 6.5, 92, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(6, 14.7, -89, 6.7, 92, DATE_SUB(NOW(), INTERVAL 22 HOUR)),
(6, 18.2, -89, 6.9, 92, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(6, 22.5, -88, 7.1, 92, DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(6, 31.8, -87, 7.3, 92, DATE_SUB(NOW(), INTERVAL 16 HOUR)),
(6, 45.2, -86, 7.5, 92, DATE_SUB(NOW(), INTERVAL 14 HOUR)),
(6, 62.4, -85, 7.8, 92, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(6, 71.9, -84, 8.0, 92, DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(6, 88.3, -83, 8.2, 92, DATE_SUB(NOW(), INTERVAL  8 HOUR)),
(6, 95.7, -82, 8.4, 92, DATE_SUB(NOW(), INTERVAL  6 HOUR)),
(6, 78.4, -83, 8.1, 92, DATE_SUB(NOW(), INTERVAL  4 HOUR)),
(6, 54.2, -85, 7.7, 92, DATE_SUB(NOW(), INTERVAL  2 HOUR)),
(6, 38.6, -87, 7.3, 92, DATE_SUB(NOW(), INTERVAL  1 HOUR)),
(6, 28.1, -89, 7.0, 92, DATE_SUB(NOW(), INTERVAL 30 MINUTE));

-- Umidade Solo - Vargem Limpa (sensor 7) — tendência de seca
INSERT INTO sensor_readings (sensor_id, value, rssi, snr, battery_pct, recorded_at) VALUES
(7, 58.2, -95, 5.1, 76, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(7, 55.7, -95, 5.2, 76, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(7, 51.3, -94, 5.4, 76, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(7, 46.8, -94, 5.5, 76, DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(7, 40.2, -93, 5.7, 76, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(7, 33.5, -93, 5.8, 76, DATE_SUB(NOW(), INTERVAL  6 HOUR)),
(7, 28.9, -92, 6.0, 76, DATE_SUB(NOW(), INTERVAL  2 HOUR)),
(7, 24.1, -92, 6.1, 76, DATE_SUB(NOW(), INTERVAL  1 HOUR));

-- Umidade Ar - Vila Falcão (sensor 8)
INSERT INTO sensor_readings (sensor_id, value, rssi, snr, battery_pct, recorded_at) VALUES
(8, 78.4, -91, 6.2, 63, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(8, 74.1, -91, 6.3, 63, DATE_SUB(NOW(), INTERVAL  8 HOUR)),
(8, 68.7, -90, 6.5, 63, DATE_SUB(NOW(), INTERVAL  4 HOUR)),
(8, 62.3, -90, 6.6, 63, DATE_SUB(NOW(), INTERVAL  2 HOUR)),
(8, 57.8, -91, 6.4, 63, DATE_SUB(NOW(), INTERVAL  1 HOUR));

-- Nível - Igarapé Tarumã Manaus Norte (sensor 9) — subindo
INSERT INTO sensor_readings (sensor_id, value, rssi, snr, battery_pct, recorded_at) VALUES
(9,  89.4, -88, 7.0, 54, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(9, 110.2, -87, 7.3, 54, DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(9, 134.7, -86, 7.6, 54, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(9, 158.3, -85, 7.9, 54, DATE_SUB(NOW(), INTERVAL  6 HOUR)),
(9, 172.6, -84, 8.1, 54, DATE_SUB(NOW(), INTERVAL  2 HOUR)),
(9, 181.4, -84, 8.2, 54, DATE_SUB(NOW(), INTERVAL  1 HOUR));

-- AQ Zona Sul Manaus (sensor 10) — fumaça industrial
INSERT INTO sensor_readings (sensor_id, value, rssi, snr, battery_pct, recorded_at) VALUES
(10, 35.4, -92, 6.1, 71, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(10, 48.7, -91, 6.4, 71, DATE_SUB(NOW(), INTERVAL  8 HOUR)),
(10, 72.3, -90, 6.7, 71, DATE_SUB(NOW(), INTERVAL  4 HOUR)),
(10, 83.9, -89, 6.9, 71, DATE_SUB(NOW(), INTERVAL  2 HOUR)),
(10, 67.4, -90, 6.6, 71, DATE_SUB(NOW(), INTERVAL  1 HOUR));

-- ============================================================
-- ALERTAS FICTÍCIOS — vários níveis e regiões
-- ============================================================
INSERT INTO alerts (rule_id, sensor_id, region_id, alert_level_id, trigger_value, title, message, triggered_at, resolved_at, is_active, notified_sms, notified_push) VALUES

(1,  5, 5, 2, 152.4,
 'Atenção: Nível d''água elevado — Rio Bauru Centro',
 'ATENÇÃO: Nível de água em Sensor Nível - Rio Bauru Centro chegou a 152cm na região Bauru - Centro. Monitore a situação.',
 DATE_SUB(NOW(), INTERVAL 37 HOUR), DATE_SUB(NOW(), INTERVAL 26 HOUR), 0, 1, 1),

(2,  5, 5, 3, 213.8,
 'Crítico: Risco de inundação — Rio Bauru',
 'CRÍTICO: Risco de inundação em Bauru - Centro! Nível de água = 213cm no sensor Sensor Nível - Rio Bauru Centro.',
 DATE_SUB(NOW(), INTERVAL 32 HOUR), DATE_SUB(NOW(), INTERVAL 24 HOUR), 0, 1, 1),

(4,  6, 5, 3, 88.3,
 'Alerta: Fumaça detectada — Terminal Rodoviário Bauru',
 'ALERTA: Fumaça detectada em Bauru - Centro (88 ppm). Possível foco de incêndio. Verifique imediatamente.',
 DATE_SUB(NOW(), INTERVAL 8 HOUR), NULL, 1, 1, 1),

(6,  7, 7, 2, 24.1,
 'Atenção: Solo muito seco — Fazenda Vargem Limpa',
 'ATENÇÃO: Solo muito seco em Bauru - Vargem Limpa (24%). Risco de seca. Planeje irrigação.',
 DATE_SUB(NOW(), INTERVAL 1 HOUR), NULL, 1, 0, 1),

(1,  9, 8, 2, 172.6,
 'Atenção: Igarapé do Tarumã com nível elevado',
 'ATENÇÃO: Nível de água em Sensor Nível - Igarapé Tarumã chegou a 172cm na região Manaus - Zona Norte.',
 DATE_SUB(NOW(), INTERVAL 2 HOUR), NULL, 1, 1, 1),

(4, 10, 9, 3, 83.9,
 'Alerta: Qualidade do ar comprometida — Zona Sul Manaus',
 'ALERTA: Fumaça detectada em Manaus - Zona Sul (83 ppm). Possível foco de incêndio na área industrial.',
 DATE_SUB(NOW(), INTERVAL 2 HOUR), NULL, 1, 1, 1),

(3,  1, 2, 4, 267.3,
 'EMERGÊNCIA: Inundação iminente — Igarapé do Mindu',
 'EMERGÊNCIA: INUNDAÇÃO IMINENTE em Manaus - Igarapé Zone! Nível = 267cm. Evacuação de risco concluída.',
 DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY), 0, 1, 1),

(5,  2, 1, 4, 162.0,
 'EMERGÊNCIA: Qualidade do ar perigosa — Porto de Manaus',
 'EMERGÊNCIA: Qualidade do ar PERIGOSA em Manaus - Centro (162 ppm). Manter-se em local fechado.',
 DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY), 0, 1, 1),

(NULL, NULL, 5, 1, NULL,
 'Informativo: Previsão de chuvas intensas para Bauru — SP',
 'Previsão meteorológica indica precipitação de até 80mm nas próximas 24h para Bauru e região. Evite áreas de risco.',
 DATE_SUB(NOW(), INTERVAL 3 HOUR), NULL, 1, 0, 1),

(NULL, NULL, 1, 1, NULL,
 'Informativo: Período de estiagem previsto para AM',
 'CEMADEN prevê período de estiagem prolongada para a região do Amazonas. Produtores rurais devem reforçar reservatórios.',
 DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, 1, 0, 1);

-- ============================================================
-- MAPAS DE RISCO — por região e tipo
-- ============================================================
INSERT INTO risk_maps (region_id, risk_type, risk_level, risk_label, model_version, confidence_pct, valid_until) VALUES
(5, 'flood',    72, 'alto',     '1.2', 84, DATE_ADD(NOW(), INTERVAL 12 HOUR)),
(5, 'drought',  28, 'baixo',    '1.2', 79, DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(5, 'fire',     65, 'alto',     '1.2', 76, DATE_ADD(NOW(), INTERVAL 12 HOUR)),
(5, 'combined', 68, 'alto',     '1.2', 81, DATE_ADD(NOW(), INTERVAL 12 HOUR)),
(6, 'flood',    45, 'moderado', '1.2', 77, DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(6, 'combined', 45, 'moderado', '1.2', 74, DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(7, 'drought',  81, 'crítico',  '1.2', 88, DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(7, 'fire',     74, 'alto',     '1.2', 82, DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(7, 'combined', 78, 'alto',     '1.2', 85, DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(1, 'flood',    88, 'crítico',  '1.2', 91, DATE_ADD(NOW(), INTERVAL  6 HOUR)),
(1, 'combined', 85, 'crítico',  '1.2', 89, DATE_ADD(NOW(), INTERVAL  6 HOUR)),
(2, 'flood',    93, 'crítico',  '1.2', 93, DATE_ADD(NOW(), INTERVAL  6 HOUR)),
(2, 'combined', 90, 'crítico',  '1.2', 91, DATE_ADD(NOW(), INTERVAL  6 HOUR)),
(3, 'drought',  55, 'moderado', '1.2', 73, DATE_ADD(NOW(), INTERVAL 48 HOUR)),
(3, 'combined', 48, 'moderado', '1.2', 70, DATE_ADD(NOW(), INTERVAL 48 HOUR)),
(8, 'flood',    79, 'alto',     '1.2', 86, DATE_ADD(NOW(), INTERVAL 12 HOUR)),
(8, 'combined', 76, 'alto',     '1.2', 83, DATE_ADD(NOW(), INTERVAL 12 HOUR)),
(9, 'fire',     71, 'alto',     '1.2', 80, DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(9, 'combined', 66, 'alto',     '1.2', 77, DATE_ADD(NOW(), INTERVAL 24 HOUR));

-- ============================================================
-- RELATÓRIOS CLIMÁTICOS PÚBLICOS
-- ============================================================
INSERT INTO reports (region_id, type, title, summary, generated_by, is_public, views_count, created_at) VALUES

(5, 'flood',
 'Análise de Enchentes — Ribeirão Bauru (Set/2026)',
 'Episódio de cheia registrado no Ribeirão Bauru após chuva acumulada de 94mm em 6h. Níveis atingiram 213cm, ultrapassando cota de alerta. Área afetada estimada: 1,2 km². Famílias em alerta preventivo: 320.',
 'auto', 1, 148, DATE_SUB(NOW(), INTERVAL 2 DAY)),

(5, 'drought',
 'Relatório de Seca Agrícola — Zona Rural Bauru (Ago/2026)',
 'Levantamento indica déficit hídrico de 38% nas lavouras da zona rural de Bauru. Umidade do solo em 24% (abaixo do limiar crítico de 25%). Impacto estimado em 1.800 hectares de cana e laranja.',
 'auto', 1, 97, DATE_SUB(NOW(), INTERVAL 5 DAY)),

(5, 'fire',
 'Monitoramento de Qualidade do Ar — Bauru (Set/2026)',
 'Episódio de fumaça registrado no Terminal Rodoviário com pico de 95 ppm. Origem provável: queimadas no interior paulista. Duração: ~4h. Sem internações registradas.',
 'auto', 1, 63, DATE_SUB(NOW(), INTERVAL 1 DAY)),

(1, 'flood',
 'Boletim Hidrológico — Manaus (Set/2026)',
 'Igarapé do Mindu registrou nível máximo de 267cm em evento de cheia extrema. Bairros em alerta de evacuação. Sistema SATI-Alerta emitiu notificações para 4.200 usuários cadastrados.',
 'auto', 1, 312, DATE_SUB(NOW(), INTERVAL 15 DAY)),

(2, 'flood',
 'Relatório Técnico — Enchente Igarapé Zone (Ago/2026)',
 'Análise técnica do episódio de enchente na área do Igarapé Zone. Precipitação acumulada de 187mm em 12h. Danos estimados à infraestrutura: R$ 2,3 milhões. Propostas de intervenção mapeadas.',
 'manual', 1, 221, DATE_SUB(NOW(), INTERVAL 20 DAY)),

(7, 'drought',
 'Alerta de Seca — Zona Rural Bauru Vargem Limpa (Set/2026)',
 'Solo com umidade volumétrica em queda contínua há 10 dias. Previsão indica ausência de chuvas por mais 15 dias. Agricultores orientados a acionar sistemas de irrigação de emergência.',
 'auto', 1, 54, DATE_SUB(NOW(), INTERVAL 3 DAY)),

(3, 'general',
 'Relatório Geral — Parintins (Jul/2026)',
 'Situação climática estável em Parintins. Nível do Rio Amazonas dentro da normalidade sazonal. Umidade do solo adequada para o período. Próxima avaliação: outubro/2026.',
 'auto', 1, 41, DATE_SUB(NOW(), INTERVAL 45 DAY)),

(9, 'infrastructure',
 'Diagnóstico Sensores — Manaus Zona Sul (Set/2026)',
 'Avaliação técnica dos sensores da Zona Sul de Manaus. Dois sensores com bateria abaixo de 30% identificados para troca preventiva. Taxa de disponibilidade da rede: 94,7% no período.',
 'manual', 0, 12, DATE_SUB(NOW(), INTERVAL 7 DAY));

-- ============================================================
-- LOG DE NOTIFICAÇÕES (histórico)
-- ============================================================
INSERT INTO notification_log (alert_id, user_id, channel, destination, status, sent_at) VALUES
(1, NULL, 'push', 'broadcast:region:5',  'sent', DATE_SUB(NOW(), INTERVAL 37 HOUR)),
(1, NULL, 'sms',  '+5514991110001',      'sent', DATE_SUB(NOW(), INTERVAL 37 HOUR)),
(2, NULL, 'push', 'broadcast:region:5',  'sent', DATE_SUB(NOW(), INTERVAL 32 HOUR)),
(2, NULL, 'sms',  '+5514991110001',      'sent', DATE_SUB(NOW(), INTERVAL 32 HOUR)),
(3, NULL, 'push', 'broadcast:region:5',  'sent', DATE_SUB(NOW(), INTERVAL  8 HOUR)),
(3, NULL, 'sms',  '+5514991110002',      'sent', DATE_SUB(NOW(), INTERVAL  8 HOUR)),
(5, NULL, 'push', 'broadcast:region:8',  'sent', DATE_SUB(NOW(), INTERVAL  2 HOUR)),
(5, NULL, 'sms',  '+5592999880001',      'sent', DATE_SUB(NOW(), INTERVAL  2 HOUR)),
(6, NULL, 'push', 'broadcast:region:9',  'sent', DATE_SUB(NOW(), INTERVAL  2 HOUR)),
(6, NULL, 'sms',  '+5592999880002',      'sent', DATE_SUB(NOW(), INTERVAL  2 HOUR));

-- ============================================================
-- USUÁRIOS EXTRAS (demo)
-- ============================================================
INSERT INTO users (name, email, phone, password_hash, role_id, region_id, is_active) VALUES
('Carlos Prefeitura Bauru',   'carlos@bauru.sp.gov.br',    '+5514991110001', '$2y$12$sati.admin.hash.placeholder..A', 1, 5, 1),
('Ana Produtora Rural Bauru', 'ana@cooperativa.sp.br',      '+5514991110002', '$2y$12$sati.producer.hash.placeholder', 2, 7, 1),
('Roberto Comunidade Bauru',  'roberto@escola.bauru.sp',    '+5514991110003', '$2y$12$sati.public.hash.placeholder..', 3, 5, 1),
('Fernanda SEMA Manaus',      'fernanda@sema.am.gov.br',   '+5592999880001', '$2y$12$sati.admin.hash.placeholder..A', 1, 1, 1),
('Pedro Agricultura AM',      'pedro@seapa.am.gov.br',     '+5592999880002', '$2y$12$sati.producer.hash.placeholder', 2, 3, 1);

-- ============================================================
-- API KEYS — Gateways Bauru
-- ============================================================
INSERT INTO api_keys (name, key_hash, gateway_id, region_id, is_active) VALUES
('Gateway ESP32 - Bauru Centro',      SHA2('sati_gateway_bauru_centro_key', 256),  'GW-BAU-001', 5, 1),
('Gateway ESP32 - Bauru Vargem',      SHA2('sati_gateway_bauru_vargem_key', 256),  'GW-BAU-002', 7, 1),
('Gateway ESP32 - Manaus Zona Norte', SHA2('sati_gateway_manaus_norte_key', 256),  'GW-MAN-002', 8, 1);

SET FOREIGN_KEY_CHECKS = 1;
