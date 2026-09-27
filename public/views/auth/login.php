<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="base-url" content="<?= Core\Helper::e(Core\Config::get('app')['url']) ?>">
  <title>Login — SATI-Alerta</title>
  <meta name="description" content="Acesse a Plataforma SATI-Alerta de Monitoramento Climático e Alertas de Desastres.">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- SATI CSS -->
  <link href="<?= Core\Helper::asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>

<div class="login-page">
  <!-- Fundo animado -->
  <div class="login-bg"></div>
  <div class="login-particles"></div>

  <!-- SVG Wave decoration -->
  <svg class="wave-decoration" viewBox="0 0 1440 200" xmlns="http://www.w3.org/2000/svg">
    <path fill="#00b4d8" fill-opacity="1"
      d="M0,64 C360,160 1080,0 1440,96 L1440,200 L0,200 Z"/>
  </svg>

  <!-- Painel de Login -->
  <div class="login-panel fade-in-up">
    <!-- Logo -->
    <div class="login-logo">
      <div class="logo-icon">
        <i class="bi bi-broadcast-pin" style="color:#fff;font-size:1.5rem"></i>
      </div>
      <div>
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.3rem;color:#fff;letter-spacing:.02em">
          SATI<span style="color:var(--sati-teal)">-Alerta</span>
        </div>
        <div style="font-size:.7rem;color:var(--sati-text-muted);text-transform:uppercase;letter-spacing:.1em">
          Plataforma de Monitoramento Climático
        </div>
      </div>
    </div>

    <h1 style="font-size:1.4rem;font-weight:700;margin-bottom:.25rem">Acesso à Plataforma</h1>
    <p style="font-size:.83rem;color:var(--sati-text-muted);margin-bottom:1.75rem">
      Entre com suas credenciais para acessar o painel correspondente ao seu perfil.
    </p>

    <?php if ($error = Core\Session::getFlash('error')): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3" style="background:rgba(239,35,60,.12);border-color:rgba(239,35,60,.3);border-radius:8px;color:#ef233c;font-size:.83rem;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= Core\Helper::e($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= Core\Helper::url('login') ?>" id="login-form" novalidate>
      <input type="hidden" name="_token" value="<?= Core\Session::token() ?>">

      <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <div style="position:relative">
          <i class="bi bi-envelope" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sati-text-muted);pointer-events:none"></i>
          <input type="email" id="email" name="email" class="form-control ps-5"
            placeholder="seu@email.gov.br" required autocomplete="email"
            value="<?= Core\Helper::e($_POST['email'] ?? '') ?>">
        </div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Senha</label>
        <div style="position:relative">
          <i class="bi bi-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sati-text-muted);pointer-events:none"></i>
          <input type="password" id="password" name="password" class="form-control ps-5"
            placeholder="••••••••" required autocomplete="current-password">
          <button type="button" id="toggle-pw" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--sati-text-muted);cursor:pointer;padding:0">
            <i class="bi bi-eye" id="pw-icon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-sati-primary w-100 py-2 justify-content-center" style="font-size:.95rem">
        <i class="bi bi-box-arrow-in-right"></i>
        Entrar na Plataforma
      </button>
    </form>

    <!-- Perfis de acesso -->
    <div class="mt-4 pt-3" style="border-top:1px solid var(--sati-border)">
      <p style="font-size:.72rem;color:var(--sati-text-muted);text-align:center;margin-bottom:.75rem;text-transform:uppercase;letter-spacing:.08em">Perfis disponíveis</p>
      <div class="d-flex gap-2 justify-content-center flex-wrap">
        <span style="background:rgba(0,180,216,.08);border:1px solid rgba(0,180,216,.2);border-radius:50px;padding:.2rem .7rem;font-size:.7rem;color:var(--sati-teal)">
          <i class="bi bi-building"></i> Governamental
        </span>
        <span style="background:rgba(6,214,160,.08);border:1px solid rgba(6,214,160,.2);border-radius:50px;padding:.2rem .7rem;font-size:.7rem;color:var(--sati-green)">
          <i class="bi bi-tree"></i> Produtor Rural
        </span>
        <span style="background:rgba(122,144,170,.08);border:1px solid rgba(122,144,170,.2);border-radius:50px;padding:.2rem .7rem;font-size:.7rem;color:var(--sati-text-muted)">
          <i class="bi bi-people"></i> Comunidade
        </span>
      </div>
    </div>

    <!-- Link para interface pública -->
    <div class="text-center mt-3">
      <a href="<?= Core\Helper::url('public') ?>" style="font-size:.78rem;color:var(--sati-text-muted)">
        <i class="bi bi-globe"></i> Acessar Plataforma Pública sem login
      </a>
    </div>

    <!-- ODS Footer -->
    <div class="text-center mt-4 pt-3" style="border-top:1px solid var(--sati-border)">
      <p style="font-size:.65rem;color:var(--sati-text-dim)">
        Alinhado com &nbsp;
        <span style="color:var(--sati-teal);font-weight:600">ODS 3 · ODS 11 · ODS 13 · ODS 17</span>
        &nbsp;— ONU 2030
      </p>
    </div>
  </div>
</div>

<script>
  // Toggle show/hide senha
  document.getElementById('toggle-pw')?.addEventListener('click', function() {
    const inp  = document.getElementById('password');
    const icon = document.getElementById('pw-icon');
    if (inp.type === 'password') {
      inp.type   = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      inp.type   = 'password';
      icon.className = 'bi bi-eye';
    }
  });

  // Loading state no submit
  document.getElementById('login-form')?.addEventListener('submit', function(e) {
    const btn = this.querySelector('[type=submit]');
    if (this.checkValidity()) {
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Entrando...';
      btn.disabled  = true;
    }
  });
</script>
</body>
</html>
