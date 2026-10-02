-- ============================================================
-- SATI-Alerta — Banco de Dados Completo (Estrutura + Dados)
-- Pronto para Importação em Hospedagem (cPanel / phpMyAdmin / VPS)
-- Data de geração: 2026-09-28
-- Compatível com: MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+
-- Codificação: UTF-8 (utf8mb4_unicode_ci)
-- ============================================================
-- COMO IMPORTAR NO BANCO DE DADOS:
-- 1. Crie o banco de dados (ex: sati_alerta).
-- 2. Crie o usuário MySQL correspondente e conceda os privilégios necessários.
-- 3. Abra o gerenciador (ex: phpMyAdmin) e selecione o banco na barra lateral.
-- 4. Clique na aba "Importar", selecione este arquivo e clique em "Executar".
--
-- OU VIA TERMINAL (SSH / Linux / XAMPP):
-- mysql -u root -p sati_alerta < database/sati_alerta_completo.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `alert_levels`
--

DROP TABLE IF EXISTS `alert_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alert_levels` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `label` varchar(50) NOT NULL,
  `color_hex` char(7) NOT NULL,
  `priority` tinyint(3) unsigned NOT NULL COMMENT '1=mais alto',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alert_levels`
--

/*!40000 ALTER TABLE `alert_levels` DISABLE KEYS */;
INSERT INTO `alert_levels` (`id`, `code`, `label`, `color_hex`, `priority`) VALUES (1,'INFO','Informativo','#17a2b8',4),(2,'WARNING','Atenção','#ffc107',3),(3,'CRITICAL','Crítico','#fd7e14',2),(4,'EMERGENCY','Emergência','#dc3545',1);
/*!40000 ALTER TABLE `alert_levels` ENABLE KEYS */;

--
-- Table structure for table `alert_rules`
--

DROP TABLE IF EXISTS `alert_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alert_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `sensor_type_id` smallint(5) unsigned NOT NULL,
  `alert_level_id` tinyint(3) unsigned NOT NULL,
  `condition_op` enum('gt','lt','gte','lte','eq') NOT NULL,
  `threshold_value` decimal(14,4) NOT NULL,
  `duration_min` smallint(6) DEFAULT 0 COMMENT 'Minutos acima do limiar para disparar',
  `message_template` text DEFAULT NULL COMMENT 'Template da mensagem: use {value}, {sensor}, {region}',
  `notify_sms` tinyint(1) DEFAULT 1,
  `notify_push` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sensor_type_id` (`sensor_type_id`),
  KEY `alert_level_id` (`alert_level_id`),
  CONSTRAINT `alert_rules_ibfk_1` FOREIGN KEY (`sensor_type_id`) REFERENCES `sensor_types` (`id`),
  CONSTRAINT `alert_rules_ibfk_2` FOREIGN KEY (`alert_level_id`) REFERENCES `alert_levels` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alert_rules`
--

/*!40000 ALTER TABLE `alert_rules` DISABLE KEYS */;
INSERT INTO `alert_rules` (`id`, `name`, `sensor_type_id`, `alert_level_id`, `condition_op`, `threshold_value`, `duration_min`, `message_template`, `notify_sms`, `notify_push`, `is_active`, `created_at`) VALUES (1,'Nível Água Atenção',2,2,'gte',150.0000,10,'ATENÇÃO: Nível de água em {sensor} chegou a {value}cm na região {region}. Monitore a situação.',1,1,1,'2026-09-27 21:26:30'),(2,'Nível Água Crítico',2,3,'gte',200.0000,5,'CRÍTICO: Risco de inundação em {region}! Nível de água = {value}cm no sensor {sensor}.',1,1,1,'2026-09-27 21:26:30'),(3,'Nível Água Emergência',2,4,'gte',250.0000,2,'🚨 EMERGÊNCIA: INUNDAÇÃO IMINENTE em {region}! Nível = {value}cm. Evacue áreas de risco!',1,1,1,'2026-09-27 21:26:30'),(4,'Fumaça Detectada',1,3,'gte',80.0000,5,'ALERTA: Fumaça detectada em {region} ({value} ppm). Possível foco de incêndio. Verifique imediatamente.',1,1,1,'2026-09-27 21:26:30'),(5,'Qualidade Ar Emergência',1,4,'gte',150.0000,2,'🚨 EMERGÊNCIA: Qualidade do ar PERIGOSA em {region} ({value} ppm). Mantenha-se em local fechado!',1,1,1,'2026-09-27 21:26:30'),(6,'Solo Seco Atenção',4,2,'lte',25.0000,60,'ATENÇÃO: Solo muito seco em {region} ({value}%). Risco de seca. Planeje irrigação.',0,1,1,'2026-09-27 21:26:30'),(7,'Solo Crítico',4,3,'lte',15.0000,30,'CRÍTICO: Umidade do solo extremamente baixa em {region} ({value}%). Alto risco de perda de lavoura.',1,1,1,'2026-09-27 21:26:30');
/*!40000 ALTER TABLE `alert_rules` ENABLE KEYS */;

--
-- Table structure for table `alerts`
--

