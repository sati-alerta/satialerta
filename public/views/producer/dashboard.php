<?php
/**
 * Dashboard do Produtor Rural — Foco em umidade do solo e previsão de seca
 * RBAC: rural_producer | admin_gov
 */
use Core\Database;
use Core\Helper;
use Core\Auth;

$user     = Auth::user();
$regionId = (int)($user['region_id'] ?? 1);

// Última leitura de umidade do solo
$soilData = Database::fetchOne(
    "SELECT sr.value, sr.recorded_at, s.name AS sensor_name, s.location_desc, s.battery_pct
     FROM sensor_readings sr
     JOIN sensors s ON s.id = sr.sensor_id
     JOIN sensor_types st ON st.id = s.sensor_type_id
     WHERE st.code = 'SOIL_MOISTURE' AND s.region_id = ?
     ORDER BY sr.recorded_at DESC LIMIT 1",
    [$regionId]
);

// Última leitura de umidade do ar
$airHumidity = Database::fetchOne(
    "SELECT sr.value, sr.recorded_at FROM sensor_readings sr
     JOIN sensors s ON s.id = sr.sensor_id
     JOIN sensor_types st ON st.id = s.sensor_type_id
     WHERE st.code = 'AIR_HUMIDITY' AND s.region_id = ?
     ORDER BY sr.recorded_at DESC LIMIT 1",
    [$regionId]
);

// Última leitura de qualidade do ar (fumaça)
$airQuality = Database::fetchOne(
    "SELECT sr.value, sr.recorded_at FROM sensor_readings sr
     JOIN sensors s ON s.id = sr.sensor_id
     JOIN sensor_types st ON st.id = s.sensor_type_id
     WHERE st.code = 'AIR_QUALITY' AND s.region_id = ?
     ORDER BY sr.recorded_at DESC LIMIT 1",
    [$regionId]
);

// Risco de seca preditivo
$riskMap = Database::fetchOne(
    "SELECT risk_level, risk_label FROM risk_maps
     WHERE region_id = ? AND risk_type = 'drought'
     ORDER BY generated_at DESC LIMIT 1",
    [$regionId]
);

$riskFire = Database::fetchOne(
    "SELECT risk_level, risk_label FROM risk_maps
     WHERE region_id = ? AND risk_type = 'fire'
     ORDER BY generated_at DESC LIMIT 1",
    [$regionId]
);

// Série temporal de umidade do solo — últimas 48h
$soilHistory = Database::fetchAll(
    "SELECT sr.value, DATE_FORMAT(sr.recorded_at,'%d/%m %H:%i') AS time_label
     FROM sensor_readings sr
     JOIN sensors s ON s.id = sr.sensor_id
     JOIN sensor_types st ON st.id = s.sensor_type_id
     WHERE st.code = 'SOIL_MOISTURE' AND s.region_id = ?
       AND sr.recorded_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
     ORDER BY sr.recorded_at ASC LIMIT 50",
    [$regionId]
);

// Alertas ativos na região do produtor
$myAlerts = Database::fetchAll(
    "SELECT a.title, a.message, a.triggered_at,
            al.code AS level_code, al.label AS level_label, al.color_hex
     FROM alerts a
     JOIN alert_levels al ON al.id = a.alert_level_id
     WHERE a.region_id = ? AND a.is_active = 1
     ORDER BY al.priority ASC, a.triggered_at DESC LIMIT 5",
    [$regionId]
);

// Relatórios de agricultura públicos da região
$agriReports = Database::fetchAll(
    "SELECT id, title, summary, created_at FROM reports
     WHERE region_id = ? AND type IN ('agriculture','drought') AND is_public = 1
     ORDER BY created_at DESC LIMIT 4",
    [$regionId]
);

// Regiões disponíveis para o seletor
$regions = Database::fetchAll("SELECT id, name FROM regions ORDER BY name");

$soilPct      = (float)($soilData['value']    ?? 45);
$humidityPct  = (float)($airHumidity['value'] ?? 60);
$airPpm       = (float)($airQuality['value']  ?? 15);
$droughtRisk  = (int)($riskMap['risk_level']  ?? 20);
$fireRisk     = (int)($riskFire['risk_level'] ?? 15);

