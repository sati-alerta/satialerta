<?php
/**
 * Painel Admin — Gerenciamento de Sensores IoT
 * RBAC: admin_gov
 */
use Core\Database;
use Core\Helper;
use Core\Auth;

$user = Auth::user();

// Busca sensores com última leitura e status
$sensors = Database::fetchAll(
    "SELECT s.id, s.eui, s.name, s.latitude, s.longitude, s.location_desc,
            s.battery_pct, s.is_active, s.last_seen_at, s.installed_at,
            st.code AS type_code, st.name AS type_name, st.unit, st.icon,
            r.name AS region_name,
            (SELECT value FROM sensor_readings sr
             WHERE sr.sensor_id = s.id ORDER BY sr.recorded_at DESC LIMIT 1) AS last_value,
            (SELECT recorded_at FROM sensor_readings sr
             WHERE sr.sensor_id = s.id ORDER BY sr.recorded_at DESC LIMIT 1) AS last_reading_at
     FROM sensors s
     JOIN sensor_types st ON st.id = s.sensor_type_id
     JOIN regions r ON r.id = s.region_id
     ORDER BY s.is_active DESC, s.last_seen_at DESC"
);

$sensorTypes = Database::fetchAll("SELECT id, code, name, unit, icon FROM sensor_types ORDER BY name");
$regions     = Database::fetchAll("SELECT id, name, state FROM regions ORDER BY state, name");

$baseUrl = Helper::e(Core\Config::get('app')['url']);
$totalSensors  = count($sensors);
$onlineSensors = count(array_filter($sensors, fn($s) => $s['is_active'] && $s['last_seen_at'] && strtotime($s['last_seen_at']) > time() - 7200));
$offlineSensors = $totalSensors - $onlineSensors;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="base-url" content="<?= $baseUrl ?>">
  <title>Sensores IoT — SATI-Alerta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= Helper::asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div id="sidebar-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1050"></div>
