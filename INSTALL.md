# SATI-Alerta — Guia de Instalação e Configuração

## Pré-requisitos
- XAMPP com PHP 8.1+ e MySQL 8.0+
- `mod_rewrite` ativado no Apache

---

## 1. Banco de Dados

Abra o phpMyAdmin ou execute no MySQL:

```sql
-- 1. Cria o schema
source C:/xampp/htdocs/appSati-alerta/database/schema.sql

-- 2. Insere dados iniciais
source C:/xampp/htdocs/appSati-alerta/database/seeds/initial_data.sql
```

Depois, atualize as senhas dos usuários de teste:

```sql
USE sati_alerta;

-- Senha padrão: Admin@2025
UPDATE users SET password_hash = '$2y$12$EjRqJoW.KkfCVJmvq1V.O.jnQByYGb1IM5g1vHTb8dLVUf68E' WHERE email = 'admin@sati.gov.br';
UPDATE users SET password_hash = '$2y$12$EjRqJoW.KkfCVJmvq1V.O.jnQByYGb1IM5g1vHTb8dLVUf68E' WHERE email = 'joao@cooperativa.am';
UPDATE users SET password_hash = '$2y$12$EjRqJoW.KkfCVJmvq1V.O.jnQByYGb1IM5g1vHTb8dLVUf68E' WHERE email = 'maria@escola.am.gov.br';
```

Ou gere o hash correto via PHP:
```php
<?php echo password_hash('Admin@2025', PASSWORD_BCRYPT, ['cost' => 12]);
```

---

## 2. Arquivo .env

Copie `.env.example` para `.env` e configure:

```bash
copy .env.example .env
```

Edite o `.env` com suas credenciais reais de banco de dados, Twilio e OneSignal.

---

## 3. Apache — mod_rewrite

Verifique se o `httpd.conf` do XAMPP tem `AllowOverride All` para o diretório htdocs:

```apache
<Directory "C:/xampp/htdocs">
    AllowOverride All
</Directory>
```

---

## 4. Cron Jobs (Windows — Agendador de Tarefas)

Configure 3 tarefas no Agendador de Tarefas do Windows:

| Frequência | Comando |
|---|---|
| A cada 1 min  | `php C:\xampp\htdocs\appSati-alerta\cron\run_alert_checker.php` |
| A cada 15 min | `php C:\xampp\htdocs\appSati-alerta\cron\run_risk_engine.php` |
| Diário 06:00  | `php C:\xampp\htdocs\appSati-alerta\cron\generate_daily_report.php` |

Em Linux/Mac (crontab):
```
* * * * * php /path/to/appSati-alerta/cron/run_alert_checker.php
*/15 * * * * php /path/to/appSati-alerta/cron/run_risk_engine.php
0 6 * * * php /path/to/appSati-alerta/cron/generate_daily_report.php
```

---

## 5. Acessar o Sistema

| URL | Descrição |
|---|---|
| `http://localhost/appSati-alerta/public/login` | Login (todos os perfis) |
| `http://localhost/appSati-alerta/public/` | Portal Público (sem login) |
| `http://localhost/appSati-alerta/public/admin/dashboard` | Painel Gov |
| `http://localhost/appSati-alerta/public/producer/dashboard` | Painel Produtor |

---

## 6. Testar Ingestão de Dados (Simular ESP32)

```bash
curl -X POST http://localhost/appSati-alerta/api/v1/ingest \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sati_gateway_secret_key_change_this" \
  -d '{
    "gateway_id": "GW-MAN-001",
    "sensor_eui": "A1B2C3D4E5F60001",
    "value": 185.5,
    "rssi": -110,
    "snr": 7.2,
    "battery_pct": 87,
    "timestamp": "2025-06-15T14:30:00Z"
  }'
```

Resposta esperada:
```json
{ "success": true, "reading_id": 1, "sensor_id": 1, "value": 185.5 }
```

---

## 7. Credenciais de Acesso (Padrão)

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador Gov | admin@sati.gov.br | Admin@2025 |
| Produtor Rural | joao@cooperativa.am | Admin@2025 |
| Público | maria@escola.am.gov.br | Admin@2025 |

> ⚠️ **Troque as senhas após o primeiro acesso em produção!**
