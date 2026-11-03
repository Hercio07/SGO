<?php
/** @var string $msg */
/** @var string $tipo */
?>
<!DOCTYPE html>
<html lang="pt" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Recuperar Senha — <?= APP_NAME ?></title>
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

    <h1 class="login-title">Recuperar Senha</h1>
    <p class="login-subtitle">Introduza o email da sua conta para receber o link de recuperação.</p>

    <?php if (!empty($msg)): ?>
    <?php
      $alertStyle = $tipo === 'sucesso'
          ? 'background:rgba(34,197,94,0.1);border-color:rgba(34,197,94,0.4);color:#16a34a;'
          : ($tipo === 'aviso'
              ? 'background:rgba(245,158,11,0.1);border-color:rgba(245,158,11,0.4);color:#b45309;'
              : '');
    ?>
    <div class="login-alert" style="<?= $alertStyle ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <?php if ($tipo === 'sucesso'): ?>
        <polyline points="20 6 9 17 4 12"/>
        <?php elseif ($tipo === 'aviso'): ?>
        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        <?php else: ?>
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        <?php endif; ?>
      </svg>
      <?= e($msg) ?>
    </div>
    <?php endif; ?>

    <?php if (empty($msg) || $tipo === 'erro' || $tipo === 'aviso'): ?>
    <form method="POST" action="<?= BASE_URL ?>/recuperar-senha">
      <?= campoCsrf() ?>
      <div class="login-form-group">
        <label class="login-label" for="email">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
          Email
        </label>
        <div class="login-input-wrap">
          <svg class="login-input-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
          <input type="email" id="email" name="email" class="login-input"
            placeholder="utilizador@exemplo.com" autocomplete="email" inputmode="email"
            autocapitalize="none" spellcheck="false" maxlength="150" required
            value="<?= e($_POST['email'] ?? '') ?>">
        </div>
      </div>

      <button type="submit" class="login-btn">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
        </svg>
        Enviar Link de Recuperação
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
      <div class="login-right-heading">Recuperação segura de acesso.</div>
      <div class="login-right-text">
        Se existir um e-mail associado à sua conta, receberá um link para redefinir a senha e voltar a aceder ao <?= APP_NAME ?> em segurança.
      </div>
    </div>
  </div>

</div>

<div class="login-footer"><?= APP_NAME ?> — <?= ENTITY_NAME ?> · Sistema de uso exclusivo e restrito</div>

<script>
document.getElementById('email') && document.getElementById('email').focus();
</script>
</body>
</html>
