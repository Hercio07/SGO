<select name="natureza_dados" class="filter-input" style="min-width:180px;max-width:260px;" aria-label="Natureza dos dados">
  <option value="">Todas as naturezas</option>
  <?php foreach (OcorrenciaService::NATUREZAS as $valor=>$rotulo): ?><option value="<?= $valor ?>" <?= ($filtros['natureza_dados'] ?? '') === $valor ? 'selected' : '' ?>><?= $rotulo ?></option><?php endforeach; ?>
</select>