DROP TABLE IF EXISTS `alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rule_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = disparo manual',
  `sensor_id` int(10) unsigned DEFAULT NULL,
  `region_id` int(10) unsigned NOT NULL,
  `alert_level_id` tinyint(3) unsigned NOT NULL,
  `trigger_value` decimal(14,4) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `triggered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `notified_sms` tinyint(1) DEFAULT 0,
  `notified_push` tinyint(1) DEFAULT 0,
  `created_by` int(10) unsigned DEFAULT NULL COMMENT 'NULL = autom??tico, ID = usu??rio manual',
  PRIMARY KEY (`id`),
  KEY `idx_region_active` (`region_id`,`is_active`),
  KEY `idx_triggered_at` (`triggered_at`),
  KEY `rule_id` (`rule_id`),
  KEY `sensor_id` (`sensor_id`),
  KEY `alert_level_id` (`alert_level_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `alert_rules` (`id`),
  CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`id`),
  CONSTRAINT `alerts_ibfk_3` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`),
  CONSTRAINT `alerts_ibfk_4` FOREIGN KEY (`alert_level_id`) REFERENCES `alert_levels` (`id`),
  CONSTRAINT `alerts_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alerts`
--

/*!40000 ALTER TABLE `alerts` DISABLE KEYS */;
INSERT INTO `alerts` (`id`, `rule_id`, `sensor_id`, `region_id`, `alert_level_id`, `trigger_value`, `title`, `message`, `triggered_at`, `resolved_at`, `is_active`, `notified_sms`, `notified_push`, `created_by`) VALUES (1,1,5,5,2,152.4000,'Atenção: Nível d\'água elevado — Rio Bauru Centro','ATENÇÃO: Nível de água em Sensor Nível - Rio Bauru Centro chegou a 152cm na região Bauru - Centro. Monitore a situação.','2026-09-26 08:26:30','2026-09-26 19:26:30',0,1,1,NULL),(2,2,5,5,3,213.8000,'Crítico: Risco de inundação — Rio Bauru','CRÍTICO: Risco de inundação em Bauru - Centro! Nível de água = 213cm no sensor Sensor Nível - Rio Bauru Centro.','2026-09-26 13:26:30','2026-09-26 21:26:30',0,1,1,NULL),(3,4,6,5,3,88.3000,'Alerta: Fumaça detectada — Terminal Rodoviário Bauru','ALERTA: Fumaça detectada em Bauru - Centro (88 ppm). Possível foco de incêndio. Verifique imediatamente.','2026-09-27 13:26:30',NULL,1,1,1,NULL),(4,6,7,7,2,24.1000,'Atenção: Solo muito seco — Fazenda Vargem Limpa','ATENÇÃO: Solo muito seco em Bauru - Vargem Limpa (24%). Risco de seca. Planeje irrigação.','2026-09-27 20:26:30',NULL,1,0,1,NULL),(5,1,9,8,2,172.6000,'Atenção: Igarapé do Tarumã com nível elevado','ATENÇÃO: Nível de água em Sensor Nível - Igarapé Tarumã chegou a 172cm na região Manaus - Zona Norte.','2026-09-27 19:26:30',NULL,1,1,1,NULL),(6,4,10,9,3,83.9000,'Alerta: Qualidade do ar comprometida — Zona Sul Manaus','ALERTA: Fumaça detectada em Manaus - Zona Sul (83 ppm). Possível foco de incêndio na área industrial.','2026-09-27 19:26:30',NULL,1,1,1,NULL),(7,3,1,2,4,267.3000,'EMERGÊNCIA: Inundação iminente — Igarapé do Mindu','EMERGÊNCIA: INUNDAÇÃO IMINENTE em Manaus - Igarapé Zone! Nível = 267cm. Evacuação de risco concluída.','2026-09-12 21:26:30','2026-09-13 21:26:30',0,1,1,NULL),(8,5,2,1,4,162.0000,'EMERGÊNCIA: Qualidade do ar perigosa — Porto de Manaus','EMERGÊNCIA: Qualidade do ar PERIGOSA em Manaus - Centro (162 ppm). Manter-se em local fechado.','2026-08-28 21:26:30','2026-08-29 21:26:30',0,1,1,NULL),(9,NULL,NULL,5,1,NULL,'Informativo: Previsão de chuvas intensas para Bauru — SP','Previsão meteorológica indica precipitação de até 80mm nas próximas 24h para Bauru e região. Evite áreas de risco.','2026-09-27 18:26:30',NULL,1,0,1,NULL),(10,NULL,NULL,1,1,NULL,'Informativo: Período de estiagem previsto para AM','CEMADEN prevê período de estiagem prolongada para a região do Amazonas. Produtores rurais devem reforçar reservatórios.','2026-09-25 21:26:30',NULL,1,0,1,NULL);
/*!40000 ALTER TABLE `alerts` ENABLE KEYS */;

--
-- Table structure for table `api_keys`
--

