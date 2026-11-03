<?php require ROOT . '/views/layouts/inicio.php'; ?>
<?php
/** @var array $tipos */
/** @var array $esquadras */
/** @var array $erros */
/** @var array $dados */
$isEdit = false;
$titulo = 'Registar Nova Ocorrência';
?>

<div class="breadcrumb mb-20">
  <a href="<?= BASE_URL ?>/dashboard">Início</a>
  <span class="breadcrumb-sep">›</span>
  <a href="<?= BASE_URL ?>/ocorrencias">Ocorrências</a>
  <span class="breadcrumb-sep">›</span>
  <span style="color:var(--text-primary);">Nova Ocorrência</span>
</div>

<div class="page-header">
  <div>
    <h1 class="page-title"><?= $titulo ?></h1>
    <p class="page-subtitle">Preencha todos os campos obrigatórios com rigor e exactidão</p>
    <p class="text-muted" style="font-size:12px;margin-top:8px;">A ocorrência será submetida para aprovação pelo supervisor da esquadra. Após a aprovação, ficará disponível ao Comando e poderá ser atribuído um investigador.</p>
  </div>
  <a href="<?= BASE_URL ?>/ocorrencias" class="btn btn-secondary btn-sm">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
    Voltar
  </a>
</div>

<?php if (!empty($erros)): ?>
<div class="alert alert-error mb-20">
  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <ul>
    <?php foreach ($erros as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="grid-2-1">
<div>
<form method="POST" action="<?= BASE_URL ?>/ocorrencias/criar" enctype="multipart/form-data" id="formOcorrencia" data-vld-form>
<?= campoCsrf() ?>
<?php require ROOT.'/views/partials/natureza_ocorrencia.php'; ?>
<!-- ══ 1. DADOS GERAIS ══ -->
<div class="card mb-20">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Dados Gerais
    </div>
    <span style="font-size:11px; color:var(--text-muted);">Campos obrigatórios marcados com *</span>
  </div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label class="form-label">Tipo de Ocorrência <span class="required">*</span></label>
      <select name="tipo_id" class="form-control" required id="tipoSelect">
        <option value="">— Seleccione o Tipo —</option>
        <?php foreach ($tipos as $t): ?>
        <option value="<?= $t['id'] ?>"
          data-prioridade="<?= $t['prioridade_default'] ?>"
          <?= ($dados['tipo_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
          <?= e($t['nome']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Data da Ocorrência <span class="required">*</span></label>
      <input type="date" name="data_ocorrencia" class="form-control" required
        max="<?= date('Y-m-d') ?>"
        value="<?= e($dados['data_ocorrencia'] ?? date('Y-m-d')) ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Hora da Ocorrência <span class="required">*</span></label>
      <input type="time" name="hora_ocorrencia" class="form-control" required
        value="<?= e($dados['hora_ocorrencia'] ?? date('H:i')) ?>">
    </div>
  </div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label class="form-label">Prioridade <span class="required">*</span></label>
      <select name="prioridade" class="form-control" id="prioridadeSelect">
        <option value="baixa"  <?= ($dados['prioridade'] ?? '') === 'baixa' ? 'selected' : '' ?>>Baixa</option>
        <option value="media"  <?= ($dados['prioridade'] ?? 'media') === 'media' ? 'selected' : '' ?>>Média</option>
        <option value="alta"   <?= ($dados['prioridade'] ?? '') === 'alta' ? 'selected' : '' ?>>Alta</option>
        <option value="critica"<?= ($dados['prioridade'] ?? '') === 'critica' ? 'selected' : '' ?>>Crítica</option>
      </select>
    </div>

    <?php if ($_SESSION['perfil'] === 'admin'): ?>
    <div class="form-group">
      <label class="form-label">Esquadra / Posto <span class="required">*</span></label>
      <select name="esquadra_id" class="form-control">
        <option value="">— Seleccione —</option>
        <?php foreach ($esquadras as $e): ?>
        <option value="<?= $e['id'] ?>" <?= ($dados['esquadra_id'] ?? '') == $e['id'] ? 'selected' : '' ?>>
          <?= e($e['nome']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php else: ?>
    <div class="form-group">
      <label class="form-label">Esquadra / Posto</label>
      <input type="text" class="form-control" value="<?= e($_SESSION['esquadra_nome'] ?? '') ?>" disabled>
    </div>
    <?php endif; ?>

    <div class="form-group">
      <label class="form-label">Agente Registador</label>
      <input type="text" class="form-control" value="<?= e($_SESSION['nome'] ?? '') ?>" disabled>
    </div>
  </div>
</div>

<!-- ══ 1b. TIPO DE PEÇA DE EXPEDIENTE ══ -->
<div class="card mb-20">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Tipo de Peça de Expediente
    </div>
    <span style="font-size:11px; color:var(--text-muted);">Como o processo chegou ao conhecimento da PRM</span>
  </div>
  <?php
    $tiposPeca = [
      'auto_noticia'  => ['label' => 'Auto de Notícia',  'hint' => 'A polícia testemunhou ou tomou conhecimento directo do facto.'],
      'auto_denuncia' => ['label' => 'Auto de Denúncia',  'hint' => 'Alguém apresentou uma denúncia formal à polícia.'],
      'participacao'  => ['label' => 'Participação',      'hint' => 'Uma parte envolvida participou/comunicou a ocorrência.'],
      'informacao'    => ['label' => 'Informação',        'hint' => 'Nota ou informação interna, sem carácter de denúncia.'],
    ];
    $tipoPecaSel = $dados['tipo_peca'] ?? 'auto_noticia';
  ?>
  <div class="form-row cols-4" id="tipoPecaGrupo">
    <?php foreach ($tiposPeca as $val => $tp): ?>
    <label class="form-group" style="margin:0;display:flex;align-items:flex-start;gap:8px;padding:10px;border:1px solid var(--border);border-radius:8px;cursor:pointer;">
      <input type="radio" name="tipo_peca" value="<?= $val ?>" <?= $tipoPecaSel === $val ? 'checked' : '' ?> style="margin-top:2px;">
      <span>
        <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= $tp['label'] ?></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= $tp['hint'] ?></div>
      </span>
    </label>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══ 2. LOCAL ══ -->
<div class="card mb-20">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
      Local da Ocorrência
    </div>
  </div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label class="form-label">Bairro <span class="required">*</span></label>
      <input type="text" name="bairro" id="bairroInput" class="form-control" required
        placeholder="Polana" data-validate="bairro" maxlength="80"
        value="<?= e($dados['bairro'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label class="form-label">Rua / Avenida</label>
      <input type="text" name="rua" class="form-control" placeholder="Av. Julius Nyerere"
        value="<?= e($dados['rua'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label class="form-label">Referência do Local</label>
      <input type="text" name="referencia_local" class="form-control" placeholder="Próximo ao mercado central"
        value="<?= e($dados['referencia_local'] ?? '') ?>">
    </div>
  </div>
</div>

<!-- ══ 2b. LOCALIZAÇÃO GPS ══ -->
<div class="card mb-20">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
      Localização GPS (Opcional)
    </div>
    <button type="button" class="btn btn-ghost btn-sm" onclick="limparCoordenadas()">
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      Limpar
    </button>
  </div>
  <p style="font-size:12px;color:var(--text-muted);padding:0 0 10px 0;">Clique no mapa para marcar o local exacto da ocorrência.</p>
  <div id="mapaPicker" style="height:300px;border-radius:8px;border:1px solid var(--border);"></div>
  <div class="form-row cols-2" style="margin-top:12px;">
    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">Latitude</label>
      <input type="text" id="latDisplay" class="form-control" placeholder="Clique no mapa..." readonly style="cursor:default;">
      <input type="hidden" name="latitude" id="latInput" value="<?= e($dados['latitude'] ?? '') ?>">
    </div>
    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">Longitude</label>
      <input type="text" id="lngDisplay" class="form-control" placeholder="Clique no mapa..." readonly style="cursor:default;">
      <input type="hidden" name="longitude" id="lngInput" value="<?= e($dados['longitude'] ?? '') ?>">
    </div>
  </div>
</div>

<!-- ══ 3. PARTICIPANTES ══ -->
<div class="card mb-20">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Participantes (Vítimas, Suspeitos, Testemunhas)
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="adicionarParticipante()">
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Adicionar
    </button>
  </div>

  <div id="participantesContainer">
    <div id="semParticipantes" style="text-align:center; padding:20px; color:var(--text-muted); font-size:13px;">
      Clique em "Adicionar" para registar participantes
    </div>
  </div>
</div>

<!-- ══ 4. DESCRIÇÃO ══ -->
<div class="card mb-20">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>
      Descrição dos Factos
    </div>
  </div>

  <div class="form-group">
    <label class="form-label">Relato Detalhado dos Factos <span class="required">*</span></label>
    <textarea name="descricao_factos" class="form-control" rows="6" required minlength="50"
      placeholder="Descreva os factos ocorridos: quem, o quê, quando, onde e como."><?= e($dados['descricao_factos'] ?? '') ?></textarea>
    <div class="form-hint">Mínimo 50 caracteres. Seja específico e objectivo.</div>
  </div>

  <div class="divider"></div>
  <div class="form-row cols-2">
    <div class="form-group">
      <label class="form-label">Objectos Recuperados / Apreendidos</label>
      <textarea name="objetos_recuperados" class="form-control" rows="3"
        placeholder="Arma, telefone, carteira..."><?= e($dados['objetos_recuperados'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Observações Adicionais</label>
      <textarea name="observacoes" class="form-control" rows="3"
        placeholder="Informações adicionais"><?= e($dados['observacoes'] ?? '') ?></textarea>
    </div>
  </div>
</div>

<!-- ══ 5. ANEXOS ══ -->
<div class="card mb-20">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
      Documentos e Evidências
    </div>
  </div>

  <div class="upload-area" id="uploadArea" onclick="document.getElementById('fileInput').click()">
    <div class="upload-icon">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
    </div>
    <div class="upload-text">Arrastar ficheiros ou clique para seleccionar</div>
    <div class="upload-hint">PDF, Word, Imagens — Máx. 10MB por ficheiro</div>
    <input type="file" id="fileInput" name="anexos[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" style="display:none;" onchange="mostrarFicheiros(this)">
  </div>

  <div id="fileList" style="margin-top:10px; display:none;">
    <div style="font-size:12px; font-weight:600; color:var(--text-secondary); margin-bottom:8px;">Ficheiros seleccionados:</div>
    <div id="fileItems"></div>
  </div>
</div>

<!-- ══ BOTÕES ══ -->
<div class="d-flex align-center gap-12" style="justify-content:flex-end; padding-bottom:20px;">
  <a href="<?= BASE_URL ?>/ocorrencias" class="btn btn-ghost">Cancelar</a>
  <button type="submit" class="btn btn-primary btn-lg">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    Submeter para Aprovação
  </button>
</div>

</form>
</div>

<!-- ══ PREVIEW DO AUTO ══ -->
<div>
  <div class="card mb-20" style="position:sticky; top:16px;">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Preview do Auto
      </div>
    </div>
    <pre id="autoPreview" style="white-space:pre-wrap; font-family:inherit; font-size:12px; line-height:1.7; color:var(--text-secondary); margin:0;"></pre>
  </div>
</div>
</div>

<script>
// Auto-seleccionar prioridade com base no tipo
document.getElementById('tipoSelect').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const prioridade = opt.getAttribute('data-prioridade');
  if (prioridade) {
    document.getElementById('prioridadeSelect').value = prioridade;
  }
});

let partCount = 0;
function adicionarParticipante() {
  document.getElementById('semParticipantes').style.display = 'none';
  const idx = partCount++;
  const div = document.createElement('div');
  div.className = 'participant-row';
  div.id = 'part_' + idx;
  div.innerHTML = `
    <div class="form-group" style="margin:0;">
      <label class="form-label" style="font-size:10px;">Tipo</label>
      <select name="participantes[${idx}][tipo]" class="form-control">
        <option value="vitima">Vítima</option>
        <option value="suspeito">Suspeito</option>
        <option value="testemunha">Testemunha</option>
        <option value="detido">Detido</option>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label" style="font-size:10px;">Nome Completo</label>
      <input type="text" name="participantes[${idx}][nome]" class="form-control" placeholder="Nome completo" data-validate="nome" maxlength="100">
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label" style="font-size:10px;">Documento (BI/Passaporte)</label>
      <input type="text" name="participantes[${idx}][documento]" class="form-control" placeholder="123456789012L" data-validate="bi" maxlength="15">
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label" style="font-size:10px;">Telefone</label>
      <input type="text" name="participantes[${idx}][telefone]" class="form-control" placeholder="+258 84 000 0000" data-validate="phone">
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label" style="font-size:10px;">Email <span style="color:#3B82F6;font-size:9px;">(notificações)</span></label>
      <input type="email" name="participantes[${idx}][email]" class="form-control" placeholder="utilizador@gmail.com" data-validate="email">
    </div>
    <div style="padding-top:20px;">
      <button type="button" class="btn btn-danger btn-sm btn-icon" onclick="removerParticipante(${idx})" title="Remover">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div style="grid-column:1/-1; display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-top:4px; padding-top:8px; border-top:1px dashed var(--border);">
      <div style="grid-column:1/-1; font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px;">Identificação Civil (para o Auto)</div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-size:10px;">Idade</label>
        <input type="number" name="participantes[${idx}][idade]" class="form-control" min="0" max="120" placeholder="Ex: 25">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-size:10px;">Sexo</label>
        <select name="participantes[${idx}][sexo]" class="form-control">
          <option value="">—</option>
          <option value="M">Masculino</option>
          <option value="F">Feminino</option>
          <option value="outro">Outro</option>
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-size:10px;">Estado Civil</label>
        <select name="participantes[${idx}][estado_civil]" class="form-control">
          <option value="">—</option>
          <option value="solteiro">Solteiro(a)</option>
          <option value="casado">Casado(a)</option>
          <option value="divorciado">Divorciado(a)</option>
          <option value="viuvo">Viúvo(a)</option>
          <option value="uniao_de_facto">União de Facto</option>
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-size:10px;">Data de Nascimento</label>
        <input type="date" name="participantes[${idx}][data_nascimento]" class="form-control">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-size:10px;">Nome do Pai</label>
        <input type="text" name="participantes[${idx}][nome_pai]" class="form-control" maxlength="100">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-size:10px;">Nome da Mãe</label>
        <input type="text" name="participantes[${idx}][nome_mae]" class="form-control" maxlength="100">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-size:10px;">Naturalidade</label>
        <input type="text" name="participantes[${idx}][naturalidade]" class="form-control" placeholder="Ex: Maputo" maxlength="100">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-size:10px;">Nacionalidade</label>
        <input type="text" name="participantes[${idx}][nacionalidade]" class="form-control" value="Moçambicana" maxlength="100">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-size:10px;">Profissão</label>
        <input type="text" name="participantes[${idx}][profissao]" class="form-control" maxlength="100">
      </div>
      <div class="form-group" style="margin:0; grid-column:span 2;">
        <label class="form-label" style="font-size:10px;">Endereço / Bairro</label>
        <input type="text" name="participantes[${idx}][endereco]" class="form-control" placeholder="Bairro, Rua">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-size:10px;">Quarteirão</label>
        <input type="text" name="participantes[${idx}][quarteirao]" class="form-control" maxlength="20">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="font-size:10px;">Nº Casa</label>
        <input type="text" name="participantes[${idx}][numero_casa]" class="form-control" maxlength="20">
      </div>
    </div>
  `;
  document.getElementById('participantesContainer').appendChild(div);
  if (window.VLD) VLD.initForm(document.getElementById('formOcorrencia'));
}

function removerParticipante(idx) {
  const el = document.getElementById('part_' + idx);
  if (el) el.remove();
  if (document.getElementById('participantesContainer').children.length <= 1) {
    document.getElementById('semParticipantes').style.display = 'block';
  }
}

function mostrarFicheiros(input) {
  const list = document.getElementById('fileList');
  const items = document.getElementById('fileItems');
  if (input.files.length === 0) { list.style.display = 'none'; return; }
  list.style.display = 'block';
  items.innerHTML = '';
  Array.from(input.files).forEach(f => {
    const size = (f.size / 1024).toFixed(0) + ' KB';
    items.innerHTML += `<div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border-light);font-size:12px;">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--blue-400);flex-shrink:0;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <span style="flex:1;color:var(--text-primary);">${f.name}</span>
      <span style="color:var(--text-muted);">${size}</span>
    </div>`;
  });
}

// Drag & Drop
const ua = document.getElementById('uploadArea');
ua.addEventListener('dragover', e => { e.preventDefault(); ua.classList.add('drag-over'); });
ua.addEventListener('dragleave', () => ua.classList.remove('drag-over'));
ua.addEventListener('drop', e => {
  e.preventDefault();
  ua.classList.remove('drag-over');
  const fi = document.getElementById('fileInput');
  fi.files = e.dataTransfer.files;
  mostrarFicheiros(fi);
});

// ── Mapa Picker ──
let pickerMap = null, pickerMarker = null;

function iniciarPickerMapa() {
  if (!window.L) return;
  const latSalva  = parseFloat(document.getElementById('latInput').value);
  const lngSalva  = parseFloat(document.getElementById('lngInput').value);
  const centro    = (!isNaN(latSalva) && !isNaN(lngSalva)) ? [latSalva, lngSalva] : [-25.966, 32.578];
  const zoomInicio = (!isNaN(latSalva) && !isNaN(lngSalva)) ? 16 : 13;

  pickerMap = L.map('mapaPicker').setView(centro, zoomInicio);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19
  }).addTo(pickerMap);

  if (!isNaN(latSalva) && !isNaN(lngSalva)) {
    colocarMarcador(latSalva, lngSalva);
  }

  pickerMap.on('click', function(e) {
    colocarMarcador(e.latlng.lat, e.latlng.lng);
  });

  // Geocoding automático pelo bairro
  const bairroInput = document.getElementById('bairroInput');
  if (bairroInput) {
    bairroInput.addEventListener('blur', function() {
      const bairro = this.value.trim();
      if (!bairro || document.getElementById('latInput').value) return;
      geocodificarBairro(bairro);
    });
  }
}

function geocodificarBairro(bairro) {
  const url = 'https://nominatim.openstreetmap.org/search?q='
    + encodeURIComponent(bairro + ', Maputo, Moçambique')
    + '&format=json&limit=1&countrycodes=mz';
  fetch(url, { headers: { 'Accept-Language': 'pt' } })
    .then(r => r.json())
    .then(data => {
      if (!data.length) return;
      const lat = parseFloat(data[0].lat);
      const lng = parseFloat(data[0].lon);
      if (isNaN(lat) || isNaN(lng)) return;
      pickerMap.setView([lat, lng], 15);
    })
    .catch(() => {});
}

function colocarMarcador(lat, lng) {
  if (pickerMarker) pickerMarker.remove();
  pickerMarker = L.marker([lat, lng], { draggable: true }).addTo(pickerMap);
  actualizarInputs(lat, lng);
  pickerMarker.on('dragend', function() {
    const pos = pickerMarker.getLatLng();
    actualizarInputs(pos.lat, pos.lng);
  });
}

function actualizarInputs(lat, lng) {
  const latR = parseFloat(lat).toFixed(6);
  const lngR = parseFloat(lng).toFixed(6);
  document.getElementById('latInput').value   = latR;
  document.getElementById('lngInput').value   = lngR;
  document.getElementById('latDisplay').value = latR;
  document.getElementById('lngDisplay').value = lngR;
}

function limparCoordenadas() {
  if (pickerMarker) { pickerMarker.remove(); pickerMarker = null; }
  ['latInput','lngInput','latDisplay','lngDisplay'].forEach(id => {
    document.getElementById(id).value = '';
    if (id.includes('Display')) document.getElementById(id).placeholder = 'Clique no mapa...';
  });
}

document.addEventListener('DOMContentLoaded', iniciarPickerMapa);

// ── Preview ao vivo do Auto ──
const TIPO_PECA_TITULOS = {
  auto_noticia:  'AUTO DE NOTÍCIA',
  auto_denuncia: 'AUTO DE DENÚNCIA',
  participacao:  'PARTICIPAÇÃO',
  informacao:    'INFORMAÇÃO',
};

function valorCampo(nome) {
  const el = document.querySelector(`[name="${nome}"]`);
  return el ? el.value.trim() : '';
}

function formatarDataHora() {
  const data = valorCampo('data_ocorrencia');
  const hora = valorCampo('hora_ocorrencia');
  if (!data && !hora) return '[Preencha a data/hora]';
  const dataFmt = data ? data.split('-').reverse().join('/') : '[Preencha a data]';
  return `${dataFmt}${hora ? ' às ' + hora : ' · [Preencha a hora]'}`;
}

function formatarLocal() {
  const partes = ['bairro', 'rua', 'referencia_local'].map(valorCampo).filter(Boolean);
  return partes.length ? partes.join(', ') : '[Preencha o local]';
}

function atualizarPreviewAuto() {
  const tipoPecaEl = document.querySelector('input[name="tipo_peca"]:checked');
  const tipoPeca = tipoPecaEl ? tipoPecaEl.value : 'auto_noticia';
  const titulo = TIPO_PECA_TITULOS[tipoPeca] || 'AUTO';

  const linhas = [
    'SIGOP-PRM — Sistema Integrado de Gestão de Ocorrências Policiais',
    `**${titulo}**`,
    '',
    `**Data e Hora:** ${formatarDataHora()}`,
    `**Local:** ${formatarLocal()}`,
    '',
    valorCampo('descricao_factos') || '[Preencha o relato dos factos]',
    '',
    '[Rascunho — o texto final do Auto em PDF será formatado a partir deste relato.]',
  ];
  document.getElementById('autoPreview').textContent = linhas.join('\n');
}

const CAMPOS_PREVIEW = [
  'tipo_peca', 'data_ocorrencia', 'hora_ocorrencia', 'bairro', 'rua', 'referencia_local', 'descricao_factos',
];
CAMPOS_PREVIEW.forEach(nome => {
  document.querySelectorAll(`[name="${nome}"]`).forEach(el => {
    el.addEventListener('input', atualizarPreviewAuto);
    el.addEventListener('change', atualizarPreviewAuto);
  });
});
document.addEventListener('DOMContentLoaded', atualizarPreviewAuto);
</script>

<?php require ROOT . '/views/layouts/fim.php'; ?>
