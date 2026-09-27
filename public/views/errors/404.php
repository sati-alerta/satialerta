<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>404 — Página não encontrada · SATI-Alerta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Syne:wght@800&display=swap');
    body { margin:0; background:#080f1a; color:#e2eaf4; font-family:'Inter',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; text-align:center; }
    h1 { font-family:'Syne',sans-serif; font-size:5rem; color:#00b4d8; margin:0; line-height:1; }
    p { color:#7a90aa; margin:.5rem 0 2rem; }
    a { background:linear-gradient(135deg,#0077b6,#00b4d8); color:#fff; padding:.6rem 1.5rem; border-radius:8px; text-decoration:none; font-weight:600; font-size:.9rem; }
  </style>
</head>
<body>
  <div>
    <div style="font-size:4rem;margin-bottom:1rem">🌊</div>
    <h1>404</h1>
    <p style="font-size:1.1rem;color:#e2eaf4;margin-top:.5rem">Página não encontrada</p>
    <p>A página que você procura não existe ou foi movida.</p>
    <a href="<?= defined('ROOT_PATH') ? \Core\Helper::url('') : '/appSati-alerta/public/' ?>">
      <i class="bi bi-house-fill"></i> Voltar ao Início
    </a>
  </div>
</body>
</html>