DROP TABLE IF EXISTS `api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `key_hash` varchar(255) NOT NULL COMMENT 'SHA-256 da API key',
  `gateway_id` varchar(50) DEFAULT NULL COMMENT 'Identificador do gateway ESP32',
  `region_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_hash` (`key_hash`),
  KEY `region_id` (`region_id`),
  CONSTRAINT `api_keys_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_keys`
--

/*!40000 ALTER TABLE `api_keys` DISABLE KEYS */;
INSERT INTO `api_keys` (`id`, `name`, `key_hash`, `gateway_id`, `region_id`, `is_active`, `last_used_at`, `created_at`) VALUES (1,'Gateway ESP32 - Manaus Centro','22ddfd4690969188fcf4c592544a343037cd4f2550139573cbbc6aaef0668384','GW-MAN-001',1,1,NULL,'2026-09-27 21:26:30'),(2,'Gateway ESP32 - Bauru Centro','33e2553331dbd6da83703dad9e2c5aa5f1e4f4a587c24227a7d7efb0aa57643e','GW-BAU-001',5,1,NULL,'2026-09-27 21:26:30'),(3,'Gateway ESP32 - Bauru Vargem','bc7c2df02fe5231b29feb83cea8285568393ce747d854db45feb3c7816467784','GW-BAU-002',7,1,NULL,'2026-09-27 21:26:30'),(4,'Gateway ESP32 - Manaus Zona Norte','4ddd66512cca5dca1008c0c4b715332d6eae1fef21ac943bdc8fb4e1c46613a4','GW-MAN-002',8,1,NULL,'2026-09-27 21:26:30');
/*!40000 ALTER TABLE `api_keys` ENABLE KEYS */;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(64) DEFAULT NULL,
  `record_id` bigint(20) unsigned DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_action` (`user_id`,`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;

--
-- Table structure for table `notification_log`
--

DROP TABLE IF EXISTS `notification_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alert_id` bigint(20) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = broadcast de regi??o',
  `channel` enum('sms','push','email') NOT NULL,
  `destination` varchar(255) NOT NULL COMMENT 'N??mero, FCM token ou e-mail',
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `provider_id` varchar(100) DEFAULT NULL COMMENT 'SID Twilio ou ID OneSignal',
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_msg` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alert_id` (`alert_id`),
  CONSTRAINT `notification_log_ibfk_1` FOREIGN KEY (`alert_id`) REFERENCES `alerts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_log`
--

/*!40000 ALTER TABLE `notification_log` DISABLE KEYS */;
INSERT INTO `notification_log` (`id`, `alert_id`, `user_id`, `channel`, `destination`, `status`, `provider_id`, `sent_at`, `error_msg`) VALUES (1,1,NULL,'push','broadcast:region:5','sent',NULL,'2026-09-26 08:26:30',NULL),(2,1,NULL,'sms','+5514991110001','sent',NULL,'2026-09-26 08:26:30',NULL),(3,2,NULL,'push','broadcast:region:5','sent',NULL,'2026-09-26 13:26:30',NULL),(4,2,NULL,'sms','+5514991110001','sent',NULL,'2026-09-26 13:26:30',NULL),(5,3,NULL,'push','broadcast:region:5','sent',NULL,'2026-09-27 13:26:30',NULL),(6,3,NULL,'sms','+5514991110002','sent',NULL,'2026-09-27 13:26:30',NULL),(7,5,NULL,'push','broadcast:region:8','sent',NULL,'2026-09-27 19:26:30',NULL),(8,5,NULL,'sms','+5592999880001','sent',NULL,'2026-09-27 19:26:30',NULL),(9,6,NULL,'push','broadcast:region:9','sent',NULL,'2026-09-27 19:26:30',NULL),(10,6,NULL,'sms','+5592999880002','sent',NULL,'2026-09-27 19:26:30',NULL);
/*!40000 ALTER TABLE `notification_log` ENABLE KEYS */;

--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `regions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `state` char(2) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `geojson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Pol??gono GeoJSON da regi??o para o mapa' CHECK (json_valid(`geojson`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regions`
--

/*!40000 ALTER TABLE `regions` DISABLE KEYS */;
INSERT INTO `regions` (`id`, `name`, `state`, `city`, `latitude`, `longitude`, `geojson`, `created_at`) VALUES (1,'Manaus - Centro','AM','Manaus',-3.1190000,-60.0217000,NULL,'2026-09-27 21:26:30'),(2,'Manaus - Igarapé Zone','AM','Manaus',-3.0876000,-60.0553000,NULL,'2026-09-27 21:26:30'),(3,'Parintins','AM','Parintins',-2.6286000,-56.7358000,NULL,'2026-09-27 21:26:30'),(4,'Itacoatiara','AM','Itacoatiara',-3.1428000,-58.4441000,NULL,'2026-09-27 21:26:30'),(5,'Bauru - Centro','SP','Bauru',-22.3147000,-49.0625000,NULL,'2026-09-27 21:26:30'),(6,'Bauru - Vila Falcão','SP','Bauru',-22.3362000,-49.0778000,NULL,'2026-09-27 21:26:30'),(7,'Bauru - Vargem Limpa','SP','Bauru',-22.2897000,-49.0414000,NULL,'2026-09-27 21:26:30'),(8,'Manaus - Zona Norte','AM','Manaus',-3.0347000,-60.0278000,NULL,'2026-09-27 21:26:30'),(9,'Manaus - Zona Sul','AM','Manaus',-3.1574000,-60.0189000,NULL,'2026-09-27 21:26:30'),(10,'Itacoatiara - Centro','AM','Itacoatiara',-3.1448000,-58.4421000,NULL,'2026-09-27 21:26:30');
/*!40000 ALTER TABLE `regions` ENABLE KEYS */;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `region_id` int(10) unsigned NOT NULL,
  `type` enum('flood','drought','fire','infrastructure','agriculture','general') NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL COMMENT 'Caminho para PDF gerado',
  `generated_by` enum('auto','manual') DEFAULT 'auto',
  `author_id` int(10) unsigned DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0 COMMENT 'Vis??vel na plataforma p??blica',
  `views_count` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_region_type` (`region_id`,`type`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`),
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
INSERT INTO `reports` (`id`, `region_id`, `type`, `title`, `summary`, `content`, `file_path`, `generated_by`, `author_id`, `is_public`, `views_count`, `created_at`) VALUES (1,5,'flood','Análise de Enchentes — Ribeirão Bauru (Set/2026)','Episódio de cheia registrado no Ribeirão Bauru após chuva acumulada de 94mm em 6h. Níveis atingiram 213cm, ultrapassando cota de alerta. Área afetada estimada: 1,2 km². Famílias em alerta preventivo: 320.',NULL,NULL,'auto',NULL,1,148,'2026-09-25 21:26:30'),(2,5,'drought','Relatório de Seca Agrícola — Zona Rural Bauru (Ago/2026)','Levantamento indica déficit hídrico de 38% nas lavouras da zona rural de Bauru. Umidade do solo em 24% (abaixo do limiar crítico de 25%). Impacto estimado em 1.800 hectares de cana e laranja.',NULL,NULL,'auto',NULL,1,97,'2026-09-22 21:26:30'),(3,5,'fire','Monitoramento de Qualidade do Ar — Bauru (Set/2026)','Episódio de fumaça registrado no Terminal Rodoviário com pico de 95 ppm. Origem provável: queimadas no interior paulista. Duração: ~4h. Sem internações registradas.',NULL,NULL,'auto',NULL,1,63,'2026-09-26 21:26:30'),(4,1,'flood','Boletim Hidrológico — Manaus (Set/2026)','Igarapé do Mindu registrou nível máximo de 267cm em evento de cheia extrema. Bairros em alerta de evacuação. Sistema SATI-Alerta emitiu notificações para 4.200 usuários cadastrados.',NULL,NULL,'auto',NULL,1,312,'2026-09-12 21:26:30'),(5,2,'flood','Relatório Técnico — Enchente Igarapé Zone (Ago/2026)','Análise técnica do episódio de enchente na área do Igarapé Zone. Precipitação acumulada de 187mm em 12h. Danos estimados à infraestrutura: R$ 2,3 milhões. Propostas de intervenção mapeadas.',NULL,NULL,'manual',NULL,1,221,'2026-09-07 21:26:30'),(6,7,'drought','Alerta de Seca — Zona Rural Bauru Vargem Limpa (Set/2026)','Solo com umidade volumétrica em queda contínua há 10 dias. Previsão indica ausência de chuvas por mais 15 dias. Agricultores orientados a acionar sistemas de irrigação de emergência.',NULL,NULL,'auto',NULL,1,54,'2026-09-24 21:26:30'),(7,3,'general','Relatório Geral — Parintins (Jul/2026)','Situação climática estável em Parintins. Nível do Rio Amazonas dentro da normalidade sazonal. Umidade do solo adequada para o período. Próxima avaliação: outubro/2026.',NULL,NULL,'auto',NULL,1,41,'2026-08-13 21:26:30'),(8,9,'infrastructure','Diagnóstico Sensores — Manaus Zona Sul (Set/2026)','Avaliação técnica dos sensores da Zona Sul de Manaus. Dois sensores com bateria abaixo de 30% identificados para troca preventiva. Taxa de disponibilidade da rede: 94,7% no período.',NULL,NULL,'manual',NULL,0,12,'2026-09-20 21:26:30');
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;

