<?php
/** @var string $erro */
?>
<!DOCTYPE html>
<html lang="pt" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Acesso ao Sistema — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700;900&display=swap">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/login.css?v=<?= filemtime(ROOT . '/public/assets/css/login.css') ?>">
<link rel="icon" type="image/png" href="<?= BASE_URL ?>/public/assets/img/logo.png">
</head>
<body>

<div class="login-bg"></div>
<div class="login-bg-overlay"></div>

<div class="login-wrapper">

  <!-- ── Painel Esquerdo: Formulário ── -->
  <div class="login-left">

    <div class="login-brand-row">
      <img src="<?= BASE_URL ?>/public/assets/img/logo.png" alt="PRM">
      <span class="login-brand-name"><?= APP_NAME ?></span>
    </div>

    <h1 class="login-title">Bem-vindo</h1>
    <p class="login-subtitle">Introduza as suas credenciais para aceder ao <?= APP_FULL_NAME ?>.</p>

    <?php if (!empty($_SESSION['toast'])): ?>
    <div class="login-alert" style="background:rgba(245,158,11,0.12);border-color:rgba(245,158,11,0.4);color:#B45309;">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
      <?= e($_SESSION['toast']['msg']) ?>
    </div>
    <?php unset($_SESSION['toast']); endif; ?>

    <?php if (!empty($erro)): ?>
    <div class="login-alert">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <?= e($erro) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/login" autocomplete="on" id="loginForm">
      <?= campoCsrf() ?>
      <div class="login-form-group">
        <label class="login-label" for="email">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
          Email ou nome de utilizador
        </label>
        <div class="login-input-wrap">
          <svg class="login-input-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
          <input type="text" id="email" name="email" class="login-input"
            placeholder="Email ou nome de utilizador" autocomplete="username"
            autocapitalize="none" spellcheck="false" maxlength="150" required
            value="<?= e($_POST['email'] ?? '') ?>">
        </div>
      </div>

      <div class="login-form-group">
        <label class="login-label" for="password">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
          </svg>
          Senha
        </label>
        <div class="login-input-wrap">
          <svg class="login-input-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
          </svg>
          <input type="password" id="password" name="password" class="login-input"
            placeholder="••••••••" autocomplete="current-password" required>
          <button type="button" class="password-toggle" id="btnTogglePwd" title="Mostrar / ocultar senha">
            <svg id="iconEye" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <svg id="iconEyeOff" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;">
              <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="login-btn" id="loginBtn">
        <svg id="loginBtnIcon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
        </svg>
        <span id="loginBtnText">Entrar</span>
      </button>

    </form>

    <div class="login-forgot">
      <a href="<?= BASE_URL ?>/recuperar-senha">Esqueci a senha</a>
    </div>

  </div>

  <!-- ── Painel Direito: Identidade ── -->
  <div class="login-right">
    <div class="login-badge">
      <span class="login-badge-dot"></span>
      <?= COMMAND_NAME ?>
    </div>

    <div class="login-right-content">
      <div class="login-right-heading">Registo de ocorrências, da denúncia à resolução.</div>
      <div class="login-right-text">
        Acompanhe ocorrências em tempo real, gira processos por esquadra e mantenha o controlo total do <?= APP_NAME ?> num único sistema.
      </div>
    </div>
  </div>

</div>

<div class="login-footer"><?= APP_NAME ?> — <?= ENTITY_NAME ?> · Sistema de uso exclusivo e restrito</div>

<script>
document.getElementById('email').focus();

// Toggle mostrar/ocultar senha
document.getElementById('btnTogglePwd').addEventListener('click', function () {
  var input   = document.getElementById('password');
  var iconEye = document.getElementById('iconEye');
  var iconOff = document.getElementById('iconEyeOff');
  var show    = input.type === 'password';
  input.type  = show ? 'text' : 'password';
  iconEye.style.display = show ? 'none' : '';
  iconOff.style.display = show ? ''     : 'none';
  input.focus();
});

// Estado de carregamento ao submeter
document.getElementById('loginForm').addEventListener('submit', function () {
  var btn  = document.getElementById('loginBtn');
  var text = document.getElementById('loginBtnText');
  var icon = document.getElementById('loginBtnIcon');
  btn.disabled       = true;
  btn.style.opacity  = '0.8';
  text.textContent   = 'A verificar...';
  icon.innerHTML     = '<circle cx="12" cy="12" r="10" stroke-dasharray="31.4" stroke-dashoffset="10" style="animation:loginSpin 1s linear infinite;transform-origin:center;"/>';
});
</script>
</body>
</html>
