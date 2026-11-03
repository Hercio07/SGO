<?php // Duas áreas de assinatura com linhas proporcionais e espaço entre elas. ?>
<table class="doc-signatures"><tr><td class="signature-space"></td>
<?php foreach($assinaturasDocumento as $assinatura): ?>
<td class="signature-entry">
  <?php if(!empty($assinatura['nome'])): ?><strong><?= e($assinatura['nome']) ?></strong><br><?php endif; ?>
  <span class="doc-note"><?= e($assinatura['legenda']) ?></span>
  <?php if(!empty($assinatura['detalhe'])): ?><br><span class="doc-note"><?= e($assinatura['detalhe']) ?></span><?php endif; ?>
</td><td class="signature-space"></td>
<?php endforeach; ?></tr></table>
