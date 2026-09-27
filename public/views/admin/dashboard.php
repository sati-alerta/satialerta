<?php
/**
 * Painel Administrativo Governamental — Secretarias
 * RBAC: admin_gov
 */

use Core\Database;
use Core\Helper;
use Core\Auth;

// Carrega dados para o dashboard
$user = Auth::user();

// KPIs
$totalSensors  = Database::fetchOne("SELECT COUNT(*) AS n FROM sensors WHERE is_active=1")['n'] ?? 0;
$totalAlerts   = Database::fetchOne("SELECT COUNT(*) AS n FROM alerts WHERE is_active=1")['n'] ?? 0;
$alertsToday   = Database::fetchOne("SELECT COUNT(*) AS n FROM alerts WHERE DATE(triggered_at)=CURDATE()")['n'] ?? 0;
$offlineSensors= Database::fetchOne("SELECT COUNT(*) AS n FROM sensors WHERE is_active=1 AND (last_seen_at < DATE_SUB(NOW(),INTERVAL 2 HOUR) OR last_seen_at IS NULL)")['n'] ?? 0;

// Alertas ativos recentes
$activeAlerts = Database::fetchAll(
    "SELECT a.id, a.title, a.message, a.triggered_at, a.region_id,
            al.code AS level_code, al.color_hex,
            r.name AS region_name,
            s.name AS sensor_name
     FROM alerts a
     JOIN alert_levels al ON al.id = a.alert_level_id
     JOIN regions r ON r.id = a.region_id
     LEFT JOIN sensors s ON s.id = a.sensor_id
     WHERE a.is_active = 1
     ORDER BY al.priority ASC, a.triggered_at DESC
     LIMIT 15"
);

// Regiões para o formulário de alerta manual
$regions = Database::fetchAll("SELECT id, name, state FROM regions ORDER BY state, name");

// Níveis de alerta para o formulário
$alertLevels = Database::fetchAll("SELECT id, code, label, color_hex FROM alert_levels ORDER BY priority");

// Mapa de risco atual por região
$riskMaps = Database::fetchAll(
    "SELECT rm.risk_type, rm.risk_level, rm.risk_label, r.name AS region_name
     FROM risk_maps rm
     JOIN regions r ON r.id = rm.region_id
     WHERE rm.risk_type = 'combined'
     ORDER BY rm.risk_level DESC LIMIT 6"
);

// Últimas leituras (gráfico de linha — últimas 24h de nível de água)
$waterReadings = Database::fetchAll(
    "SELECT sr.value, DATE_FORMAT(sr.recorded_at,'%H:%i') AS time_label
     FROM sensor_readings sr
     JOIN sensors s ON s.id = sr.sensor_id
     JOIN sensor_types st ON st.id = s.sensor_type_id
     WHERE st.code = 'WATER_LEVEL'
       AND sr.recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
     ORDER BY sr.recorded_at ASC LIMIT 48"
);