--
-- Table structure for table `risk_maps`
--

DROP TABLE IF EXISTS `risk_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `risk_maps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `region_id` int(10) unsigned NOT NULL,
  `risk_type` enum('flood','drought','fire','combined') NOT NULL,
  `risk_level` tinyint(3) unsigned NOT NULL COMMENT '0-100',
  `risk_label` varchar(50) DEFAULT NULL COMMENT 'baixo / moderado / alto / cr??tico',
  `geojson_data` longtext DEFAULT NULL COMMENT 'Pol??gonos de risco calculados em GeoJSON',
  `model_version` varchar(20) DEFAULT '1.0',
  `confidence_pct` tinyint(3) unsigned DEFAULT 70 COMMENT 'Confian??a do modelo %',
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `valid_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_region_type` (`region_id`,`risk_type`),
  CONSTRAINT `risk_maps_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_maps`
--

/*!40000 ALTER TABLE `risk_maps` DISABLE KEYS */;
INSERT INTO `risk_maps` (`id`, `region_id`, `risk_type`, `risk_level`, `risk_label`, `geojson_data`, `model_version`, `confidence_pct`, `generated_at`, `valid_until`) VALUES (1,5,'flood',72,'alto',NULL,'1.2',84,'2026-09-27 21:26:30','2026-09-28 09:26:30'),(2,5,'drought',28,'baixo',NULL,'1.2',79,'2026-09-27 21:26:30','2026-09-28 21:26:30'),(3,5,'fire',65,'alto',NULL,'1.2',76,'2026-09-27 21:26:30','2026-09-28 09:26:30'),(4,5,'combined',68,'alto',NULL,'1.2',81,'2026-09-27 21:26:30','2026-09-28 09:26:30'),(5,6,'flood',45,'moderado',NULL,'1.2',77,'2026-09-27 21:26:30','2026-09-28 21:26:30'),(6,6,'combined',45,'moderado',NULL,'1.2',74,'2026-09-27 21:26:30','2026-09-28 21:26:30'),(7,7,'drought',81,'crítico',NULL,'1.2',88,'2026-09-27 21:26:30','2026-09-28 21:26:30'),(8,7,'fire',74,'alto',NULL,'1.2',82,'2026-09-27 21:26:30','2026-09-28 21:26:30'),(9,7,'combined',78,'alto',NULL,'1.2',85,'2026-09-27 21:26:30','2026-09-28 21:26:30'),(10,1,'flood',88,'crítico',NULL,'1.2',91,'2026-09-27 21:26:30','2026-09-28 03:26:30'),(11,1,'combined',85,'crítico',NULL,'1.2',89,'2026-09-27 21:26:30','2026-09-28 03:26:30'),(12,2,'flood',93,'crítico',NULL,'1.2',93,'2026-09-27 21:26:30','2026-09-28 03:26:30'),(13,2,'combined',90,'crítico',NULL,'1.2',91,'2026-09-27 21:26:30','2026-09-28 03:26:30'),(14,3,'drought',55,'moderado',NULL,'1.2',73,'2026-09-27 21:26:30','2026-09-29 21:26:30'),(15,3,'combined',48,'moderado',NULL,'1.2',70,'2026-09-27 21:26:30','2026-09-29 21:26:30'),(16,8,'flood',79,'alto',NULL,'1.2',86,'2026-09-27 21:26:30','2026-09-28 09:26:30'),(17,8,'combined',76,'alto',NULL,'1.2',83,'2026-09-27 21:26:30','2026-09-28 09:26:30'),(18,9,'fire',71,'alto',NULL,'1.2',80,'2026-09-27 21:26:30','2026-09-28 21:26:30'),(19,9,'combined',66,'alto',NULL,'1.2',77,'2026-09-27 21:26:30','2026-09-28 21:26:30');
/*!40000 ALTER TABLE `risk_maps` ENABLE KEYS */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `name`, `label`, `description`, `created_at`) VALUES (1,'admin_gov','Administrador Governamental','Secretarias de Proteção Civil, Infraestrutura e Agricultura','2026-09-27 21:26:30'),(2,'rural_producer','Produtor Rural','Pequenos agricultores e cooperativas','2026-09-27 21:26:30'),(3,'public','Usuário Público','Comunidade, escolas, cidadãos em geral','2026-09-27 21:26:30');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;

