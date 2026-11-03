<div class="doc-footer"><?= e(APP_NAME . ' - ' . COMMAND_NAME) ?>
  <br>Gerado em <?= date('d/m/Y H:i') ?><?= DocumentoService::emitente() ? ' por ' . e(DocumentoService::emitente()) : '' ?>
</div>
