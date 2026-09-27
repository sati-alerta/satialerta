<?php
/**
 * Painel Admin — Relatórios Climáticos
 * RBAC: admin_gov
 */
use Core\Database;
use Core\Helper;
use Core\Auth;

$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regionId = (int)($_POST['region_id'] ?? 0);
    $type     = $_POST['type'] ?? 'general';
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $isPublic = !empty($_POST['is_public']) ? 1 : 0;

    if ($regionId && $title) {
        $summary = mb_substr($content, 0, 180);
        Database::query(
            "INSERT INTO reports (region_id, type, title, summary, content, generated_by, author_id, is_public)
             VALUES (?, ?, ?, ?, ?, 'manual', ?, ?)",
            [$regionId, $type, $title, $summary, $content, $user['id'] ?? null, $isPublic]
        );
        Helper::redirect(Helper::url('admin/reports'));
    }
}

$reports = Database::fetchAll(
    "SELECT rp.id, rp.type, rp.title, rp.summary, rp.file_path, rp.generated_by,
            rp.is_public, rp.views_count, rp.created_at,
            r.name AS region_name, r.state,
            u.name AS author_name
     FROM reports rp
     JOIN regions r ON r.id = rp.region_id
     LEFT JOIN users u ON u.id = rp.author_id
     ORDER BY rp.created_at DESC
     LIMIT 80"
);

$regions = Database::fetchAll("SELECT id, name, state FROM regions ORDER BY state, name");
$baseUrl = Helper::e(Core\Config::get('app')['url']);

