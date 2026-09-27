<?php
/**
 * setup.php — Script de configuração inicial (REMOVA EM PRODUÇÃO)
 * Acesse uma vez:  http://localhost/appSati-alerta/setup.php
 */

define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/core/Config.php';
require_once ROOT_PATH . '/core/Database.php';

use Core\Config;
use Core\Database;

Config::loadEnv(ROOT_PATH . '/.env');

$hash = password_hash('Admin@2025', PASSWORD_BCRYPT, ['cost' => 12]);

$output = [];

// 1. Atualiza senhas dos usuários seed
try {
    Database::query("UPDATE users SET password_hash = ?", [$hash]);
    $output[] = ['✅', 'Senhas de todos os usuários definidas para: Admin@2025'];
} catch (\Exception $e) {
    $output[] = ['❌', 'Erro ao atualizar senhas: ' . $e->getMessage()];
}

// 2. Verifica tabelas essenciais
$tables = ['roles','users','regions','sensor_types','sensors','alerts','alert_levels','alert_rules'];
foreach ($tables as $t) {
    try {
        $cnt = Database::fetchOne("SELECT COUNT(*) AS n FROM {$t}")['n'] ?? 0;
        $output[] = ['✅', "Tabela <code>{$t}</code>: {$cnt} registros"];
    } catch (\Exception $e) {
        $output[] = ['❌', "Tabela <code>{$t}</code>: NÃO ENCONTRADA — execute schema.sql"];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Setup — SATI-Alerta</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap');
  body { font-family:'Inter',sans-serif; background:#080f1a; color:#e2eaf4; max-width:700px; margin:2rem auto; padding:1.5rem; }
  h1 { color:#00b4d8; }
  .item { padding:.5rem .75rem; border-radius:6px; margin:.4rem 0; background:#0f2040; font-size:.88rem; border-left:3px solid #00b4d8; }
  .item.fail { border-left-color:#ef233c; }
  a { display:inline-block; margin-top:1.5rem; background:#0077b6; color:#fff; padding:.6rem 1.5rem; border-radius:8px; text-decoration:none; font-weight:700; }
  code { background:rgba(0,180,216,.15); padding:.1rem .4rem; border-radius:4px; font-size:.85rem; }
</style>
</head>
<body>
  <h1>🛠️ SATI-Alerta — Setup</h1>
  <p style="color:#7a90aa;font-size:.85rem">Verificação e configuração inicial do sistema.</p>

  <?php foreach ($output as [$icon, $msg]): ?>
    <div class="item <?= str_contains($icon,'❌') ? 'fail' : '' ?>">
      <?= $icon ?> <?= $msg ?>
    </div>
  <?php endforeach; ?>

  <div style="margin-top:1.5rem;padding:1rem;background:rgba(239,35,60,.1);border:1px solid rgba(239,35,60,.3);border-radius:8px;font-size:.82rem;color:#ef233c">
    ⚠️ <strong>Apague este arquivo (setup.php) após a configuração!</strong><br>
    Ele altera senhas do banco de dados e não deve ficar acessível em produção.
  </div>

  <a href="public/index.php">→ Ir para o Sistema</a>
  <a href="public/index.php?page=login" style="background:#0f2040;margin-left:.5rem">→ Login</a>
</body>
</html>
