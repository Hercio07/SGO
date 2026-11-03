<?php if (!empty($_SESSION['user_id'])):
SessaoService::preparar();
$estadoSessao = SessaoService::estado();
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/sessao.css?v=<?= filemtime(ROOT.'/public/assets/css/sessao.css') ?>">
<div id="sessao-aviso" class="sessao-overlay" role="alertdialog" aria-modal="true" aria-labelledby="sessao-titulo" aria-describedby="sessao-descricao" hidden>
  <div class="sessao-dialogo">
    <div class="sessao-cabecalho">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
      <h2 id="sessao-titulo">Sessão prestes a terminar</h2>
    </div>
    <div class="sessao-corpo">
      <p id="sessao-descricao">A sua sessão será encerrada por inactividade. Deseja continuar?</p>
      <p class="sessao-contagem">Tempo restante: <strong id="sessao-tempo"><?= sprintf('%02d:%02d',intdiv(SESSION_WARNING,60),SESSION_WARNING%60) ?></strong></p>
      <p id="sessao-erro" role="status"></p>
    </div>
    <div class="sessao-acoes">
      <button type="button" id="sessao-continuar"><span aria-hidden="true">✓</span> Continuar sessão</button>
    </div>
  </div>
</div>
<script>
window.SESSAO_CONFIG = <?= json_encode([
    'identificador'=>$_SESSION['sessao_identificador'], 'token'=>gerarTokenCsrf(),
    'atividade'=>(int)$_SESSION['last_activity'], 'restante'=>$estadoSessao['restante'],
    'limite'=>SESSION_TIMEOUT, 'aviso'=>SESSION_WARNING, 'base'=>BASE_URL,
    'login'=>BASE_URL.'/login', 'loginInatividade'=>BASE_URL.'/login?motivo=inatividade',
    'alterarSenha'=>trim($_GET['url'] ?? '','/') === 'alterar-senha',
],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
</script>
<script src="<?= BASE_URL ?>/public/assets/js/sessao.js?v=<?= filemtime(ROOT.'/public/assets/js/sessao.js') ?>"></script>
<?php endif; ?>