--
-- Table structure for table `sensor_readings`
--

DROP TABLE IF EXISTS `sensor_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sensor_readings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sensor_id` int(10) unsigned NOT NULL,
  `value` decimal(14,4) NOT NULL,
  `raw_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Payload bruto recebido do ESP32 para auditoria' CHECK (json_valid(`raw_payload`)),
  `rssi` smallint(6) DEFAULT NULL COMMENT 'RSSI do sinal LoRa em dBm',
  `snr` decimal(5,2) DEFAULT NULL COMMENT 'SNR do sinal LoRa',
  `battery_pct` tinyint(3) unsigned DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Timestamp do sensor (UTC)',
  `received_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Quando a API recebeu',
  PRIMARY KEY (`id`),
  KEY `idx_sensor_time` (`sensor_id`,`recorded_at`),
  KEY `idx_recorded_at` (`recorded_at`),
  CONSTRAINT `sensor_readings_ibfk_1` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor_readings`
--

/*!40000 ALTER TABLE `sensor_readings` DISABLE KEYS */;
INSERT INTO `sensor_readings` (`id`, `sensor_id`, `value`, `raw_payload`, `rssi`, `snr`, `battery_pct`, `recorded_at`, `received_at`) VALUES (1,5,42.0000,NULL,-87,7.20,87,'2026-09-25 22:26:30','2026-09-27 21:26:30'),(2,5,45.5000,NULL,-85,7.50,87,'2026-09-25 23:26:30','2026-09-27 21:26:30'),(3,5,51.2000,NULL,-84,7.80,87,'2026-09-26 00:26:30','2026-09-27 21:26:30'),(4,5,58.7000,NULL,-83,8.00,87,'2026-09-26 01:26:30','2026-09-27 21:26:30'),(5,5,67.3000,NULL,-82,8.20,87,'2026-09-26 02:26:30','2026-09-27 21:26:30'),(6,5,79.1000,NULL,-81,8.50,87,'2026-09-26 03:26:30','2026-09-27 21:26:30'),(7,5,91.4000,NULL,-80,8.70,87,'2026-09-26 04:26:30','2026-09-27 21:26:30'),(8,5,108.6000,NULL,-79,9.00,87,'2026-09-26 05:26:30','2026-09-27 21:26:30'),(9,5,125.2000,NULL,-79,9.10,87,'2026-09-26 06:26:30','2026-09-27 21:26:30'),(10,5,139.8000,NULL,-78,9.30,87,'2026-09-26 07:26:30','2026-09-27 21:26:30'),(11,5,152.4000,NULL,-77,9.50,87,'2026-09-26 08:26:30','2026-09-27 21:26:30'),(12,5,163.7000,NULL,-77,9.40,87,'2026-09-26 09:26:30','2026-09-27 21:26:30'),(13,5,178.9000,NULL,-76,9.20,87,'2026-09-26 10:26:30','2026-09-27 21:26:30'),(14,5,190.1000,NULL,-76,9.00,87,'2026-09-26 11:26:30','2026-09-27 21:26:30'),(15,5,201.5000,NULL,-75,8.80,87,'2026-09-26 12:26:30','2026-09-27 21:26:30'),(16,5,213.8000,NULL,-75,8.60,87,'2026-09-26 13:26:30','2026-09-27 21:26:30'),(17,5,224.3000,NULL,-74,8.40,87,'2026-09-26 14:26:30','2026-09-27 21:26:30'),(18,5,218.6000,NULL,-74,8.50,87,'2026-09-26 15:26:30','2026-09-27 21:26:30'),(19,5,207.2000,NULL,-75,8.70,87,'2026-09-26 17:26:30','2026-09-27 21:26:30'),(20,5,192.4000,NULL,-76,9.00,87,'2026-09-26 19:26:30','2026-09-27 21:26:30'),(21,5,174.1000,NULL,-77,9.20,87,'2026-09-26 21:26:30','2026-09-27 21:26:30'),(22,5,158.9000,NULL,-78,9.40,87,'2026-09-26 23:26:30','2026-09-27 21:26:30'),(23,5,143.7000,NULL,-79,9.50,87,'2026-09-27 01:26:30','2026-09-27 21:26:30'),(24,5,128.3000,NULL,-80,9.30,87,'2026-09-27 03:26:30','2026-09-27 21:26:30'),(25,5,112.6000,NULL,-81,9.10,87,'2026-09-27 05:26:30','2026-09-27 21:26:30'),(26,5,98.4000,NULL,-82,8.90,87,'2026-09-27 07:26:30','2026-09-27 21:26:30'),(27,5,85.2000,NULL,-83,8.70,87,'2026-09-27 09:26:30','2026-09-27 21:26:30'),(28,5,74.8000,NULL,-84,8.50,87,'2026-09-27 11:26:30','2026-09-27 21:26:30'),(29,5,65.3000,NULL,-85,8.30,87,'2026-09-27 13:26:30','2026-09-27 21:26:30'),(30,5,57.9000,NULL,-86,8.10,87,'2026-09-27 15:26:30','2026-09-27 21:26:30'),(31,5,52.4000,NULL,-87,7.90,87,'2026-09-27 17:26:30','2026-09-27 21:26:30'),(32,5,48.7000,NULL,-87,7.70,87,'2026-09-27 19:26:30','2026-09-27 21:26:30'),(33,5,45.1000,NULL,-88,7.50,87,'2026-09-27 20:26:30','2026-09-27 21:26:30'),(34,5,43.2000,NULL,-88,7.40,87,'2026-09-27 20:56:30','2026-09-27 21:26:30'),(35,6,12.3000,NULL,-90,6.50,92,'2026-09-26 21:26:30','2026-09-27 21:26:30'),(36,6,14.7000,NULL,-89,6.70,92,'2026-09-26 23:26:30','2026-09-27 21:26:30'),(37,6,18.2000,NULL,-89,6.90,92,'2026-09-27 01:26:30','2026-09-27 21:26:30'),(38,6,22.5000,NULL,-88,7.10,92,'2026-09-27 03:26:30','2026-09-27 21:26:30'),(39,6,31.8000,NULL,-87,7.30,92,'2026-09-27 05:26:30','2026-09-27 21:26:30'),(40,6,45.2000,NULL,-86,7.50,92,'2026-09-27 07:26:30','2026-09-27 21:26:30'),(41,6,62.4000,NULL,-85,7.80,92,'2026-09-27 09:26:30','2026-09-27 21:26:30'),(42,6,71.9000,NULL,-84,8.00,92,'2026-09-27 11:26:30','2026-09-27 21:26:30'),(43,6,88.3000,NULL,-83,8.20,92,'2026-09-27 13:26:30','2026-09-27 21:26:30'),(44,6,95.7000,NULL,-82,8.40,92,'2026-09-27 15:26:30','2026-09-27 21:26:30'),(45,6,78.4000,NULL,-83,8.10,92,'2026-09-27 17:26:30','2026-09-27 21:26:30'),(46,6,54.2000,NULL,-85,7.70,92,'2026-09-27 19:26:30','2026-09-27 21:26:30'),(47,6,38.6000,NULL,-87,7.30,92,'2026-09-27 20:26:30','2026-09-27 21:26:30'),(48,6,28.1000,NULL,-89,7.00,92,'2026-09-27 20:56:30','2026-09-27 21:26:30'),(49,7,58.2000,NULL,-95,5.10,76,'2026-09-25 21:26:30','2026-09-27 21:26:30'),(50,7,55.7000,NULL,-95,5.20,76,'2026-09-26 09:26:30','2026-09-27 21:26:30'),(51,7,51.3000,NULL,-94,5.40,76,'2026-09-26 21:26:30','2026-09-27 21:26:30'),(52,7,46.8000,NULL,-94,5.50,76,'2026-09-27 03:26:30','2026-09-27 21:26:30'),(53,7,40.2000,NULL,-93,5.70,76,'2026-09-27 09:26:30','2026-09-27 21:26:30'),(54,7,33.5000,NULL,-93,5.80,76,'2026-09-27 15:26:30','2026-09-27 21:26:30'),(55,7,28.9000,NULL,-92,6.00,76,'2026-09-27 19:26:30','2026-09-27 21:26:30'),(56,7,24.1000,NULL,-92,6.10,76,'2026-09-27 20:26:30','2026-09-27 21:26:30'),(57,8,78.4000,NULL,-91,6.20,63,'2026-09-27 09:26:30','2026-09-27 21:26:30'),(58,8,74.1000,NULL,-91,6.30,63,'2026-09-27 13:26:30','2026-09-27 21:26:30'),(59,8,68.7000,NULL,-90,6.50,63,'2026-09-27 17:26:30','2026-09-27 21:26:30'),(60,8,62.3000,NULL,-90,6.60,63,'2026-09-27 19:26:30','2026-09-27 21:26:30'),(61,8,57.8000,NULL,-91,6.40,63,'2026-09-27 20:26:30','2026-09-27 21:26:30'),(62,9,89.4000,NULL,-88,7.00,54,'2026-09-26 21:26:30','2026-09-27 21:26:30'),(63,9,110.2000,NULL,-87,7.30,54,'2026-09-27 03:26:30','2026-09-27 21:26:30'),(64,9,134.7000,NULL,-86,7.60,54,'2026-09-27 09:26:30','2026-09-27 21:26:30'),(65,9,158.3000,NULL,-85,7.90,54,'2026-09-27 15:26:30','2026-09-27 21:26:30'),(66,9,172.6000,NULL,-84,8.10,54,'2026-09-27 19:26:30','2026-09-27 21:26:30'),(67,9,181.4000,NULL,-84,8.20,54,'2026-09-27 20:26:30','2026-09-27 21:26:30'),(68,10,35.4000,NULL,-92,6.10,71,'2026-09-27 09:26:30','2026-09-27 21:26:30'),(69,10,48.7000,NULL,-91,6.40,71,'2026-09-27 13:26:30','2026-09-27 21:26:30'),(70,10,72.3000,NULL,-90,6.70,71,'2026-09-27 17:26:30','2026-09-27 21:26:30'),(71,10,83.9000,NULL,-89,6.90,71,'2026-09-27 19:26:30','2026-09-27 21:26:30'),(72,10,67.4000,NULL,-90,6.60,71,'2026-09-27 20:26:30','2026-09-27 21:26:30');
/*!40000 ALTER TABLE `sensor_readings` ENABLE KEYS */;

--
-- Table structure for table `sensor_types`
--

DROP TABLE IF EXISTS `sensor_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sensor_types` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit` varchar(20) DEFAULT NULL COMMENT 'Unidade de medida: ppm, cm, %, ??Sv/h etc.',
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'bi-cpu',
  `min_safe` decimal(12,4) DEFAULT NULL COMMENT 'Limite m??nimo seguro',
  `max_safe` decimal(12,4) DEFAULT NULL COMMENT 'Limite m??ximo seguro',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor_types`
--

/*!40000 ALTER TABLE `sensor_types` DISABLE KEYS */;
INSERT INTO `sensor_types` (`id`, `code`, `name`, `unit`, `description`, `icon`, `min_safe`, `max_safe`, `created_at`) VALUES (1,'AIR_QUALITY','Qualidade do Ar / Fumaça','ppm','Detecta partículas de fumaça e qualidade do ar','bi-wind',0.0000,50.0000,'2026-09-27 21:26:30'),(2,'WATER_LEVEL','Nível de Água','cm','Nível do leito de rios, canais e bueiros','bi-water',0.0000,200.0000,'2026-09-27 21:26:30'),(3,'AIR_HUMIDITY','Umidade do Ar','%','Percentual de umidade relativa do ar','bi-cloud-drizzle',30.0000,90.0000,'2026-09-27 21:26:30'),(4,'SOIL_MOISTURE','Umidade do Solo','%','Percentual de umidade volumétrica do solo','bi-layers',20.0000,80.0000,'2026-09-27 21:26:30'),(5,'RADIATION','Radiação Solar','W/m²','Irradiação solar global','bi-sun',0.0000,1200.0000,'2026-09-27 21:26:30');
/*!40000 ALTER TABLE `sensor_types` ENABLE KEYS */;

--
-- Table structure for table `sensors`
--

DROP TABLE IF EXISTS `sensors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sensors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `eui` varchar(32) NOT NULL COMMENT 'DevEUI LoRaWAN ??nico do sensor',
  `name` varchar(100) DEFAULT NULL,
  `sensor_type_id` smallint(5) unsigned NOT NULL,
  `region_id` int(10) unsigned NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `altitude_m` decimal(8,2) DEFAULT NULL,
  `location_desc` varchar(255) DEFAULT NULL COMMENT 'Descri????o textual da localiza????o',
  `installed_at` date DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `battery_pct` tinyint(3) unsigned DEFAULT 100,
  `is_active` tinyint(1) DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Configura????es extras por tipo de sensor' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `eui` (`eui`),
  KEY `sensor_type_id` (`sensor_type_id`),
  KEY `region_id` (`region_id`),
  CONSTRAINT `sensors_ibfk_1` FOREIGN KEY (`sensor_type_id`) REFERENCES `sensor_types` (`id`),
  CONSTRAINT `sensors_ibfk_2` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensors`
--

/*!40000 ALTER TABLE `sensors` DISABLE KEYS */;
INSERT INTO `sensors` (`id`, `eui`, `name`, `sensor_type_id`, `region_id`, `latitude`, `longitude`, `altitude_m`, `location_desc`, `installed_at`, `last_seen_at`, `battery_pct`, `is_active`, `metadata`, `created_at`) VALUES (1,'A1B2C3D4E5F60001','Sensor Igarapé do Mindu - Nível',2,2,-3.0921000,-60.0489000,NULL,'Ponte Nível Igarapé do Mindu','2025-01-15',NULL,100,1,NULL,'2026-09-27 21:26:30'),(2,'A1B2C3D4E5F60002','Sensor AQ - Porto de Manaus',1,1,-3.1307000,-60.0231000,NULL,'Porto de Manaus - Qualidade Ar','2025-01-20',NULL,100,1,NULL,'2026-09-27 21:26:30'),(3,'A1B2C3D4E5F60003','Sensor Solo - Km 45 AM-010',4,3,-2.7500000,-56.9200000,NULL,'Lavoura km 45 rodovia AM-010','2025-02-01',NULL,100,1,NULL,'2026-09-27 21:26:30'),(4,'A1B2C3D4E5F60004','Sensor Umidade - Parintins',3,3,-2.6310000,-56.7390000,NULL,'Estação Meteorológica Parintins','2025-02-10',NULL,100,1,NULL,'2026-09-27 21:26:30'),(5,'B1B2C3D4E5F60001','Sensor Nível - Rio Bauru Centro',2,5,-22.3201000,-49.0690000,NULL,'Leito do Ribeirão Bauru - Ponte da Av. Rodrigues Alves','2025-03-10','2026-09-27 21:14:30',87,1,NULL,'2026-09-27 21:26:30'),(6,'B1B2C3D4E5F60002','Sensor AQ - Terminal Rodoviário Bauru',1,5,-22.3147000,-49.0625000,NULL,'Av. Rodrigues Alves 6-50 - Terminal Rodoviário','2025-03-15','2026-09-27 21:18:30',92,1,NULL,'2026-09-27 21:26:30'),(7,'B1B2C3D4E5F60003','Sensor Umidade Solo - Fazenda Vargem',4,7,-22.2897000,-49.0414000,NULL,'Fazenda Modelo - Zona Rural Vargem Limpa','2025-04-01','2026-09-27 21:01:30',76,1,NULL,'2026-09-27 21:26:30'),(8,'B1B2C3D4E5F60004','Sensor Umidade Ar - Bauru Vila Falcão',3,6,-22.3362000,-49.0778000,NULL,'Praça da Vila Falcão - Estação Meteorológica','2025-04-05','2026-09-27 21:21:30',63,1,NULL,'2026-09-27 21:26:30'),(9,'B1B2C3D4E5F60005','Sensor Radiação Solar - Bauru UNESP',5,5,-22.3207000,-49.0636000,NULL,'Campus UNESP Bauru - Telhado Bloco Engenharia','2025-04-10','2026-09-27 21:11:30',99,1,NULL,'2026-09-27 21:26:30'),(10,'A1B2C3D4E5F60005','Sensor Nível - Igarapé Tarumã',2,8,-3.0347000,-60.0278000,NULL,'Igarapé do Tarumã - Ponte da AM-070','2025-02-20','2026-09-27 20:46:30',54,1,NULL,'2026-09-27 21:26:30'),(11,'A1B2C3D4E5F60006','Sensor AQ - Zona Sul Manaus',1,9,-3.1574000,-60.0189000,NULL,'Av. Constantino Nery - Zona Sul Industrial','2025-02-25','2026-09-27 21:08:30',71,1,NULL,'2026-09-27 21:26:30'),(12,'A1B2C3D4E5F60007','Sensor Umidade Solo - Itacoatiara',4,10,-3.1448000,-58.4421000,NULL,'Lavoura Experimental AM-010 km 270','2025-03-01','2026-09-27 21:04:30',88,1,NULL,'2026-09-27 21:26:30');
/*!40000 ALTER TABLE `sensors` ENABLE KEYS */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` tinyint(3) unsigned NOT NULL,
  `region_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role_id`, `region_id`, `is_active`, `last_login_at`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,'Administrador SATI','admin@sati.gov.br','+5592999990001','$2y$12$Wa5quKRp2BVVzZvtwZoCuuOE68fX1hBY6lyBA3MHd2g4NHhk2wks6',1,1,1,'2026-09-27 21:48:52','2026-09-27 21:26:30','2026-09-27 21:48:52',NULL),(2,'João da Silva','joao@cooperativa.am','+5592999990002','$2y$12$Wa5quKRp2BVVzZvtwZoCuuOE68fX1hBY6lyBA3MHd2g4NHhk2wks6',2,1,1,NULL,'2026-09-27 21:26:30','2026-09-27 21:26:31',NULL),(3,'Maria Comunidade','maria@escola.am.gov.br','+5592999990003','$2y$12$Wa5quKRp2BVVzZvtwZoCuuOE68fX1hBY6lyBA3MHd2g4NHhk2wks6',3,1,1,NULL,'2026-09-27 21:26:30','2026-09-27 21:26:31',NULL),(4,'Carlos Prefeitura Bauru','carlos@bauru.sp.gov.br','+5514991110001','$2y$12$Wa5quKRp2BVVzZvtwZoCuuOE68fX1hBY6lyBA3MHd2g4NHhk2wks6',1,5,1,NULL,'2026-09-27 21:26:30','2026-09-27 21:26:31',NULL),(5,'Ana Produtora Rural Bauru','ana@cooperativa.sp.br','+5514991110002','$2y$12$Wa5quKRp2BVVzZvtwZoCuuOE68fX1hBY6lyBA3MHd2g4NHhk2wks6',2,7,1,NULL,'2026-09-27 21:26:30','2026-09-27 21:26:31',NULL),(6,'Roberto Comunidade Bauru','roberto@escola.bauru.sp','+5514991110003','$2y$12$Wa5quKRp2BVVzZvtwZoCuuOE68fX1hBY6lyBA3MHd2g4NHhk2wks6',3,5,1,NULL,'2026-09-27 21:26:30','2026-09-27 21:26:31',NULL),(7,'Fernanda SEMA Manaus','fernanda@sema.am.gov.br','+5592999880001','$2y$12$Wa5quKRp2BVVzZvtwZoCuuOE68fX1hBY6lyBA3MHd2g4NHhk2wks6',1,1,1,NULL,'2026-09-27 21:26:30','2026-09-27 21:26:31',NULL),(8,'Pedro Agricultura AM','pedro@seapa.am.gov.br','+5592999880002','$2y$12$Wa5quKRp2BVVzZvtwZoCuuOE68fX1hBY6lyBA3MHd2g4NHhk2wks6',2,3,1,NULL,'2026-09-27 21:26:30','2026-09-27 21:26:31',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
SET FOREIGN_KEY_CHECKS = 1;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-28 20:10:29