<div class="app-shell">

  <!-- SIDEBAR -->
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
      <a href="<?= Helper::url('admin/dashboard') ?>" class="nav-link"><i class="bi bi-grid-3x3-gap-fill"></i> Dashboard</a>
      <a href="<?= Helper::url('admin/sensors') ?>" class="nav-link active"><i class="bi bi-cpu-fill"></i> Sensores IoT</a>
      <a href="<?= Helper::url('admin/alerts') ?>" class="nav-link"><i class="bi bi-bell-fill"></i> Alertas</a>
      <div class="nav-section-label mt-2">Analytics</div>
      <a href="#" class="nav-link"><i class="bi bi-map-fill"></i> Mapas de Risco</a>
      <a href="<?= Helper::url('admin/reports') ?>" class="nav-link"><i class="bi bi-file-earmark-bar-graph-fill"></i> Relatórios</a>
      <div class="nav-section-label mt-2">Dados Abertos</div>
      <a href="<?= Helper::url('public') ?>" class="nav-link"><i class="bi bi-globe"></i> Portal Público</a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
        <div><div class="user-name"><?= Helper::e($user['name']) ?></div><div class="user-role">Administrador Gov.</div></div>
        <a href="<?= Helper::url('logout') ?>" class="ms-auto btn-icon" title="Sair" style="text-decoration:none"><i class="bi bi-box-arrow-right"></i></a>
      </div>
    </div>
  </aside>

  <main class="main-content">
    <div class="topbar">
      <div>
        <button class="btn-icon me-2 d-md-none" id="sidebar-toggle"><i class="bi bi-list fs-5"></i></button>
        <div class="topbar-title">Sensores IoT</div>
        <div class="topbar-subtitle"><i class="bi bi-cpu"></i> Rede LoRaWAN / Sateliot via ESP32 Gateway</div>
      </div>
      <div class="topbar-actions">
        <button class="btn-sati-primary" data-bs-toggle="modal" data-bs-target="#modalAddSensor">
          <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">Cadastrar Sensor</span>
        </button>
      </div>
    </div>

    <div class="page-content">

      <!-- KPIs rápidos -->
      <div class="kpi-grid mb-4" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
        <div class="kpi-card accent-teal fade-in-up">
          <div class="kpi-icon"><i class="bi bi-cpu-fill"></i></div>
          <div class="kpi-value"><?= $totalSensors ?></div>
          <div class="kpi-label">Total Sensores</div>
        </div>
        <div class="kpi-card accent-green fade-in-up">
          <div class="kpi-icon" style="--accent-color:var(--sati-green)"><i class="bi bi-wifi"></i></div>
          <div class="kpi-value"><?= $onlineSensors ?></div>
          <div class="kpi-label">Online (2h)</div>
        </div>
        <div class="kpi-card accent-danger fade-in-up">
          <div class="kpi-icon" style="--accent-color:var(--sati-danger)"><i class="bi bi-wifi-off"></i></div>
          <div class="kpi-value"><?= $offlineSensors ?></div>
          <div class="kpi-label">Offline/Sem Dado</div>
        </div>
        <div class="kpi-card accent-warning fade-in-up">
          <div class="kpi-icon" style="--accent-color:var(--sati-warning)"><i class="bi bi-battery-half"></i></div>
          <div class="kpi-value"><?= count(array_filter($sensors, fn($s) => ($s['battery_pct'] ?? 100) < 20)) ?></div>
          <div class="kpi-label">Bateria Baixa</div>
        </div>
      </div>

      <!-- Filtro de busca rápida -->
      <div class="panel mb-3">
        <div class="panel-body py-2">
          <div class="row g-2 align-items-center">
            <div class="col-md-4">
              <div style="position:relative">
                <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--sati-text-muted)"></i>
                <input type="text" id="sensor-search" class="form-control ps-4" placeholder="Buscar sensor por nome, EUI ou localização...">
              </div>
            </div>
            <div class="col-md-3">
              <select id="filter-type" class="form-select">
                <option value="">Todos os tipos</option>
                <?php foreach ($sensorTypes as $st): ?>
                  <option value="<?= Helper::e($st['code']) ?>"><?= Helper::e($st['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <select id="filter-status" class="form-select">
                <option value="">Todos os status</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
              </select>
            </div>
            <div class="col-md-2 text-end">
              <span style="font-size:.78rem;color:var(--sati-text-muted)" id="sensor-count"><?= $totalSensors ?> sensores</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabela de sensores -->
      <div class="panel">
        <div class="panel-body p-0">
          <div style="overflow-x:auto">
            <table class="sati-table" id="sensors-table">
              <thead>
                <tr>
                  <th>Status</th>
                  <th>Sensor / EUI</th>
                  <th>Tipo</th>
                  <th>Região</th>
                  <th>Última Leitura</th>
                  <th>Última Vez Visto</th>
                  <th>Bateria</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($sensors as $sensor):
                    $isOnline   = $sensor['is_active'] && $sensor['last_seen_at'] && strtotime($sensor['last_seen_at']) > time() - 7200;
                    $statusClass= $isOnline ? 'online' : 'offline';
                    $batteryPct = (int)($sensor['battery_pct'] ?? 100);
                    $batteryColor = $batteryPct < 20 ? 'var(--sati-danger)' : ($batteryPct < 50 ? 'var(--sati-warning)' : 'var(--sati-green)');
                ?>
                <tr class="sensor-row"
                    data-type="<?= Helper::e($sensor['type_code']) ?>"
                    data-status="<?= $isOnline ? 'online' : 'offline' ?>">
                  <td>
                    <span class="status-dot <?= $statusClass ?>"></span>
                    <span style="font-size:.72rem;color:var(--sati-text-muted);margin-left:4px"><?= $isOnline ? 'Online' : 'Offline' ?></span>
                  </td>
                  <td>
                    <div style="font-weight:600;color:var(--sati-text);font-size:.83rem"><?= Helper::e($sensor['name'] ?? 'Sem nome') ?></div>
                    <div style="font-size:.7rem;color:var(--sati-text-muted);font-family:monospace"><?= Helper::e($sensor['eui']) ?></div>
                    <?php if ($sensor['location_desc']): ?>
                      <div style="font-size:.7rem;color:var(--sati-text-dim)"><i class="bi bi-geo-alt"></i> <?= Helper::e($sensor['location_desc']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span style="display:inline-flex;align-items:center;gap:.3rem;background:rgba(0,180,216,.08);border:1px solid rgba(0,180,216,.15);border-radius:20px;padding:.15rem .6rem;font-size:.72rem;color:var(--sati-teal)">
                      <i class="bi <?= Helper::e($sensor['icon'] ?? 'bi-cpu') ?>"></i>
                      <?= Helper::e($sensor['type_name']) ?>
                    </span>
                  </td>
                  <td style="font-size:.82rem"><?= Helper::e($sensor['region_name']) ?></td>
                  <td>
                    <?php if ($sensor['last_value'] !== null): ?>
                      <strong style="font-size:.95rem"><?= round((float)$sensor['last_value'], 1) ?></strong>
                      <span style="color:var(--sati-text-muted);font-size:.75rem"> <?= Helper::e($sensor['unit'] ?? '') ?></span>
                      <?php if ($sensor['last_reading_at']): ?>
                        <div style="font-size:.7rem;color:var(--sati-text-muted)"><?= Helper::timeAgo($sensor['last_reading_at']) ?></div>
                      <?php endif; ?>
                    <?php else: ?>
                      <span style="color:var(--sati-text-dim);font-size:.78rem">Sem leitura</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.78rem;color:var(--sati-text-muted)">
                    <?= $sensor['last_seen_at'] ? Helper::timeAgo($sensor['last_seen_at']) : '—' ?>
                  </td>
                  <td>
                    <?php if ($batteryPct !== null): ?>
                      <div style="display:flex;align-items:center;gap:.4rem">
                        <div style="width:40px;height:6px;background:rgba(255,255,255,.08);border-radius:3px;overflow:hidden">
                          <div style="width:<?= $batteryPct ?>%;height:100%;background:<?= $batteryColor ?>;border-radius:3px;transition:.5s"></div>
                        </div>
                        <span style="font-size:.72rem;color:<?= $batteryColor ?>"><?= $batteryPct ?>%</span>
                      </div>
                    <?php else: ?>
                      <span style="color:var(--sati-text-dim)">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display:flex;gap:.3rem">
                      <button class="btn-icon" title="Ver histórico" onclick="viewSensorHistory(<?= $sensor['id'] ?>, '<?= Helper::e($sensor['name']) ?>')">
                        <i class="bi bi-graph-up"></i>
                      </button>
                      <button class="btn-icon" title="Localizar no mapa">
                        <i class="bi bi-geo-alt-fill text-teal"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div><!-- /page-content -->
  </main>
</div>

<!-- Modal Cadastrar Sensor -->
<div class="modal fade" id="modalAddSensor" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--sati-card);border:1px solid var(--sati-border);border-radius:var(--radius)">
      <div class="modal-header" style="border-bottom:1px solid var(--sati-border)">
        <h5 class="modal-title" style="color:var(--sati-text);font-size:1rem"><i class="bi bi-cpu text-teal me-2"></i>Cadastrar Novo Sensor IoT</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="#" novalidate>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Device EUI (LoRaWAN)</label>
              <input type="text" name="eui" class="form-control" placeholder="A1B2C3D4E5F60001" required maxlength="16" pattern="[A-Fa-f0-9]{16}">
              <div style="font-size:.7rem;color:var(--sati-text-muted);margin-top:.25rem">16 caracteres hexadecimais do DevEUI do sensor</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nome do Sensor</label>
              <input type="text" name="name" class="form-control" placeholder="Ex: Sensor Igarapé Zona Norte">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tipo de Sensor</label>
              <select name="sensor_type_id" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach ($sensorTypes as $st): ?>
                  <option value="<?= $st['id'] ?>"><?= Helper::e($st['name']) ?> (<?= $st['unit'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Região</label>
              <select name="region_id" class="form-select" required>
                <?php foreach ($regions as $r): ?>
                  <option value="<?= $r['id'] ?>"><?= Helper::e($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Latitude</label>
              <input type="number" name="latitude" class="form-control" step="0.0000001" placeholder="-3.1190" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Longitude</label>
              <input type="number" name="longitude" class="form-control" step="0.0000001" placeholder="-60.0217" required>
            </div>
            <div class="col-12">
              <label class="form-label">Descrição da Localização</label>
              <input type="text" name="location_desc" class="form-control" placeholder="Ex: Margem direita Igarapé do Mindu, próx. Av. Autaz Mirim">
            </div>
            <div class="col-md-4">
              <label class="form-label">Data de Instalação</label>
              <input type="date" name="installed_at" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
          </div>
          <div class="mt-4">
            <button type="submit" class="btn-sati-primary w-100 justify-content-center py-2">
              <i class="bi bi-plus-circle-fill"></i> Cadastrar Sensor
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Histórico do Sensor -->
<div class="modal fade" id="modalSensorHistory" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="background:var(--sati-card);border:1px solid var(--sati-border);border-radius:var(--radius)">
      <div class="modal-header" style="border-bottom:1px solid var(--sati-border)">
        <h5 class="modal-title" style="color:var(--sati-text);font-size:1rem" id="history-modal-title"><i class="bi bi-graph-up text-teal me-2"></i>Histórico de Leituras</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div style="height:280px"><canvas id="chart-sensor-history"></canvas></div>
      </div>
    </div>
  </div>
</div>

<div id="toast-stack" class="toast-stack"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= Helper::asset('js/app.js') ?>"></script>
<script src="<?= Helper::asset('js/charts.js') ?>"></script>
<script>
// Filtro de busca da tabela de sensores
const searchInput   = document.getElementById('sensor-search');
const filterType    = document.getElementById('filter-type');
const filterStatus  = document.getElementById('filter-status');
const sensorCount   = document.getElementById('sensor-count');

function filterTable() {
  const q      = searchInput.value.toLowerCase();
  const type   = filterType.value;
  const status = filterStatus.value;
  let visible  = 0;

  document.querySelectorAll('.sensor-row').forEach(row => {
    const text   = row.textContent.toLowerCase();
    const rowType = row.dataset.type;
    const rowStat = row.dataset.status;
    const match  = (!q || text.includes(q)) && (!type || rowType === type) && (!status || rowStat === status);
    row.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  sensorCount.textContent = `${visible} sensores`;
}

searchInput.addEventListener('input', filterTable);
filterType.addEventListener('change', filterTable);
filterStatus.addEventListener('change', filterTable);

// Modal histórico do sensor
function viewSensorHistory(sensorId, name) {
  document.getElementById('history-modal-title').innerHTML =
    `<i class="bi bi-graph-up text-teal me-2"></i>Histórico — ${name}`;

  new bootstrap.Modal(document.getElementById('modalSensorHistory')).show();

  // Cria gráfico vazio enquanto carrega
  SatiCharts.timeSeriesLine('chart-sensor-history', [], [], name, '#00b4d8');
  SatiCharts.updateTimeSeries('chart-sensor-history', sensorId, 24);
}
</script>
</body>
</html>
