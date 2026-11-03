<div class="card mb-20">
  <div class="card-header"><div class="card-title">Natureza e fonte dos dados</div></div>
  <div class="form-row cols-2">
    <div class="form-group"><label class="form-label" for="naturezaDados">Natureza dos dados <span class="required">*</span></label>
      <select id="naturezaDados" name="natureza_dados" class="form-control" required>
        <?php foreach (OcorrenciaService::NATUREZAS as $valor=>$rotulo): ?><option value="<?= $valor ?>" <?= ($dados['natureza_dados'] ?? 'por_validar') === $valor ? 'selected' : '' ?>><?= $rotulo ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label class="form-label" for="fonteInformacao">Fonte da informação / forma de confirmação</label>
      <input id="fonteInformacao" name="fonte_informacao" class="form-control" maxlength="500" value="<?= e($dados['fonte_informacao'] ?? '') ?>" placeholder="Declaração, documento ou endereço da fonte pública">
    </div>
  </div>
  <p class="text-muted" style="font-size:12px;margin:0;">Use «Por validar» enquanto a informação não estiver confirmada. Identifique sempre os campos simulados nas observações. A classificação e a fonte ficam no histórico das alterações.</p>
</div>
