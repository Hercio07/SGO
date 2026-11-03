<?php $logoDocumento = $logoBase64 ?? (!empty($cabecalhoFormal) ? DocumentoService::emblema() : DocumentoService::logo()); ?>
<?php if (!empty($cabecalhoFormal)): ?>
<div class="doc-formal-header">
  <?php if ($logoDocumento): ?><img src="<?= $logoDocumento ?>" alt="Emblema da República de Moçambique"><?php endif; ?>
  <div class="country">REPÚBLICA DE MOÇAMBIQUE</div>
  <div class="formal-line">MINISTÉRIO DO INTERIOR</div>
  <div class="formal-line">COMANDO DA PRM — CIDADE DE MAPUTO</div>
  <div class="formal-line"><strong><?= e($unidadeDocumento ?? '') ?></strong></div>
</div>
<?php else: ?>
<table class="doc-header" cellpadding="0" cellspacing="0"><tr>
  <?php if ($logoDocumento): ?><td class="doc-logo" style="width:18mm;"><img src="<?= $logoDocumento ?>" width="49" height="49" style="width:13mm;height:13mm;" alt="PRM"></td><?php endif; ?>
  <td><div class="doc-institution"><?= e(ENTITY_NAME) ?></div>
    <div class="doc-command"><?= e(COMMAND_NAME) ?></div>
    <div class="doc-system"><?= e(APP_FULL_NAME) ?> — <?= e(APP_NAME) ?></div>
  </td>
</tr></table>
<?php endif; ?>
