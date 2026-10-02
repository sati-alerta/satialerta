<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Configuração do Banco de Dados — SATI-Alerta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap');
    :root {
      --bg: #080f1a;
      --card-bg: #0f2040;
      --border: rgba(0, 180, 216, 0.2);
      --accent: #00b4d8;
      --danger: #ef233c;
      --warning: #ffb703;
      --text: #e2eaf4;
      --muted: #7a90aa;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    .container {
      max-width: 680px;
      width: 100%;
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    }
    .header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .header-icon {
      font-size: 2.5rem;
      background: rgba(239, 35, 60, 0.15);
      border: 1px solid rgba(239, 35, 60, 0.3);
      border-radius: 12px;
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--danger);
    }
    h1 {
      margin: 0;
      font-size: 1.4rem;
      color: #fff;
    }
    p.lead {
      color: var(--muted);
      margin: 0.25rem 0 0;
      font-size: 0.9rem;
    }
    .error-box {
      background: rgba(239, 35, 60, 0.1);
      border-left: 4px solid var(--danger);
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      font-size: 0.88rem;
    }
    .error-box strong { color: var(--danger); display: block; margin-bottom: 0.25rem; }
    .error-box code { color: #fca311; word-break: break-all; }
    .config-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1.5rem;
      font-size: 0.85rem;
      background: rgba(0,0,0,0.2);
      border-radius: 8px;
      overflow: hidden;
    }
    .config-table th, .config-table td {
      padding: 0.6rem 0.8rem;
      text-align: left;
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .config-table th { color: var(--muted); font-weight: 500; width: 35%; }
    .config-table td { color: #fff; font-family: monospace; }
    .steps-card {
      background: rgba(0, 180, 216, 0.05);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.25rem;
      margin-bottom: 1.5rem;
    }
    .steps-card h2 {
      margin: 0 0 1rem;
      font-size: 1rem;
      color: var(--accent);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .step {
      display: flex;
      gap: 0.75rem;
      margin-bottom: 0.85rem;
      font-size: 0.86rem;
      line-height: 1.4;
    }
    .step:last-child { margin-bottom: 0; }
    .step-num {
      background: var(--accent);
      color: #000;
      font-weight: 700;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      flex-shrink: 0;
    }
    .actions {
      display: flex;
      gap: 0.75rem;
      justify-content: flex-end;
    }
    button, a.btn {
      padding: 0.65rem 1.25rem;
      border-radius: 8px;
      font-size: 0.88rem;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      cursor: pointer;
      border: none;
      transition: all 0.2s;
    }
    .btn-primary {
      background: linear-gradient(135deg, #0077b6, #00b4d8);
      color: #fff;
    }
    .btn-primary:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="header-icon">
        <i class="bi bi-database-fill-slash"></i>
      </div>
      <div>
        <h1>Configuração do Banco de Dados Pendente</h1>
        <p class="lead">O sistema foi instalado, mas precisa das credenciais do MySQL da hospedagem.</p>
      </div>
    </div>

    <div class="error-box">
      <strong><i class="bi bi-exclamation-triangle-fill"></i> Detalhe do Erro:</strong>
      <code><?= htmlspecialchars($dbError ?? 'Falha ao conectar com o banco de dados.') ?></code>
    </div>

    <?php
      $cfg = class_exists('\Core\Config') ? \Core\Config::get('database') : [];
    ?>
    <table class="config-table">
      <tr>
        <th>Host (DB_HOST)</th>
        <td><?= htmlspecialchars($cfg['host'] ?? 'localhost') ?></td>
      </tr>
      <tr>
        <th>Banco de Dados (DB_NAME)</th>
        <td><?= htmlspecialchars($cfg['name'] ?? 'sati_alerta') ?></td>
      </tr>
      <tr>
        <th>Usuário no .env (DB_USER)</th>
        <td><?= htmlspecialchars($cfg['user'] ?? 'root') ?></td>
      </tr>
      <tr>
        <th>Senha (DB_PASS)</th>
        <td><?= empty($cfg['pass']) ? '<span style="color:#ef233c">[Vazia]</span>' : '<span style="color:#4cc9f0">[Definida]</span>' ?></td>
      </tr>
    </table>

    <div class="steps-card">
      <h2><i class="bi bi-tools"></i> Como Resolver (Passo a Passo):</h2>
      <div class="step">
        <div class="step-num">1</div>
        <div>No painel da sua hospedagem ou servidor, certifique-se de que o banco de dados e o usuário MySQL foram criados.</div>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <div>Certifique-se de vincular o usuário ao banco de dados com todas as permissões necessárias (TODOS OS PRIVILÉGIOS).</div>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <div>No arquivo <code>.env</code> na raiz do projeto, preencha corretamente <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code> e <code>DB_PASS</code>.</div>
      </div>
      <div class="step">
        <div class="step-num">4</div>
        <div>No gerenciador de banco de dados (ex: phpMyAdmin), certifique-se de ter importado as tabelas do projeto (arquivos SQL em <code>database/</code>).</div>
      </div>
    </div>

    <div class="actions">
      <button onclick="window.location.reload()" class="btn-primary">
        <i class="bi bi-arrow-clockwise"></i> Testar Conexão Novamente
      </button>
    </div>
  </div>
</body>
</html>