$typeLabels = [
    'flood'          => ['🌊 Inundação',      'danger'],
    'drought'        => ['🏜️ Seca',           'warning'],
    'fire'           => ['🔥 Incêndio',       'critical'],
    'infrastructure' => ['🏗️ Infraestrutura', 'teal'],
    'agriculture'    => ['🌱 Agricultura',    'green'],
    'general'        => ['📋 Geral',           'muted'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="base-url" content="<?= $baseUrl ?>">
  <title>Relatórios — SATI-Alerta</title>
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
      <a href="<?= Helper::url('admin/alerts') ?>" class="nav-link"><i class="bi bi-bell-fill"></i> Alertas</a>
      <div class="nav-section-label mt-2">Analytics</div>
      <a href="#" class="nav-link"><i class="bi bi-map-fill"></i> Mapas de Risco</a>
      <a href="<?= Helper::url('admin/reports') ?>" class="nav-link active"><i class="bi bi-file-earmark-bar-graph-fill"></i> Relatórios</a>
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
        <div class="topbar-title">Relatórios Climáticos</div>
        <div class="topbar-subtitle">Geração automática diária + relatórios manuais</div>
      </div>
      <div class="topbar-actions">
        <button class="btn-sati-primary" data-bs-toggle="modal" data-bs-target="#modalNewReport">
          <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">Novo Relatório</span>
        </button>
      </div>
    </div>

    <div class="page-content">

      <!-- Estatísticas rápidas -->
      <div class="kpi-grid mb-4" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
        <?php
        $totalRep   = count($reports);
        $autoRep    = count(array_filter($reports, fn($r) => $r['generated_by'] === 'auto'));
        $publicRep  = count(array_filter($reports, fn($r) => $r['is_public']));
        $totalViews = array_sum(array_column($reports, 'views_count'));
        ?>
        <div class="kpi-card accent-teal fade-in-up">
          <div class="kpi-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
          <div class="kpi-value"><?= $totalRep ?></div>
          <div class="kpi-label">Total Relatórios</div>
        </div>
        <div class="kpi-card accent-green fade-in-up">
          <div class="kpi-icon" style="--accent-color:var(--sati-green)"><i class="bi bi-robot"></i></div>
          <div class="kpi-value"><?= $autoRep ?></div>
          <div class="kpi-label">Automáticos</div>
        </div>
        <div class="kpi-card accent-warning fade-in-up">
          <div class="kpi-icon" style="--accent-color:var(--sati-warning)"><i class="bi bi-globe"></i></div>
          <div class="kpi-value"><?= $publicRep ?></div>
          <div class="kpi-label">Públicos</div>
        </div>
        <div class="kpi-card fade-in-up">
          <div class="kpi-icon"><i class="bi bi-eye-fill"></i></div>
          <div class="kpi-value"><?= number_format($totalViews) ?></div>
          <div class="kpi-label">Visualizações</div>
        </div>
      </div>

      <!-- Filtro -->
      <div class="panel mb-3">
        <div class="panel-body py-2">
          <div class="row g-2">
            <div class="col-md-4">
              <input type="text" id="report-search" class="form-control" placeholder="Buscar relatório...">
            </div>
            <div class="col-md-3">
              <select id="filter-type-report" class="form-select">
                <option value="">Todos os tipos</option>
                <?php foreach (array_keys($typeLabels) as $t): ?>
                  <option value="<?= $t ?>"><?= $typeLabels[$t][0] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <select id="filter-region-report" class="form-select">
                <option value="">Todas as regiões</option>
                <?php foreach ($regions as $r): ?>
                  <option value="<?= Helper::e($r['name']) ?>"><?= Helper::e($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Grid de relatórios como Cards -->
      <div class="row g-3" id="reports-grid">
        <?php foreach ($reports as $i => $rep):
          [$typeLabel, $typeColor] = $typeLabels[$rep['type']] ?? ['Relatório', 'muted'];
        ?>
        <div class="col-lg-4 col-md-6 report-card-col fade-in-up"
             data-type="<?= $rep['type'] ?>"
             data-region="<?= Helper::e($rep['region_name']) ?>"
             data-search="<?= strtolower(Helper::e($rep['title'] . ' ' . $rep['region_name'])) ?>">
          <div class="kpi-card" style="--accent-color:var(--sati-<?= $typeColor === 'muted' ? 'text-muted' : $typeColor ?>);height:100%;display:flex;flex-direction:column">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.75rem">
              <span style="font-size:.78rem;padding:.2rem .6rem;border-radius:20px;background:rgba(255,255,255,.05);color:var(--sati-text-muted)">
                <?= $typeLabel ?>
              </span>
              <?php if ($rep['is_public']): ?>
                <span style="font-size:.7rem;color:var(--sati-teal)"><i class="bi bi-globe"></i> Público</span>
              <?php else: ?>
                <span style="font-size:.7rem;color:var(--sati-text-dim)"><i class="bi bi-lock"></i> Restrito</span>
              <?php endif; ?>
            </div>
            <div style="font-weight:700;font-size:.88rem;margin-bottom:.35rem;flex:1;line-height:1.4">
              <?= Helper::e($rep['title']) ?>
            </div>
            <?php if ($rep['summary']): ?>
              <div style="font-size:.77rem;color:var(--sati-text-muted);margin-bottom:.6rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                <?= Helper::e($rep['summary']) ?>
              </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:.6rem;border-top:1px solid var(--sati-border)">
              <div style="font-size:.72rem;color:var(--sati-text-muted)">
                <i class="bi bi-geo-alt"></i> <?= Helper::e($rep['region_name']) ?><br>
                <i class="bi bi-calendar3"></i> <?= Helper::formatDate($rep['created_at'], 'd/m/Y') ?>
                <span style="margin-left:.4rem;color:var(--sati-text-dim)"><?= $rep['generated_by'] === 'auto' ? '• Auto' : '• Manual' ?></span>
              </div>
              <div style="display:flex;gap:.3rem">
                <button class="btn-icon" title="Visualizar" onclick="viewReport(<?= $rep['id'] ?>)">
                  <i class="bi bi-eye"></i>
                </button>
                <?php if (!empty($rep['file_path'])): ?>
                  <a href="<?= Helper::e($rep['file_path']) ?>" target="_blank" class="btn-icon" title="Baixar PDF">
                    <i class="bi bi-file-pdf text-danger-sati"></i>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($reports)): ?>
          <div class="col-12 text-center py-5">
            <i class="bi bi-file-earmark-bar-graph" style="font-size:3rem;color:var(--sati-text-dim)"></i>
            <p class="text-muted-sati mt-2">Nenhum relatório gerado ainda.<br>Execute o cron job diário ou crie um manualmente.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- Modal Novo Relatório -->
<div class="modal fade" id="modalNewReport" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--sati-card);border:1px solid var(--sati-border);border-radius:var(--radius)">
      <div class="modal-header" style="border-bottom:1px solid var(--sati-border)">
        <h5 class="modal-title" style="color:var(--sati-text);font-size:1rem"><i class="bi bi-file-earmark-plus text-teal me-2"></i>Novo Relatório Manual</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST" novalidate>
          <div class="mb-3">
            <label class="form-label">Região</label>
            <select name="region_id" class="form-select" required>
              <?php foreach ($regions as $r): ?>
                <option value="<?= $r['id'] ?>"><?= Helper::e($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Tipo</label>
            <select name="type" class="form-select">
              <?php foreach ($typeLabels as $k => [$label, $c]): ?>
                <option value="<?= $k ?>"><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Título</label>
            <input type="text" name="title" class="form-control" required placeholder="Ex: Análise de Risco de Cheias — Junho/2025">
          </div>
          <div class="mb-3">
            <label class="form-label">Conteúdo</label>
            <textarea name="content" class="form-control" rows="5" placeholder="Análise e conclusões..."></textarea>
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" name="is_public" id="is_public" class="form-check-input" value="1">
            <label for="is_public" class="form-check-label" style="font-size:.85rem;color:var(--sati-text)">Tornar público (Plataforma de Dados Abertos)</label>
          </div>
          <button type="submit" class="btn-sati-primary w-100 justify-content-center py-2">
            <i class="bi bi-save2-fill"></i> Salvar Relatório
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Visualizar Relatório -->
<div class="modal fade" id="modalViewReport" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="background:var(--sati-card);border:1px solid var(--sati-border);border-radius:var(--radius)">
      <div class="modal-header" style="border-bottom:1px solid var(--sati-border)">
        <h5 class="modal-title" id="report-view-title" style="color:var(--sati-text);font-size:1rem"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="max-height:70vh;overflow-y:auto">
        <pre id="report-view-content" style="background:rgba(0,0,0,.2);border-radius:8px;padding:1.25rem;white-space:pre-wrap;font-size:.82rem;color:var(--sati-text);border-left:3px solid var(--sati-teal);font-family:'Inter',sans-serif;line-height:1.7"></pre>
      </div>
    </div>
  </div>
</div>

<div id="toast-stack" class="toast-stack"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= Helper::asset('js/app.js') ?>"></script>
<script>
// Filtros
const reportSearch        = document.getElementById('report-search');
const filterTypeReport    = document.getElementById('filter-type-report');
const filterRegionReport  = document.getElementById('filter-region-report');

function filterReports() {
  const q      = reportSearch.value.toLowerCase();
  const type   = filterTypeReport.value;
  const region = filterRegionReport.value;

  document.querySelectorAll('.report-card-col').forEach(col => {
    const match = (!q || col.dataset.search?.includes(q))
               && (!type || col.dataset.type === type)
               && (!region || col.dataset.region === region);
    col.style.display = match ? '' : 'none';
  });
}

reportSearch.addEventListener('input', filterReports);
filterTypeReport.addEventListener('change', filterReports);
filterRegionReport.addEventListener('change', filterReports);

// Visualizar relatório (simplificado — sem fetch separado)
function viewReport(id) {
  document.getElementById('report-view-title').textContent = 'Carregando...';
  document.getElementById('report-view-content').textContent = 'Buscando conteúdo...';
  new bootstrap.Modal(document.getElementById('modalViewReport')).show();

  fetch(`${SATI.BASE_URL}/api/v1/reports?id=${id}`)
    .then(r => r.json())
    .then(data => {
      const report = data.reports?.[0];
      if (report) {
        document.getElementById('report-view-title').textContent = report.title;
        document.getElementById('report-view-content').textContent = report.content ?? report.summary ?? '(Sem conteúdo)';
      }
    })
    .catch(() => {
      document.getElementById('report-view-content').textContent = 'Erro ao carregar o relatório.';
    });
}
</script>
</body>
</html>