$baseUrl = Helper::e(Core\Config::get('app')['url']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="base-url" content="<?= $baseUrl ?>">
  <meta name="auto-refresh" content="120">
  <title>Painel Administrativo — SATI-Alerta</title>
  <meta name="description" content="Painel de controle governamental para monitoramento climático, alertas e mapas de risco.">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link href="<?= Helper::asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>

<!-- Overlay mobile sidebar -->
<div id="sidebar-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1050;transition:opacity .3s"></div>

<div class="app-shell">

  <!-- ─── SIDEBAR ─────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="bi bi-broadcast-pin" style="color:#fff"></i></div>
      <div>
        <div class="brand-name">SATI<span style="color:var(--sati-teal)">-Alerta</span></div>
        <div class="brand-tag">Gov · Secretarias</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Monitoramento</div>
      <a href="<?= Helper::url('admin/dashboard') ?>" class="nav-link active">
        <i class="bi bi-grid-3x3-gap-fill"></i> Dashboard
        <?php if ($totalAlerts > 0): ?>
          <span class="nav-badge"><?= $totalAlerts ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= Helper::url('admin/sensors') ?>" class="nav-link">
        <i class="bi bi-cpu-fill"></i> Sensores IoT
      </a>
      <a href="<?= Helper::url('admin/alerts') ?>" class="nav-link">
        <i class="bi bi-bell-fill"></i> Alertas
        <?php if ($totalAlerts > 0): ?>
          <span class="nav-badge"><?= $totalAlerts ?></span>
        <?php endif; ?>
      </a>

      <div class="nav-section-label mt-2">Analytics</div>
      <a href="#" class="nav-link">
        <i class="bi bi-map-fill"></i> Mapas de Risco
      </a>
      <a href="<?= Helper::url('admin/reports') ?>" class="nav-link">
        <i class="bi bi-file-earmark-bar-graph-fill"></i> Relatórios
      </a>

      <div class="nav-section-label mt-2">Dados Abertos</div>
      <a href="<?= Helper::url('public') ?>" class="nav-link">
        <i class="bi bi-globe"></i> Portal Público
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
        <div>
          <div class="user-name"><?= Helper::e($user['name']) ?></div>
          <div class="user-role">Administrador Gov.</div>
        </div>
        <a href="<?= Helper::url('logout') ?>" class="ms-auto btn-icon" title="Sair" style="text-decoration:none">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      </div>
    </div>
  </aside>

  <!-- ─── MAIN CONTENT ────────────────────────────────────── -->
  <main class="main-content">

    <!-- Topbar -->
    <div class="topbar">
      <div>
        <button class="btn-icon me-2 d-md-none" id="sidebar-toggle"><i class="bi bi-list fs-5"></i></button>
        <div class="topbar-title">Dashboard Governamental</div>
        <div class="topbar-subtitle">
          <i class="bi bi-clock"></i> <?= date('d/m/Y H:i') ?> —
          <i class="bi bi-geo-alt"></i> Monitoramento em tempo real
        </div>
      </div>
      <div class="topbar-actions">
        <!-- Botão Alerta Manual -->
        <button class="btn-sati-danger" data-bs-toggle="modal" data-bs-target="#modalManualAlert">
          <i class="bi bi-megaphone-fill"></i>
          <span class="d-none d-sm-inline">Disparar Alerta</span>
        </button>
        <!-- Badge alertas -->
        <div class="btn-icon position-relative">
          <i class="bi bi-bell-fill"></i>
          <?php if ($totalAlerts > 0): ?>
            <span class="badge bg-danger" id="alert-count-badge"><?= $totalAlerts ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="page-content">

      <!-- ─── KPI Cards ─────────────────────────────────── -->
      <div class="kpi-grid mb-4">
        <div class="kpi-card accent-teal fade-in-up">
          <div class="kpi-icon"><i class="bi bi-cpu-fill"></i></div>
          <div class="kpi-value"><?= $totalSensors ?></div>
          <div class="kpi-label">Sensores Ativos</div>
          <?php if ($offlineSensors > 0): ?>
            <div class="kpi-delta"><span class="up"><i class="bi bi-exclamation-triangle"></i> <?= $offlineSensors ?> offline</span></div>
          <?php endif; ?>
        </div>
        <div class="kpi-card accent-danger fade-in-up">
          <div class="kpi-icon" style="--accent-color:var(--sati-danger)"><i class="bi bi-exclamation-octagon-fill"></i></div>
          <div class="kpi-value text-danger-sati"><?= $totalAlerts ?></div>
          <div class="kpi-label">Alertas Ativos</div>
          <div class="kpi-delta"><?= $alertsToday ?> hoje</div>
        </div>
        <div class="kpi-card accent-warning fade-in-up">
          <div class="kpi-icon" style="--accent-color:var(--sati-warning)"><i class="bi bi-water"></i></div>
          <div class="kpi-value"><?= count($waterReadings) > 0 ? round($waterReadings[count($waterReadings)-1]['value'], 0) : '—' ?></div>
          <div class="kpi-label">Nível Água (cm)</div>
          <div class="kpi-delta">Último sensor ativo</div>
        </div>
        <div class="kpi-card accent-green fade-in-up">
          <div class="kpi-icon" style="--accent-color:var(--sati-green)"><i class="bi bi-shield-check-fill"></i></div>
          <div class="kpi-value" style="font-size:1.2rem"><?= $totalAlerts === 0 ? 'Normal' : 'Alerta' ?></div>
          <div class="kpi-label">Status Geral</div>
          <div class="kpi-delta">Via <?= $totalSensors ?> sensores</div>
        </div>
      </div>

      <!-- ─── Mapa GIS + Alertas ────────────────────────── -->
      <div class="row g-3 mb-3">
        <!-- Mapa Leaflet -->
        <div class="col-lg-8">
          <div id="map-container">
            <div class="panel-header" style="background:var(--sati-surface);border-bottom:1px solid var(--sati-border)">
              <div class="panel-title"><i class="bi bi-map-fill text-teal"></i> Mapa de Sensores em Tempo Real</div>
              <div style="display:flex;gap:.5rem;align-items:center;font-size:.72rem;color:var(--sati-text-muted)">
                <span><span class="status-dot online me-1"></span>Ativo</span>
                <span><span style="width:8px;height:8px;background:var(--sati-warning);border-radius:50%;display:inline-block;margin-right:4px"></span>Atenção</span>
                <span><span style="width:8px;height:8px;background:var(--sati-danger);border-radius:50%;display:inline-block;margin-right:4px"></span>Alerta</span>
              </div>
            </div>
            <div id="sati-map" data-lat="-3.119" data-lng="-60.021"></div>
          </div>
        </div>

        <!-- Painel de Alertas Ativos -->
        <div class="col-lg-4">
          <div class="panel" style="height:100%">
            <div class="panel-header">
              <div class="panel-title"><i class="bi bi-bell-fill text-danger-sati"></i> Alertas Ativos</div>
              <a href="<?= Helper::url('admin/alerts') ?>" style="font-size:.75rem;color:var(--sati-teal)">Ver todos</a>
            </div>
            <div class="panel-body p-0" style="max-height:468px;overflow-y:auto;padding:0 1rem !important">
              <div id="alert-list" style="padding:0 1rem">
                <?php if (empty($activeAlerts)): ?>
                  <div class="text-center py-4">
                    <i class="bi bi-shield-check" style="font-size:2rem;color:var(--sati-green)"></i>
                    <p style="font-size:.82rem;color:var(--sati-text-muted);margin-top:.5rem">Nenhum alerta ativo</p>
                  </div>
                <?php else: ?>
                  <?php foreach ($activeAlerts as $a): ?>
                    <div class="alert-item">
                      <div class="alert-dot <?= Helper::e($a['level_code']) ?>"></div>
                      <div class="alert-body">
                        <div class="alert-title"><?= Helper::e($a['title']) ?></div>
                        <div class="alert-meta">
                          <i class="bi bi-geo-alt"></i> <?= Helper::e($a['region_name']) ?>
                          &nbsp;·&nbsp; <?= Helper::timeAgo($a['triggered_at']) ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Gráfico Nível Água + Mapas de Risco ──────── -->
      <div class="row g-3 mb-3">
        <!-- Gráfico série temporal -->
        <div class="col-lg-7">
          <div class="panel">
            <div class="panel-header">
              <div class="panel-title"><i class="bi bi-activity text-teal"></i> Nível de Água — Últimas 24h</div>
              <span style="font-size:.72rem;color:var(--sati-text-muted)">Todos os sensores</span>
            </div>
            <div class="panel-body">
              <div style="height:220px">
                <canvas id="chart-water"
                  data-chart="line"
                  data-labels='<?= json_encode(array_column($waterReadings, 'time_label')) ?>'
                  data-values='<?= json_encode(array_column($waterReadings, 'value')) ?>'
                  data-label="Nível de Água"
                  data-color="#00b4d8"
                  data-unit="cm">
                </canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Cards de risco preditivo -->
        <div class="col-lg-5">
          <div class="panel" style="height:100%">
            <div class="panel-header">
              <div class="panel-title"><i class="bi bi-graph-up-arrow" style="color:var(--sati-warning)"></i> Índice de Risco por Região</div>
            </div>
            <div class="panel-body p-0">
              <?php if (empty($riskMaps)): ?>
                <p class="text-center text-muted-sati py-3" style="font-size:.82rem">Processando dados de risco...</p>
              <?php else: ?>
                <div style="padding:.5rem">
                  <?php foreach ($riskMaps as $rm): ?>
                    <?php
                    $levelClass = match(true) {
                        $rm['risk_level'] >= 80 => 'critical',
                        $rm['risk_level'] >= 60 => 'high',
                        $rm['risk_level'] >= 40 => 'moderate',
                        $rm['risk_level'] >= 20 => 'low',
                        default => 'minimal',
                    };
                    ?>
                    <div style="display:flex;align-items:center;gap:.75rem;padding:.5rem .75rem;border-radius:8px;margin-bottom:.25rem;background:rgba(0,0,0,.2)">
                      <div style="flex:1;font-size:.82rem;color:var(--sati-text)"><?= Helper::e($rm['region_name']) ?></div>
                      <div style="width:120px;background:rgba(255,255,255,.05);border-radius:4px;height:6px;overflow:hidden">
                        <div style="width:<?= $rm['risk_level'] ?>%;height:100%;background:<?= $rm['risk_level'] >= 80 ? 'var(--sati-danger)' : ($rm['risk_level'] >= 60 ? 'var(--sati-critical)' : ($rm['risk_level'] >= 40 ? 'var(--sati-warning)' : 'var(--sati-green)')) ?>"></div>
                      </div>
                      <span class="risk-badge <?= $levelClass ?>"><?= Helper::e($rm['risk_label']) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /page-content -->
  </main>
</div><!-- /app-shell -->

<!-- ─── Modal: Disparar Alerta Manual ───────────────────── -->
<div class="modal fade" id="modalManualAlert" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--sati-card);border:1px solid var(--sati-border);border-radius:var(--radius)">
      <div class="modal-header" style="border-bottom:1px solid var(--sati-border)">
        <h5 class="modal-title" style="color:var(--sati-text);font-size:1rem"><i class="bi bi-megaphone-fill text-danger-sati me-2"></i>Disparar Alerta Manual</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-manual-alert" novalidate>
          <div class="mb-3">
            <label class="form-label">Região</label>
            <select name="region_id" class="form-select" required>
              <option value="">Selecione a região...</option>
              <?php foreach ($regions as $r): ?>
                <option value="<?= $r['id'] ?>"><?= Helper::e($r['name']) ?> — <?= $r['state'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nível do Alerta</label>
            <select name="alert_level_id" class="form-select" required>
              <option value="">Selecione o nível...</option>
              <?php foreach ($alertLevels as $al): ?>
                <option value="<?= $al['id'] ?>" style="color:<?= $al['color_hex'] ?>">
                  <?= Helper::e($al['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Título do Alerta</label>
            <input type="text" name="title" class="form-control" placeholder="Ex: Risco de Alagamento — Igarapé do Mindu" required maxlength="255">
          </div>
          <div class="mb-3">
            <label class="form-label">Mensagem</label>
            <textarea name="message" class="form-control" rows="3" required placeholder="Mensagem completa para SMS e Push Notification..."></textarea>
          </div>
          <button type="submit" class="btn-sati-danger w-100 justify-content-center py-2">
            <i class="bi bi-send-fill"></i> Disparar Alerta
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Toast Stack -->
<div id="toast-stack" class="toast-stack"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= Helper::asset('js/app.js') ?>"></script>
<script src="<?= Helper::asset('js/map.js') ?>"></script>
<script src="<?= Helper::asset('js/charts.js') ?>"></script>
</body>
</html>
