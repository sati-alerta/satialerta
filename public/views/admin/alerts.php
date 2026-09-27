<?php
/**
 * Painel Admin — Gerenciamento de Alertas
 * RBAC: admin_gov
 */
use Core\Database;
use Core\Helper;
use Core\Auth;

$user = Auth::user();

// Alertas ativos e histórico
$alerts = Database::fetchAll(
    "SELECT a.id, a.title, a.message, a.trigger_value, a.triggered_at, a.resolved_at,
            a.is_active, a.notified_sms, a.notified_push,
            al.code AS level_code, al.label AS level_label, al.color_hex,
            r.name AS region_name,
            s.name AS sensor_name, st.unit,
            u.name AS created_by_name
     FROM alerts a
     JOIN alert_levels al ON al.id = a.alert_level_id
     JOIN regions r ON r.id = a.region_id
     LEFT JOIN sensors s ON s.id = a.sensor_id
     LEFT JOIN sensor_types st ON st.id = s.sensor_type_id
     LEFT JOIN users u ON u.id = a.created_by
     ORDER BY a.is_active DESC, a.triggered_at DESC
     LIMIT 100"
);

$activeCount   = count(array_filter($alerts, fn($a) => $a['is_active']));
$resolvedCount = count(array_filter($alerts, fn($a) => !$a['is_active']));
$smsCount      = count(array_filter($alerts, fn($a) => $a['notified_sms']));
$manualCount   = count(array_filter($alerts, fn($a) => $a['created_by_name']));

$alertLevels = Database::fetchAll("SELECT id, code, label, color_hex FROM alert_levels ORDER BY priority");
$regions     = Database::fetchAll("SELECT id, name, state FROM regions ORDER BY state, name");

