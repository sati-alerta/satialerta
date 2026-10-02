<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>500 — Erro Interno do Servidor · SATI-Alerta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Syne:wght@800&display=swap');
    body { margin:0; background:#080f1a; color:#e2eaf4; font-family:'Inter',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; text-align:center; padding:1.5rem; }
    h1 { font-family:'Syne',sans-serif; font-size:4.5rem; color:#ef233c; margin:0; line-height:1; }
    p { color:#7a90aa; margin:.5rem 0 1.5rem; }
    .box { max-width:600px; background:#0f2040; border:1px solid rgba(239,35,60,.2); border-radius:16px; padding:2.5rem 2rem; }
    a, button { background:linear-gradient(135deg,#0077b6,#00b4d8); color:#fff; padding:.6rem 1.5rem; border-radius:8px; text-decoration:none; font-weight:600; font-size:.9rem; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:.5rem; }
    pre { background:rgba(0,0,0,0.4); text-align:left; padding:1rem; border-radius:8px; font-size:0.8rem; color:#fca311; overflow-x:auto; margin-bottom:1.5rem; }
  </style>
</head>
<body>
  <div class="box">
    <div style="font-size:3.5rem;margin-bottom:1rem">⚠️</div>
    <h1>500</h1>
    <p style="font-size:1.1rem;color:#e2eaf4;margin-top:.5rem">Ocorreu um erro interno no servidor</p>
    <?php if (!empty($errorMessage)): ?>
      <pre><?= htmlspecialchars($errorMessage) ?></pre>
    <?php else: ?>
      <p>Nossa equipe foi notificada e está trabalhando na resolução.</p>
    <?php endif; ?>
    <button onclick="window.location.reload()">
      <i class="bi bi-arrow-clockwise"></i> Tentar Novamente
    </button>
  </div>
</body>
</html>
