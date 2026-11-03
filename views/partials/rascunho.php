<?php if (in_array($vistaAtual ?? '',['criar','editar'],true)): ?>
<style>#rascunho-painel [hidden] { display:none !important; }</style>
<div id="rascunho-painel" class="card mb-20" style="padding:14px 18px;">
  <p id="rascunho-estado" role="status" style="font-size:12px;margin:0 0 8px;">A consultar o rascunho privado…</p>
  <div class="d-flex gap-8" style="flex-wrap:wrap;">
    <button id="rascunho-recuperar" class="btn btn-secondary btn-sm" type="button" hidden>Recuperar rascunho</button>
    <button id="rascunho-guardar" class="btn btn-secondary btn-sm" type="button">Guardar rascunho agora</button>
    <button id="rascunho-descartar" class="btn btn-ghost btn-sm" type="button" hidden>Descartar rascunho guardado</button>
  </div>
  <p class="text-muted" style="font-size:11px;margin:8px 0 0;">Só a sua conta pode recuperar este rascunho, durante sete dias. Os anexos têm de ser seleccionados novamente. Guardar um rascunho não regista nem actualiza a ocorrência.</p>
</div>
<script>
window.RASCUNHO_CONFIG = <?= json_encode(['contexto'=>$vistaAtual === 'criar' ? 'nova' : 'editar-'.(int)$ocorrencia['id'],'repost'=>!empty($erros)],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
</script>
<script src="<?= BASE_URL ?>/public/assets/js/rascunho.js?v=<?= filemtime(ROOT.'/public/assets/js/rascunho.js') ?>"></script>
<?php endif; ?>