// Recomendações inteligentes baseadas nos dados
$recommendations = [];
if ($soilPct < 20)     $recommendations[] = ['🚧 Solo crítico — Irrigação urgente recomendada.', 'danger'];
elseif ($soilPct < 35) $recommendations[] = ['⚠️ Solo seco — Planeje irrigação nas próximas 24h.', 'warning'];
if ($airPpm > 60)      $recommendations[] = ['🔥 Fumaça detectada — Risco de incêndio. Evite queimadas.', 'danger'];
if ($humidityPct < 40) $recommendations[] = ['💧 Umidade do ar baixa — Atenção redobrada às lavouras sensíveis.', 'warning'];
if (empty($recommendations)) $recommendations[] = ['✅ Condições favoráveis. Acompanhe as previsões semanais.', 'success'];

$baseUrl = Helper::e(Core\Config::get('app')['url']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="base-url" content="<?= $baseUrl ?>">
  <meta name="auto-refresh" content="300">
  <title>Painel do Produtor Rural — SATI-Alerta</title>
  <meta name="description" content="Monitoramento de umidade do solo, risco de seca e alertas para produtores rurais.">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= Helper::asset('css/style.css') ?>" rel="stylesheet">
  <style>
    .rec-card {
      background: var(--sati-card); border: 1px solid var(--sati-border);
      border-radius: var(--radius-sm); padding: .75rem 1rem;
      font-size: .85rem; margin-bottom: .5rem;
    }
    .rec-card.success { border-left: 3px solid var(--sati-green); }
    .rec-card.warning { border-left: 3px solid var(--sati-warning); }
    .rec-card.danger  { border-left: 3px solid var(--sati-danger); }
  </style>
</head>
<body>
<div id="sidebar-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1050"></div>
<div class="app-shell">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon" style="background:linear-gradient(135deg,#2d6a4f,#06d6a0)">
        <i class="bi bi-tree-fill" style="color:#fff"></i>
      </div>
      <div>
        <div class="brand-name">SATI<span style="color:var(--sati-green)">-Alerta</span></div>
        <div class="brand-tag">Produtor Rural</div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Meu Painel</div>
      <a href="<?= Helper::url('producer/dashboard') ?>" class="nav-link active">
        <i class="bi bi-speedometer2"></i> Dashboard da Lavoura
        <?php if (count($myAlerts) > 0): ?>
          <span class="nav-badge"><?= count($myAlerts) ?></span>
        <?php endif; ?>
      </a>
      <div class="nav-section-label mt-2">Dados Abertos</div>
      <a href="<?= Helper::url('public') ?>" class="nav-link"><i class="bi bi-globe"></i> Portal Público</a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar" style="background:linear-gradient(135deg,#2d6a4f,#06d6a0)"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
        <div><div class="user-name"><?= Helper::e($user['name']) ?></div><div class="user-role">Produtor Rural</div></div>
        <a href="<?= Helper::url('logout') ?>" class="ms-auto btn-icon" style="text-decoration:none"><i class="bi bi-box-arrow-right"></i></a>
      </div>
    </div>
  </aside>

  <main class="main-content">
    <div class="topbar">
      <div>
        <button class="btn-icon me-2 d-md-none" id="sidebar-toggle"><i class="bi bi-list fs-5"></i></button>
        <div class="topbar-title">Painel da Lavoura</div>
        <div class="topbar-subtitle">
          <i class="bi bi-geo-alt"></i>
          <select id="region-select" style="background:none;border:none;color:var(--sati-text-muted);font-size:.75rem;cursor:pointer">
            <?php foreach ($regions as $r): ?>
              <option value="<?= $r['id'] ?>" <?= $r['id'] == $regionId ? 'selected' : '' ?>><?= Helper::e($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
          — <?= date('d/m/Y H:i') ?>
        </div>
      </div>
    </div>

    <div class="page-content">

      <!-- Alertas da região -->
      <?php if (!empty($myAlerts)): ?>
      <div style="margin-bottom:1.25rem">
        <?php foreach ($myAlerts as $a): ?>
          <div class="alert-card-public level-<?= strtolower(Helper::e($a['level_code'])) ?>">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem">
              <span style="width:10px;height:10px;border-radius:50%;background:<?= Helper::e($a['color_hex']) ?>;flex-shrink:0"></span>
              <strong style="font-size:.85rem"><?= Helper::e($a['level_label']) ?> — <?= Helper::e($a['title']) ?></strong>
              <span style="margin-left:auto;font-size:.72rem;color:var(--sati-text-muted)"><?= Helper::timeAgo($a['triggered_at']) ?></span>
            </div>
            <p style="margin:0;font-size:.82rem;color:var(--sati-text-muted)"><?= Helper::e($a['message']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Gauges principais -->
      <div class="row g-3 mb-3">

        <!-- Gauge Umidade do Solo -->
        <div class="col-md-4">
          <div class="panel text-center" style="padding:1.5rem 1rem">
            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--sati-text-muted);margin-bottom:.5rem">🌱 Umidade do Solo</div>
            <div style="position:relative;height:140px">
              <canvas id="gauge-soil" data-chart="gauge" data-values='[<?= $soilPct ?>]' style="width:180px;height:140px;margin:auto;display:block"></canvas>
              <div style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);text-align:center">
                <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:<?= $soilPct < 20 ? 'var(--sati-danger)' : ($soilPct < 35 ? 'var(--sati-warning)' : 'var(--sati-green)') ?>">
                  <?= round($soilPct, 1) ?>%
                </div>
              </div>
            </div>
            <div style="font-size:.75rem;color:var(--sati-text-muted)"><?= $soilData ? Helper::timeAgo($soilData['recorded_at']) : 'Sem leitura' ?></div>
            <div style="font-size:.72rem;color:var(--sati-text-dim);margin-top:.25rem"><?= Helper::e($soilData['sensor_name'] ?? '') ?></div>
          </div>
        </div>

        <!-- Gauge Umidade do Ar -->
        <div class="col-md-4">
          <div class="panel text-center" style="padding:1.5rem 1rem">
            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--sati-text-muted);margin-bottom:.5rem">🌤 Umidade do Ar</div>
            <div style="position:relative;height:140px">
              <canvas id="gauge-humidity" data-chart="gauge" data-values='[<?= $humidityPct ?>]' style="width:180px;height:140px;margin:auto;display:block"></canvas>
              <div style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);text-align:center">
                <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:<?= $humidityPct < 40 ? 'var(--sati-warning)' : 'var(--sati-teal)' ?>">
                  <?= round($humidityPct, 1) ?>%
                </div>
              </div>
            </div>
            <div style="font-size:.75rem;color:var(--sati-text-muted)"><?= $airHumidity ? Helper::timeAgo($airHumidity['recorded_at']) : 'Sem leitura' ?></div>
          </div>
        </div>

        <!-- Cards de risco -->
        <div class="col-md-4">
          <div class="panel" style="padding:1.25rem;height:100%">
            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--sati-text-muted);margin-bottom:1rem">Índices de Risco</div>

            <!-- Risco Seca -->
            <div style="margin-bottom:1rem">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem">
                <span style="font-size:.82rem">🏜️ Seca</span>
                <span class="risk-badge <?= $droughtRisk >= 80 ? 'critical' : ($droughtRisk >= 60 ? 'high' : ($droughtRisk >= 40 ? 'moderate' : 'low')) ?>">
                  <?= Helper::e($riskMap['risk_label'] ?? 'Mínimo') ?>
                </span>
              </div>
              <div style="width:100%;height:6px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden">
                <div style="width:<?= $droughtRisk ?>%;height:100%;border-radius:3px;background:<?= $droughtRisk >= 80 ? 'var(--sati-danger)' : ($droughtRisk >= 60 ? 'var(--sati-critical)' : ($droughtRisk >= 40 ? 'var(--sati-warning)' : 'var(--sati-green)')) ?>;transition:.8s ease"></div>
              </div>
            </div>

            <!-- Risco Incêndio -->
            <div style="margin-bottom:1rem">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem">
                <span style="font-size:.82rem">🔥 Incêndio</span>
                <span class="risk-badge <?= $fireRisk >= 80 ? 'critical' : ($fireRisk >= 60 ? 'high' : ($fireRisk >= 40 ? 'moderate' : 'low')) ?>">
                  <?= Helper::e($riskFire['risk_label'] ?? 'Mínimo') ?>
                </span>
              </div>
              <div style="width:100%;height:6px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden">
                <div style="width:<?= $fireRisk ?>%;height:100%;border-radius:3px;background:<?= $fireRisk >= 80 ? 'var(--sati-danger)' : ($fireRisk >= 40 ? 'var(--sati-warning)' : 'var(--sati-green)') ?>;transition:.8s ease"></div>
              </div>
            </div>

            <!-- Qualidade do Ar -->
            <div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem">
                <span style="font-size:.82rem">💨 Qualidade do Ar</span>
                <span style="font-family:monospace;font-size:.85rem;color:<?= $airPpm > 80 ? 'var(--sati-danger)' : ($airPpm > 50 ? 'var(--sati-warning)' : 'var(--sati-green)') ?>;font-weight:700">
                  <?= round($airPpm, 1) ?> ppm
                </span>
              </div>
              <div style="width:100%;height:6px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden">
                <div style="width:<?= min(100, ($airPpm / 200) * 100) ?>%;height:100%;border-radius:3px;background:<?= $airPpm > 80 ? 'var(--sati-danger)' : ($airPpm > 50 ? 'var(--sati-warning)' : 'var(--sati-green)') ?>;transition:.8s ease"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfico série temporal do solo + Recomendações -->
      <div class="row g-3 mb-3">
        <div class="col-lg-7">
          <div class="panel">
            <div class="panel-header">
              <div class="panel-title"><i class="bi bi-activity text-green"></i> Umidade do Solo — Últimas 48h</div>
            </div>
            <div class="panel-body">
              <div style="height:220px">
                <?php if (!empty($soilHistory)): ?>
                  <canvas id="chart-soil-history"
                    data-chart="line"
                    data-labels='<?= json_encode(array_column($soilHistory, 'time_label')) ?>'
                    data-values='<?= json_encode(array_column($soilHistory, 'value')) ?>'
                    data-label="Umidade do Solo"
                    data-color="#06d6a0"
                    data-unit="%">
                  </canvas>
                <?php else: ?>
                  <div class="d-flex align-items-center justify-content-center h-100">
                    <p style="color:var(--sati-text-muted);font-size:.82rem">Aguardando dados do sensor de solo...</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Recomendações -->
        <div class="col-lg-5">
          <div class="panel" style="height:100%">
            <div class="panel-header">
              <div class="panel-title"><i class="bi bi-lightbulb-fill" style="color:var(--sati-warning)"></i> Recomendações do Sistema</div>
            </div>
            <div class="panel-body">
              <?php foreach ($recommendations as [$msg, $type]): ?>
                <div class="rec-card <?= $type ?>"><?= $msg ?></div>
              <?php endforeach; ?>

              <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sati-border)">
                <div style="font-size:.72rem;color:var(--sati-text-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.5rem">Referência de Solo</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.3rem;font-size:.75rem">
                  <div style="background:rgba(239,35,60,.1);border-radius:4px;padding:.3rem .5rem"><span style="color:var(--sati-danger)">●</span> &lt; 20% — Crítico</div>
                  <div style="background:rgba(255,209,102,.1);border-radius:4px;padding:.3rem .5rem"><span style="color:var(--sati-warning)">●</span> 20–35% — Seco</div>
                  <div style="background:rgba(0,180,216,.1);border-radius:4px;padding:.3rem .5rem"><span style="color:var(--sati-teal)">●</span> 35–60% — Ideal</div>
                  <div style="background:rgba(6,214,160,.1);border-radius:4px;padding:.3rem .5rem"><span style="color:var(--sati-green)">●</span> &gt; 60% — Saturado</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Relatórios de Agricultura -->
      <?php if (!empty($agriReports)): ?>
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="bi bi-file-earmark-bar-graph-fill text-green"></i> Relatórios Disponíveis</div>
          <a href="<?= Helper::url('public') ?>#reports" style="font-size:.75rem;color:var(--sati-teal)">Ver todos</a>
        </div>
        <div class="panel-body p-0">
          <table class="sati-table">
            <thead><tr><th>Título</th><th>Resumo</th><th>Data</th></tr></thead>
            <tbody>
              <?php foreach ($agriReports as $rep): ?>
                <tr>
                  <td style="font-weight:600;font-size:.82rem"><?= Helper::e($rep['title']) ?></td>
                  <td style="font-size:.78rem;color:var(--sati-text-muted)"><?= Helper::e(mb_strimwidth($rep['summary'] ?? '—', 0, 80, '...')) ?></td>
                  <td style="font-size:.78rem;white-space:nowrap"><?= Helper::formatDate($rep['created_at'], 'd/m/Y') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<div id="toast-stack" class="toast-stack"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= Helper::asset('js/app.js') ?>"></script>
<script src="<?= Helper::asset('js/charts.js') ?>"></script>
<script>
// Troca de região via seletor
document.getElementById('region-select')?.addEventListener('change', function() {
  window.location.href = window.location.pathname + '?region_id=' + this.value;
});
</script>
</body>
</html>
