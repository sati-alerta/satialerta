<?php
/**
 * Interface Pública — Portal cidadão / escolas / comunidade
 * Acesso sem login — Plataforma de Dados Abertos (ODS 17)
 * Design limpo, mobile-first e leve
 */

// Carrega core só se não estiver carregado (acesso direto ou via router)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 3));
    require_once ROOT_PATH . '/core/Config.php';
    require_once ROOT_PATH . '/core/Database.php';
    require_once ROOT_PATH . '/core/Helper.php';
    \Core\Config::loadEnv(ROOT_PATH . '/.env');
    $appCfg = \Core\Config::get('app');
    date_default_timezone_set($appCfg['timezone'] ?? 'America/Manaus');
    if (!empty($appCfg['url'])) {
        \Core\Helper::setBaseUrl($appCfg['url']);
    }
}

use Core\Database;
use Core\Helper;

// Seleção de região (cookie ou padrão)
$selectedRegion = (int)($_GET['region_id'] ?? ($_COOKIE['sati_region'] ?? 1));

// Região selecionada
$region = Database::fetchOne("SELECT id, name, state FROM regions WHERE id = ?", [$selectedRegion]);
if (!$region) {
    $region   = Database::fetchOne("SELECT id, name, state FROM regions LIMIT 1");
    $selectedRegion = (int)($region['id'] ?? 1);
}

// Alertas ativos na região
$activeAlerts = Database::fetchAll(
    "SELECT a.title, a.message, a.triggered_at,
            al.code AS level_code, al.label AS level_label, al.color_hex, al.priority
     FROM alerts a
     JOIN alert_levels al ON al.id = a.alert_level_id
     WHERE a.region_id = ? AND a.is_active = 1
     ORDER BY al.priority ASC, a.triggered_at DESC",
    [$selectedRegion]
);

// Risco combinado da região
$combinedRisk = Database::fetchOne(
    "SELECT risk_level, risk_label, risk_type FROM risk_maps
     WHERE region_id = ? AND risk_type = 'combined'
     ORDER BY generated_at DESC LIMIT 1",
    [$selectedRegion]
);

// Últimas leituras dos sensores da região
$latestReadings = Database::fetchAll(
    "SELECT s.name AS sensor_name, st.name AS type_name, st.unit, st.icon,
            sr.value, sr.recorded_at
     FROM sensor_readings sr
     JOIN sensors s ON s.id = sr.sensor_id
     JOIN sensor_types st ON st.id = s.sensor_type_id
     WHERE s.region_id = ?
       AND sr.id = (SELECT MAX(sr2.id) FROM sensor_readings sr2 WHERE sr2.sensor_id = s.id)
     ORDER BY st.code",
    [$selectedRegion]
);

// Relatórios públicos
$publicReports = Database::fetchAll(
    "SELECT rp.id, rp.type, rp.title, rp.summary, rp.created_at
     FROM reports rp
     WHERE rp.is_public = 1 AND rp.region_id = ?
     ORDER BY rp.created_at DESC LIMIT 6",
    [$selectedRegion]
);

// Todas as regiões para seletor
$allRegions = Database::fetchAll("SELECT id, name, state FROM regions ORDER BY state, name");

$riskLevel = (int)($combinedRisk['risk_level'] ?? 0);
$riskLabel = $combinedRisk['risk_label'] ?? 'Normal';

$riskBgColor = match(true) {
    $riskLevel >= 80 => '#c1121f22',
    $riskLevel >= 60 => '#f7700022',
    $riskLevel >= 40 => '#ffd16622',
    default          => '#06d6a022',
};
$riskBarColor = match(true) {
    $riskLevel >= 80 => '#ef233c',
    $riskLevel >= 60 => '#f77f00',
    $riskLevel >= 40 => '#ffd166',
    default          => '#06d6a0',
};

$topAlertLevel = $activeAlerts[0]['level_code'] ?? 'INFO';
$hasEmergency  = !empty(array_filter($activeAlerts, fn($a) => $a['level_code'] === 'EMERGENCY'));

