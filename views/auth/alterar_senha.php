<!DOCTYPE html>
<html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Definir senha pessoal — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700;900&display=swap">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/login.css?v=<?= filemtime(ROOT . '/public/assets/css/login.css') ?>">
</head><body>
<div class="login-bg"></div><div class="login-bg-overlay"></div>
<div class="login-wrapper"><div class="login-left">
  <div class="login-brand-row"><img src="<?= BASE_URL ?>/public/assets/img/logo.png" alt="PRM"><span class="login-brand-name"><?= APP_NAME ?></span></div>
  <h1 class="login-title">Defina a sua senha pessoal</h1>
  <p class="login-subtitle"><?= e($utilizador['nome']) ?>, altere a senha inicial antes de utilizar o sistema. Use pelo menos 6 caracteres. Pode escolher uma frase fácil de recordar e difícil de adivinhar.</p>
  <?php if ($erro): ?><div class="login-alert" role="alert"><?= e($erro) ?></div><?php endif; ?>
  <form method="POST" action="<?= BASE_URL ?>/alterar-senha" data-vld-form>
    <?= campoCsrf() ?>
    <div class="login-form-group"><label class="login-label" for="senha_atual">Senha actual</label><input class="login-input" id="senha_atual" type="password" name="senha_atual" required autocomplete="current-password"></div>
    <div class="login-form-group"><label class="login-label" for="senha_nova">Nova senha pessoal</label><input class="login-input" id="senha_nova" type="password" name="senha_nova" required minlength="<?= SenhaService::MINIMO ?>" maxlength="72" autocomplete="new-password" data-validate="password"></div>
    <div class="login-form-group"><label class="login-label" for="senha_confirmacao">Confirmar nova senha</label><input class="login-input" id="senha_confirmacao" type="password" name="senha_confirmacao" required autocomplete="new-password" data-validate="password-confirm" data-match-field="senha_nova"></div>
    <button class="login-btn" type="submit">Guardar senha e continuar</button>
  </form>
  <form method="POST" action="<?= BASE_URL ?>/logout" style="margin-top:18px;"><?= campoCsrf() ?><button class="login-btn" type="submit">Terminar sessão</button></form>
</div><div class="login-right"><div class="login-right-content"><div class="login-right-heading">Um acesso pessoal para cada colaborador.</div><div class="login-right-text">A sua senha identifica o seu acesso. As alterações de senha terminam as outras sessões da conta.</div></div></div></div>
<script src="<?= BASE_URL ?>/public/assets/js/validate.js?v=<?= filemtime(ROOT.'/public/assets/js/validate.js') ?>"></script>
<?php require ROOT.'/views/partials/sessao.php'; ?>
</body></html>
