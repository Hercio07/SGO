<?php
/** @var array|null $user */
/** @var string $msg */
/** @var string $tipo */
/** @var string $token */
?>
<!DOCTYPE html>
<html lang="pt" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Redefinir Senha — <?= APP_NAME ?></title>
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

    <h1 class="login-title">Redefinir Senha</h1>

      <?php if (!$utilizador): ?>
        <!-- Token inválido ou expirado -->
        <div class="login-alert">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          Link inválido ou expirado. Solicite um novo link de recuperação.
        </div>
        <div style="text-align:center;margin-top:20px;">
          <a href="<?= BASE_URL ?>/recuperar-senha" class="login-btn" style="display:inline-flex;text-decoration:none;width:auto;padding:12px 28px;">
            Solicitar Novo Link
          </a>
        </div>

      <?php else: ?>
        <p class="login-subtitle">A definir nova senha para <strong><?= e($utilizador['nome']) ?></strong></p>

        <?php if (!empty($msg)): ?>
        <div class="login-alert">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <?= e($msg) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/redefinir-senha" data-vld-form>
          <?= campoCsrf() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <div class="login-form-group">
            <label class="login-label" for="senha_nova">Nova Senha</label>
            <div class="login-input-wrap">
              <svg class="login-input-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
              </svg>
              <input type="password" id="senha_nova" name="senha_nova" class="login-input"
                placeholder="••••••••" autocomplete="new-password" required
                data-validate="password" minlength="<?= SenhaService::MINIMO ?>">
              <button type="button" class="password-toggle" id="btnToggleNova" title="Mostrar / ocultar senha">
                <svg id="iconEyeNova" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <svg id="iconEyeOffNova" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;">
                  <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
            <small style="display:block;margin-top:6px;font-size:11px;color:rgba(148,163,184,0.9);">Mínimo de 6 caracteres.</small>
          </div>

          <div class="login-form-group">
            <label class="login-label" for="senha_confirmacao">Confirmar Nova Senha</label>
            <div class="login-input-wrap">
              <svg class="login-input-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
              </svg>
              <input type="password" id="senha_confirmacao" name="senha_confirmacao" class="login-input"
                placeholder="••••••••" autocomplete="new-password" required
                data-validate="password-confirm" data-match-field="senha_nova">
              <button type="button" class="password-toggle" id="btnToggleConf" title="Mostrar / ocultar senha">
                <svg id="iconEyeConf" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <svg id="iconEyeOffConf" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;">
                  <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
          </div>

          <button type="submit" class="login-btn">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            Guardar Nova Senha
          </button>

        </form>
      <?php endif; ?>

    <div class="login-forgot">
      <a href="<?= BASE_URL ?>/login">← Voltar ao Login</a>
    </div>

  </div>

  <!-- ── Painel Direito: Identidade ── -->
  <div class="login-right">
    <div class="login-badge">
      <span class="login-badge-dot"></span>
      <?= COMMAND_NAME ?>
    </div>

    <div class="login-right-content">
      <div class="login-right-heading">Defina uma nova senha segura.</div>
      <div class="login-right-text">
        Use pelo menos 6 caracteres e evite senhas óbvias para proteger o seu acesso ao <?= APP_NAME ?>.
      </div>
    </div>
  </div>

</div>

<div class="login-footer"><?= APP_NAME ?> — <?= ENTITY_NAME ?> · Sistema de uso exclusivo e restrito</div>

<script src="<?= BASE_URL ?>/public/assets/js/validate.js"></script>
<script>
document.getElementById('senha_nova') && document.getElementById('senha_nova').focus();

function makeToggle(btnId, inputId, eyeId, eyeOffId) {
  var btn = document.getElementById(btnId);
  if (!btn) return;
  btn.addEventListener('click', function () {
    var input  = document.getElementById(inputId);
    var eye    = document.getElementById(eyeId);
    var eyeOff = document.getElementById(eyeOffId);
    var show   = input.type === 'password';
    input.type        = show ? 'text' : 'password';
    eye.style.display    = show ? 'none' : '';
    eyeOff.style.display = show ? ''     : 'none';
    input.focus();
  });
}
makeToggle('btnToggleNova', 'senha_nova', 'iconEyeNova', 'iconEyeOffNova');
makeToggle('btnToggleConf', 'senha_confirmacao', 'iconEyeConf', 'iconEyeOffConf');
</script>
</body>
</html>