$baseUrl = Helper::e(\Core\Config::get('app')['url']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="base-url" content="<?= $baseUrl ?>">
  <title>SATI-Alerta — Portal Público de Monitoramento Climático</title>
  <meta name="description" content="Alertas climáticos em tempo real, mapas de risco e dados abertos para sua região. Proteção de vidas e prevenção de desastres.">
  <meta name="keywords" content="alerta enchente, seca, queimada, monitoramento climático, amazonas, bauru, são paulo">

  <!-- Leve: sem Bootstrap full, apenas ícones -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= Helper::asset('css/style.css') ?>" rel="stylesheet">
  <style>
    .public-nav {
      background: var(--sati-surface);
      border-bottom: 1px solid var(--sati-border);
      padding: .75rem 1.25rem;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
    }
    .public-logo { font-family:'Syne',sans-serif; font-weight:800; font-size:1.1rem; color:#fff; }
    .public-logo span { color:var(--sati-teal); }

    .region-select-btn {
      background: var(--sati-card);
      border: 1px solid var(--sati-border);
      border-radius: 50px;
      padding: .35rem .85rem;
      color: var(--sati-text);
      font-size: .8rem;
      cursor: pointer;
    }

    .risk-hero {
      background: <?= $riskBgColor ?>;
      border: 1px solid <?= $riskBarColor ?>44;
      border-radius: var(--radius);
      padding: 1.5rem;
      text-align: center;
      margin-bottom: 1rem;
    }

    .risk-score-circle {
      width: 100px; height: 100px;
      border: 4px solid <?= $riskBarColor ?>;
      border-radius: 50%;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      margin: 1rem auto;
      box-shadow: 0 0 20px <?= $riskBarColor ?>44;
    }

    .sensor-reading-card {
      background: var(--sati-card);
      border: 1px solid var(--sati-border);
      border-radius: var(--radius-sm);
      padding: .875rem 1rem;
      display: flex; align-items: center; gap: .75rem;
    }

    .report-public-card {
      background: var(--sati-card);
      border: 1px solid var(--sati-border);
      border-radius: var(--radius-sm);
      padding: 1rem;
      transition: var(--transition);
    }
    .report-public-card:hover { border-color: var(--sati-teal); transform: translateY(-2px); }

    .section-title {
      font-family: 'Syne', sans-serif;
      font-size: 1rem; font-weight: 700;
      color: var(--sati-text);
      margin-bottom: .75rem;
      display: flex; align-items: center; gap: .5rem;
    }

    .ods-tag {
      display: inline-flex; align-items: center; gap: .3rem;
      padding: .2rem .55rem; border-radius: 4px;
      font-size: .67rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .05em;
    }

    /* Tornar mais leve: sem CSS pesado */
    @media (min-width:600px) {
      .main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
      .readings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
      .reports-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .75rem; }
    }
  </style>
</head>
<body class="public-page">

<!-- Navbar leve -->
<nav class="public-nav">
  <div class="public-logo">SATI<span>-Alerta</span></div>
  <div style="display:flex;align-items:center;gap:.5rem">
    <select class="region-select-btn" id="region-select" onchange="changeRegion(this.value)">
      <?php foreach ($allRegions as $r): ?>
        <option value="<?= $r['id'] ?>" <?= $r['id'] == $selectedRegion ? 'selected' : '' ?>>
          📍 <?= Helper::e($r['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <a href="<?= Helper::url('login') ?>" style="background:rgba(0,180,216,.12);border:1px solid rgba(0,180,216,.2);border-radius:50px;padding:.3rem .75rem;font-size:.78rem;color:var(--sati-teal)">
      <i class="bi bi-person-fill"></i>
    </a>
  </div>
</nav>

<!-- Banner de Emergência (se houver) -->
<?php if ($hasEmergency): ?>
<div style="background:#c1121f;color:#fff;text-align:center;padding:.6rem;font-size:.85rem;font-weight:700;animation:blink-border 1s infinite;border-bottom:2px solid #ef233c">
  🚨 EMERGÊNCIA ATIVA NA SUA REGIÃO — <?= Helper::e($activeAlerts[0]['title']) ?>
</div>
<?php endif; ?>

<div style="max-width:900px;margin:0 auto;padding:1rem">

  <!-- Hero: Status da Região -->
  <div class="risk-hero fade-in-up">
    <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--sati-text-muted)">Status da Região</div>
    <div style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;margin:.25rem 0"><?= Helper::e($region['name']) ?>, <?= $region['state'] ?></div>

    <div class="risk-score-circle">
      <div style="font-family:'Syne',sans-serif;font-size:1.75rem;font-weight:800;color:<?= $riskBarColor ?>;line-height:1"><?= $riskLevel ?></div>
      <div style="font-size:.65rem;color:var(--sati-text-muted)">/ 100</div>
    </div>

    <div style="font-size:1.1rem;font-weight:700;color:<?= $riskBarColor ?>"><?= Helper::e($riskLabel) ?></div>
    <div style="font-size:.78rem;color:var(--sati-text-muted);margin-top:.25rem">Índice de Risco Combinado</div>

    <!-- Barra de risco -->
    <div style="max-width:280px;margin:1rem auto 0;background:rgba(255,255,255,.06);height:8px;border-radius:4px;overflow:hidden">
      <div style="width:<?= $riskLevel ?>%;height:100%;background:<?= $riskBarColor ?>;border-radius:4px;transition:width 1s ease"></div>
    </div>
  </div>

  <!-- Alertas Ativos -->
  <?php if (!empty($activeAlerts)): ?>
  <div style="margin-bottom:1.5rem">
    <div class="section-title"><i class="bi bi-bell-fill" style="color:var(--sati-danger)"></i> Alertas Ativos</div>
    <?php foreach ($activeAlerts as $a): ?>
      <div class="alert-card-public level-<?= strtolower($a['level_code']) ?>" style="margin-bottom:.75rem">
        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem">
          <span style="width:10px;height:10px;border-radius:50%;background:<?= Helper::e($a['color_hex']) ?>;flex-shrink:0"></span>
          <strong style="font-size:.88rem"><?= Helper::e($a['level_label']) ?> — <?= Helper::e($a['title']) ?></strong>
          <span style="margin-left:auto;font-size:.7rem;color:var(--sati-text-muted);white-space:nowrap"><?= Helper::timeAgo($a['triggered_at']) ?></span>
        </div>
        <p style="margin:0;font-size:.83rem;color:var(--sati-text-muted);padding-left:1.1rem"><?= Helper::e($a['message']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div style="text-align:center;background:var(--sati-card);border:1px solid var(--sati-border);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem">
    <i class="bi bi-shield-check-fill" style="font-size:2rem;color:var(--sati-green)"></i>
    <div style="font-weight:700;margin:.5rem 0 .25rem">Região Sem Alertas Ativos</div>
    <div style="font-size:.8rem;color:var(--sati-text-muted)">Nenhuma ocorrência registrada no momento. Mantenha-se informado.</div>
  </div>
  <?php endif; ?>

  <!-- Leituras dos Sensores -->
  <?php if (!empty($latestReadings)): ?>
  <div style="margin-bottom:1.5rem">
    <div class="section-title"><i class="bi bi-cpu-fill text-teal"></i> Dados dos Sensores</div>
    <div class="readings-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.5rem">
      <?php foreach ($latestReadings as $rd): ?>
        <div class="sensor-reading-card">
          <i class="bi <?= Helper::e($rd['icon'] ?? 'bi-cpu') ?>" style="font-size:1.4rem;color:var(--sati-teal);flex-shrink:0"></i>
          <div>
            <div style="font-size:.72rem;color:var(--sati-text-muted)"><?= Helper::e($rd['type_name']) ?></div>
            <div style="font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:700;color:var(--sati-text)">
              <?= round((float)$rd['value'], 1) ?> <span style="font-size:.75rem;font-weight:400;color:var(--sati-text-muted)"><?= Helper::e($rd['unit'] ?? '') ?></span>
            </div>
            <div style="font-size:.68rem;color:var(--sati-text-dim)"><?= Helper::timeAgo($rd['recorded_at']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- O que fazer em cada situação -->
  <div style="margin-bottom:1.5rem">
    <div class="section-title"><i class="bi bi-info-circle-fill" style="color:var(--sati-teal)"></i> O que fazer em cada situação?</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.5rem">
      <div style="background:rgba(0,180,216,.07);border:1px solid rgba(0,180,216,.15);border-radius:var(--radius-sm);padding:1rem">
        <div style="font-weight:700;margin-bottom:.4rem">🌊 Risco de Inundação</div>
        <ul style="margin:0;padding-left:1.1rem;font-size:.8rem;color:var(--sati-text-muted);line-height:1.8">
          <li>Desligue a energia elétrica</li>
          <li>Mova para andares superiores</li>
          <li>Ligue 199 (Proteção Civil)</li>
          <li>Não atravesse ruas alagadas</li>
        </ul>
      </div>
      <div style="background:rgba(247,127,0,.07);border:1px solid rgba(247,127,0,.15);border-radius:var(--radius-sm);padding:1rem">
        <div style="font-weight:700;margin-bottom:.4rem">🔥 Risco de Incêndio / Fumaça</div>
        <ul style="margin:0;padding-left:1.1rem;font-size:.8rem;color:var(--sati-text-muted);line-height:1.8">
          <li>Mantenha janelas fechadas</li>
          <li>Use máscara se precisar sair</li>
          <li>Ligue 193 (Bombeiros)</li>
          <li>Não realize queimadas</li>
        </ul>
      </div>
      <div style="background:rgba(255,209,102,.07);border:1px solid rgba(255,209,102,.15);border-radius:var(--radius-sm);padding:1rem">
        <div style="font-weight:700;margin-bottom:.4rem">🏜️ Seca Severa</div>
        <ul style="margin:0;padding-left:1.1rem;font-size:.8rem;color:var(--sati-text-muted);line-height:1.8">
          <li>Economize água</li>
          <li>Evite exposição ao sol intenso</li>
          <li>Mantenha-se hidratado</li>
          <li>Proteja lavouras com cobertura</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Dados Abertos / Relatórios (ODS 17) -->
  <?php if (!empty($publicReports)): ?>
  <div id="reports" style="margin-bottom:1.5rem">
    <div class="section-title"><i class="bi bi-database-fill" style="color:var(--sati-green)"></i> Plataforma de Dados Abertos</div>
    <p style="font-size:.8rem;color:var(--sati-text-muted);margin-bottom:.75rem">Relatórios climáticos disponíveis para pesquisa, educação e planejamento público — alinhado ao ODS 17 (Parcerias para os Objetivos).</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.75rem">
      <?php foreach ($publicReports as $rep): ?>
        <div class="report-public-card">
          <div style="font-size:.72rem;color:var(--sati-text-muted);margin-bottom:.3rem"><?= Helper::formatDate($rep['created_at'], 'd/m/Y') ?></div>
          <div style="font-weight:600;font-size:.85rem;margin-bottom:.35rem;line-height:1.4"><?= Helper::e($rep['title']) ?></div>
          <?php if ($rep['summary']): ?>
            <div style="font-size:.77rem;color:var(--sati-text-muted);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
              <?= Helper::e($rep['summary']) ?>
            </div>
          <?php endif; ?>
          <div style="margin-top:.6rem">
            <span style="font-size:.68rem;background:rgba(0,180,216,.1);color:var(--sati-teal);border-radius:4px;padding:.15rem .4rem">
              <i class="bi bi-download"></i> Dados Abertos
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Contatos de Emergência -->
  <div style="background:var(--sati-card);border:1px solid var(--sati-border);border-radius:var(--radius);padding:1.25rem;margin-bottom:1.5rem">
    <div class="section-title"><i class="bi bi-telephone-fill" style="color:var(--sati-danger)"></i> Contatos de Emergência</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.5rem;text-align:center">
      <?php foreach ([['193','🚒','Bombeiros'],['192','🚑','SAMU'],['190','👮','Polícia'],['199','🏥','Proteção Civil']] as [$num,$ico,$label]): ?>
        <a href="tel:<?= $num ?>" style="background:rgba(239,35,60,.08);border:1px solid rgba(239,35,60,.15);border-radius:var(--radius-sm);padding:.75rem .5rem;display:block;text-decoration:none">
          <div style="font-size:1.3rem"><?= $ico ?></div>
          <div style="font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800;color:var(--sati-danger)"><?= $num ?></div>
          <div style="font-size:.72rem;color:var(--sati-text-muted)"><?= $label ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Rodapé ODS -->
  <div style="text-align:center;padding:1.5rem 0;border-top:1px solid var(--sati-border)">
    <div style="margin-bottom:.75rem">
      <?php foreach (['ODS 3 — Saúde e Bem-estar','ODS 11 — Cidades Sustentáveis','ODS 13 — Ação Climática','ODS 17 — Parcerias'] as $ods): ?>
        <span class="ods-tag" style="background:rgba(0,180,216,.1);color:var(--sati-teal);border:1px solid rgba(0,180,216,.15);margin:.2rem">
          <i class="bi bi-check2-circle"></i> <?= $ods ?>
        </span>
      <?php endforeach; ?>
    </div>
    <div style="font-size:.72rem;color:var(--sati-text-dim)">
      SATI-Alerta © <?= date('Y') ?> — Plataforma de Dados Abertos para Monitoramento Climático<br>
      Rede LoRaWAN / Sateliot · Dados atualizados a cada 15 minutos
    </div>
    <div style="margin-top:.5rem">
      <a href="<?= Helper::url('login') ?>" style="font-size:.75rem;color:var(--sati-text-muted)">
        <i class="bi bi-lock"></i> Acesso Institucional
      </a>
    </div>
  </div>

</div><!-- /container -->

<script>
function changeRegion(id) {
  document.cookie = 'sati_region=' + id + '; path=/; max-age=2592000';
  window.location.href = window.location.pathname + '?region_id=' + id;
}
</script>
</body>
</html>
