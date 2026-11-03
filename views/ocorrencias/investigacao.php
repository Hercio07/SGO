<?php
$conclusaoEstado = $ocorrencia['conclusao_estado'] ?? 'sem_proposta';
$podeInvestigar = OcorrenciaService::podeInvestigar($ocorrencia);
$podeAtribuir = OcorrenciaService::podeAtribuir($ocorrencia);
?>
<div class="card mb-16" id="investigacao">
  <div class="card-header"><div class="card-title">Investigação</div></div>
  <?php if ($ocorrencia['validacao_estado'] !== 'aprovada'): ?>
    <p class="text-muted">A investigação fica disponível após a aprovação do supervisor.</p>
  <?php elseif (!empty($ocorrencia['investigador_nome'])): ?>
    <p><strong>Investigador responsável</strong><br><?= e($ocorrencia['investigador_nome']) ?></p>
  <?php else: ?>
    <p class="text-muted">Aguarda atribuição pelo supervisor da esquadra.</p>
  <?php endif; ?>
  <?php if ($podeAtribuir): ?>
    <div class="divider"></div>
    <?php if (empty($investigadoresEsquadra)): ?>
      <p class="text-muted">Não há investigadores activos nesta esquadra.</p>
    <?php else: ?>
      <form method="POST" action="<?= BASE_URL ?>/ocorrencias/atribuir-investigador/<?= (int)$ocorrencia['id'] ?>">
        <?= campoCsrf() ?><input type="hidden" name="versao" value="<?= (int)$ocorrencia['versao'] ?>">
        <div class="form-group">
          <label class="form-label" for="investigadorResponsavel"><?= empty($ocorrencia['investigador_id'])?'Atribuir a':'Reatribuir a' ?></label>
          <select id="investigadorResponsavel" name="investigador_id" class="form-control" required>
            <option value="">Seleccione um investigador…</option>
            <?php foreach ($investigadoresEsquadra as $inv): ?>
            <option value="<?= (int)$inv['id'] ?>" <?= (int)($ocorrencia['investigador_id'] ?? 0)===(int)$inv['id']?'selected':'' ?>><?= e(trim(($inv['patente'] ?? '').' '.$inv['nome'])) ?> · <?= (int)$inv['casos_activos'] ?> caso(s) activo(s)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <p class="text-muted" style="font-size:12px;">A atribuição é feita pelo supervisor, considerando a disponibilidade e a carga de trabalho.</p>
        <button type="submit" class="btn btn-secondary btn-sm w-full">Guardar Atribuição</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
  <?php if ($podeInvestigar): ?>
    <div class="divider"></div>
    <form method="POST" enctype="multipart/form-data" action="<?= BASE_URL ?>/ocorrencias/diligencia/<?= (int)$ocorrencia['id'] ?>">
      <?= campoCsrf() ?><input type="hidden" name="versao" value="<?= (int)$ocorrencia['versao'] ?>">
      <div class="form-group">
        <label class="form-label" for="textoDiligencia">Diligência realizada</label>
        <textarea id="textoDiligencia" name="observacao" class="form-control" rows="4" minlength="10" maxlength="10000" required placeholder="Descreva as diligências e os resultados obtidos."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label" for="estadoDiligencia">Andamento</label>
        <select id="estadoDiligencia" name="estado" class="form-control">
          <option value="">Manter <?= e($estadoLabels[$ocorrencia['estado']]) ?></option>
          <?php if ($ocorrencia['estado']==='aberta'): ?><option value="em_investigacao">Iniciar investigação</option><?php endif; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="anexosDiligencia">Documentos e evidências</label>
        <input id="anexosDiligencia" type="file" name="anexos[]" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
      </div>
      <button type="submit" class="btn btn-primary btn-sm w-full">Registar Diligência</button>
    </form>
  <?php endif; ?>