$baseUrl = Helper::e(Core\Config::get('app')['url']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="base-url" content="<?= $baseUrl ?>">
  <title>Alertas — SATI-Alerta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= Helper::asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div id="sidebar-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1050"></div>
<div class="app-shell">

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
      <a href="<?= Helper::url('admin/sensors') ?>" class="nav-link"><i class="bi bi-cpu-fill"></i> Sensores IoT</a>
      <a href="<?= Helper::url('admin/alerts') ?>" class="nav-link active"><i class="bi bi-bell-fill"></i> Alertas <span class="nav-badge"><?= $activeCount ?></span></a>
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
        <a href="<?= Helper::url('logout') ?>" class="ms-auto btn-icon" style="text-decoration:none"><i class="bi bi-box-arrow-right"></i></a>
      </div>
    </div>
  </aside>

  <main class="main-content">
    <div class="topbar">
      <div>
        <button class="btn-icon me-2 d-md-none" id="sidebar-toggle"><i class="bi bi-list fs-5"></i></button>
        <div class="topbar-title">Central de Alertas</div>
        <div class="topbar-subtitle"><i class="bi bi-clock"></i> <?= date('d/m/Y H:i') ?></div>
      </div>
      <div class="topbar-actions">
        <button class="btn-sati-danger" data-bs-toggle="modal" data-bs-target="#modalManualAlert">
          <i class="bi bi-megaphone-fill"></i> <span class="d-none d-sm-inline">Disparar Alerta</span>
        </button>
      </div>
    </div>

    <div class="page-content">

      <div class="kpi-grid mb-4" style="grid-template-columns:repeat(auto-fit,minmax(155px,1fr))">
        <div class="kpi-card accent-danger fade-in-up">
          <div class="kpi-icon" style="--accent-color:var(--sati-danger)"><i class="bi bi-exclamation-octagon-fill"></i></div>
          <div class="kpi-value text-danger-sati"><?= $activeCount ?></div>
          <div class="kpi-label">Alertas Ativos</div>
        </div>
        <div class="kpi-card accent-green fade-in-up">
          <div class="kpi-icon" style="--accent-color:var(--sati-green)"><i class="bi bi-check-circle-fill"></i></div>
          <div class="kpi-value"><?= $resolvedCount ?></div>
          <div class="kpi-label">Resolvidos</div>
        </div>
        <div class="kpi-card accent-teal fade-in-up">
          <div class="kpi-icon"><i class="bi bi-phone-fill"></i></div>
          <div class="kpi-value"><?= $smsCount ?></div>
          <div class="kpi-label">SMS Enviados</div>
        </div>
        <div class="kpi-card accent-warning fade-in-up">
          <div class="kpi-icon" style="--accent-color:var(--sati-warning)"><i class="bi bi-person-fill"></i></div>
          <div class="kpi-value"><?= $manualCount ?></div>
          <div class="kpi-label">Manuais</div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="panel mb-3">
        <div class="panel-body py-2">
          <div class="row g-2 align-items-center">
            <div class="col-md-3">
              <select id="filter-level" class="form-select">
                <option value="">Todos os níveis</option>
                <?php foreach ($alertLevels as $al): ?>
                  <option value="<?= $al['code'] ?>"><?= Helper::e($al['label']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <select id="filter-status-alert" class="form-select">
                <option value="">Todos</option>
                <option value="1">Ativos</option>
                <option value="0">Resolvidos</option>
              </select>
            </div>
            <div class="col-md-4">
              <input type="text" id="alert-search" class="form-control" placeholder="Buscar por título ou região...">
            </div>
          </div>
        </div>
      </div>

      <!-- Tabela de alertas -->
      <div class="panel">
        <div class="panel-body p-0">
          <div style="overflow-x:auto">
            <table class="sati-table" id="alerts-table">
              <thead>
                <tr>
                  <th>Nível</th>
                  <th>Título</th>
                  <th>Região / Sensor</th>
                  <th>Valor Gatilho</th>
                  <th>Disparado em</th>
                  <th>Notificações</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($alerts as $a): ?>
                <tr class="alert-row"
                    data-level="<?= Helper::e($a['level_code']) ?>"
                    data-active="<?= $a['is_active'] ? '1' : '0' ?>">
                  <td>
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= Helper::e($a['color_hex']) ?>;box-shadow:0 0 6px <?= Helper::e($a['color_hex']) ?>;margin-right:6px;vertical-align:middle"></span>
                    <span style="font-size:.78rem;font-weight:700;color:<?= Helper::e($a['color_hex']) ?>"><?= Helper::e($a['level_label']) ?></span>
                  </td>
                  <td>
                    <div style="font-weight:600;font-size:.83rem;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= Helper::e($a['title']) ?></div>
                    <?php if ($a['created_by_name']): ?>
                      <div style="font-size:.7rem;color:var(--sati-text-muted)"><i class="bi bi-person"></i> Manual — <?= Helper::e($a['created_by_name']) ?></div>
                    <?php else: ?>
                      <div style="font-size:.7rem;color:var(--sati-text-muted)"><i class="bi bi-robot"></i> Automático</div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="font-size:.82rem"><?= Helper::e($a['region_name']) ?></div>
                    <?php if ($a['sensor_name']): ?>
                      <div style="font-size:.7rem;color:var(--sati-text-muted)"><?= Helper::e($a['sensor_name']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="font-family:monospace;font-size:.85rem">
                    <?= $a['trigger_value'] !== null ? round((float)$a['trigger_value'], 1) . ' ' . Helper::e($a['unit'] ?? '') : '—' ?>
                  </td>
                  <td style="font-size:.78rem;color:var(--sati-text-muted)">
                    <?= Helper::formatDate($a['triggered_at']) ?><br>
                    <span style="font-size:.7rem"><?= Helper::timeAgo($a['triggered_at']) ?></span>
                  </td>
                  <td>
                    <div style="display:flex;gap:.4rem">
                      <span title="SMS" style="font-size:.75rem;padding:.15rem .4rem;border-radius:4px;background:<?= $a['notified_sms'] ? 'rgba(6,214,160,.15)' : 'rgba(122,144,170,.1)' ?>;color:<?= $a['notified_sms'] ? 'var(--sati-green)' : 'var(--sati-text-dim)' ?>">
                        <i class="bi bi-phone-fill"></i> SMS
                      </span>
                      <span title="Push" style="font-size:.75rem;padding:.15rem .4rem;border-radius:4px;background:<?= $a['notified_push'] ? 'rgba(0,180,216,.15)' : 'rgba(122,144,170,.1)' ?>;color:<?= $a['notified_push'] ? 'var(--sati-teal)' : 'var(--sati-text-dim)' ?>">
                        <i class="bi bi-bell-fill"></i> Push
                      </span>
                    </div>
                  </td>
                  <td>
                    <?php if ($a['is_active']): ?>
                      <span class="risk-badge critical"><i class="bi bi-circle-fill"></i> Ativo</span>
                    <?php else: ?>
                      <span class="risk-badge minimal"><i class="bi bi-check-circle-fill"></i> Resolvido</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display:flex;gap:.3rem">
                      <button class="btn-icon" title="Ver mensagem completa" onclick="showAlertMsg('<?= htmlspecialchars($a['message'], ENT_QUOTES) ?>')">
                        <i class="bi bi-eye"></i>
                      </button>
                      <?php if ($a['is_active']): ?>
                        <button class="btn-icon" title="Resolver alerta" onclick="resolveAlert(<?= $a['id'] ?>, this)" style="color:var(--sati-green)">
                          <i class="bi bi-check-circle"></i>
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Modal Alerta Manual -->
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
              <option value="">Selecione...</option>
              <?php foreach ($regions as $r): ?>
                <option value="<?= $r['id'] ?>"><?= Helper::e($r['name']) ?> — <?= $r['state'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nível do Alerta</label>
            <select name="alert_level_id" class="form-select" required>
              <option value="">Selecione...</option>
              <?php foreach ($alertLevels as $al): ?>
                <option value="<?= $al['id'] ?>"><?= Helper::e($al['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Título</label>
            <input type="text" name="title" class="form-control" required maxlength="255" placeholder="Ex: Risco de Alagamento — Igarapé do Mindu">
          </div>
          <div class="mb-3">
            <label class="form-label">Mensagem (SMS / Push)</label>
            <textarea name="message" class="form-control" rows="3" required placeholder="Mensagem enviada para SMS e notificação push..."></textarea>
          </div>
          <button type="submit" class="btn-sati-danger w-100 justify-content-center py-2">
            <i class="bi bi-send-fill"></i> Disparar Alerta
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Mensagem Completa -->
<div class="modal fade" id="modalAlertMsg" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--sati-card);border:1px solid var(--sati-border);border-radius:var(--radius)">
      <div class="modal-header" style="border-bottom:1px solid var(--sati-border)">
        <h5 class="modal-title" style="color:var(--sati-text);font-size:.95rem"><i class="bi bi-envelope-fill text-teal me-2"></i>Mensagem do Alerta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="alert-msg-content" style="color:var(--sati-text);background:rgba(0,0,0,.2);border-radius:8px;padding:1rem;font-size:.88rem;line-height:1.7;border-left:3px solid var(--sati-teal)"></div>
      </div>
    </div>
  </div>
</div>

<div id="toast-stack" class="toast-stack"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= Helper::asset('js/app.js') ?>"></script>
<script>
// Filtros da tabela
const alertSearch = document.getElementById('alert-search');
const filterLevel = document.getElementById('filter-level');
const filterStatusAlert = document.getElementById('filter-status-alert');

function filterAlerts() {
  const q      = alertSearch.value.toLowerCase();
  const level  = filterLevel.value;
  const status = filterStatusAlert.value;

  document.querySelectorAll('.alert-row').forEach(row => {
    const text      = row.textContent.toLowerCase();
    const rowLevel  = row.dataset.level;
    const rowActive = row.dataset.active;
    const match = (!q || text.includes(q)) && (!level || rowLevel === level) && (!status || rowActive === status);
    row.style.display = match ? '' : 'none';
  });
}

alertSearch.addEventListener('input', filterAlerts);
filterLevel.addEventListener('change', filterAlerts);
filterStatusAlert.addEventListener('change', filterAlerts);

// Mostrar mensagem completa
function showAlertMsg(msg) {
  document.getElementById('alert-msg-content').textContent = msg;
  new bootstrap.Modal(document.getElementById('modalAlertMsg')).show();
}

// Resolver alerta
async function resolveAlert(id, btn) {
  if (!confirm('Confirma a resolução deste alerta?')) return;
  btn.disabled = true;

  try {
    const res = await fetch(`${SATI.BASE_URL}/api/v1/alerts`, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({action:'resolve', alert_id: id})
    });
    SATI.showToast('Alerta marcado como resolvido.', 'success');
    setTimeout(() => location.reload(), 1500);
  } catch(e) {
    SATI.showToast('Erro ao resolver alerta.', 'danger');
    btn.disabled = false;
  }
}
</script>
</body>
</html>
