<?php if (($ocorrencia['natureza_dados'] ?? 'por_validar') !== 'confirmados'): ?>
<p class="doc-note" style="border-bottom:0.5pt solid #CDD3D8;padding-bottom:2mm;">
  <strong>Natureza dos dados: <?= e(OcorrenciaService::NATUREZAS[$ocorrencia['natureza_dados'] ?? 'por_validar'] ?? 'Por validar') ?>.</strong>
  <?= ($ocorrencia['natureza_dados'] ?? '') === 'simulados' ? 'Este registo contém dados simulados, identificados no seu conteúdo ou nas observações.' : 'A confirmação dos dados ainda não foi assinalada no sistema.' ?>
</p>
<?php endif; ?>
<?php if (!empty($ocorrencia['fonte_informacao'])): ?><p class="doc-note">Fonte da informação: <?= e($ocorrencia['fonte_informacao']) ?></p><?php endif; ?>