</div>
<div class="card mb-16" id="conclusao">
  <div class="card-header"><div class="card-title">Conclusão e Encerramento</div></div>
  <p><span class="badge badge-<?= $conclusaoEstado==='aprovada'?'aprovada':($conclusaoEstado==='devolvida'?'rejeitada':'pendente') ?>"><?= e(OcorrenciaService::CONCLUSOES[$conclusaoEstado]) ?></span></p>
  <?php if (!empty($ocorrencia['conclusao_proposta'])): ?>
    <p><strong>Resultado proposto:</strong> <?= e($estadoLabels[$ocorrencia['conclusao_destino']] ?? '') ?></p>
    <p><?= nl2br(e($ocorrencia['conclusao_proposta'])) ?></p>
    <p class="text-muted" style="font-size:12px;">Proposto por <?= e($ocorrencia['conclusao_proponente_nome'] ?? '') ?> em <?= date('d/m/Y H:i',strtotime($ocorrencia['conclusao_proposta_em'])) ?>.</p>
  <?php endif; ?>
  <?php if (!empty($ocorrencia['conclusao_parecer'])): ?>
    <div class="divider"></div>
    <p><strong>Parecer do supervisor</strong><br><?= nl2br(e($ocorrencia['conclusao_parecer'])) ?></p>
    <p class="text-muted" style="font-size:12px;"><?= e($ocorrencia['conclusao_decisor_nome'] ?? '') ?> · <?= date('d/m/Y H:i',strtotime($ocorrencia['conclusao_decidida_em'])) ?></p>
  <?php endif; ?>
  <?php if ($conclusaoEstado==='pendente'): ?>
    <p class="text-muted">A proposta aguarda a decisão do supervisor. O estado final será actualizado após a validação.</p>
  <?php endif; ?>
  <?php if ($_SESSION['perfil']==='supervisor' && $conclusaoEstado==='pendente' && $ocorrencia['validacao_estado']==='aprovada'): ?>
    <div class="divider"></div>
    <form method="POST" action="<?= BASE_URL ?>/ocorrencias/decidir-conclusao/<?= (int)$ocorrencia['id'] ?>">
      <?= campoCsrf() ?><input type="hidden" name="versao" value="<?= (int)$ocorrencia['versao'] ?>">
      <div class="form-group">
        <label class="form-label" for="parecerConclusao">Parecer da decisão</label>
        <textarea id="parecerConclusao" name="parecer" class="form-control" required minlength="10" maxlength="10000" rows="4" placeholder="Fundamente a validação ou indique as diligências em falta."></textarea>
      </div>
      <div class="d-flex gap-8" style="flex-wrap:wrap;">
        <button class="btn btn-primary btn-sm" name="decisao" value="aprovar" type="submit">Validar Encerramento</button>
        <button class="btn btn-secondary btn-sm" name="decisao" value="devolver" type="submit">Devolver ao Investigador</button>
      </div>
    </form>
  <?php elseif ($podeInvestigar): ?>
    <div class="divider"></div>
    <form method="POST" action="<?= BASE_URL ?>/ocorrencias/propor-conclusao/<?= (int)$ocorrencia['id'] ?>">
      <?= campoCsrf() ?><input type="hidden" name="versao" value="<?= (int)$ocorrencia['versao'] ?>">
      <div class="form-group">
        <label class="form-label" for="destinoConclusao">Resultado proposto</label>
        <select id="destinoConclusao" name="destino" class="form-control" required>
          <option value="resolvida">Resolvida</option>
          <option value="arquivada">Arquivada</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="textoConclusao">Fundamentação da conclusão</label>
        <textarea id="textoConclusao" name="conclusao" class="form-control" minlength="10" maxlength="10000" rows="4" required placeholder="Apresente os resultados e justifique o encerramento proposto."></textarea>
      </div>
      <button type="submit" class="btn btn-primary btn-sm w-full">Enviar Proposta ao Supervisor</button>
    </form>
  <?php elseif ($conclusaoEstado==='sem_proposta'): ?>
    <p class="text-muted">O investigador responsável apresenta a conclusão para validação do supervisor.</p>
  <?php endif; ?>
</div>
<div class="card mb-16">
  <div class="card-header"><div class="card-title">Estado Operacional</div></div>
  <p><span class="badge badge-<?= e($ocorrencia['estado']) ?>"><?= e($estadoLabels[$ocorrencia['estado']]) ?></span></p>
</div>

