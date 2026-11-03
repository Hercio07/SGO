<?php require ROOT . '/views/layouts/inicio.php'; ?>
<?php
/** @var array $ocorrencia */
/** @var array $tipos */
/** @var array $esquadras */
/** @var array $participantes */
/** @var array $erros */
/** @var array $dados */
// Para a view de edição, passar os dados da ocorrência existente como $dados se não vier POST
if (empty($dados)) {
    $dados = $ocorrencia;
}
$isEdit = true;
$titulo = 'Editar Ocorrência — ' . e($ocorrencia['numero']);
?>

<div class="breadcrumb mb-20">
  <a href="<?= BASE_URL ?>/dashboard">Início</a>
  <span class="breadcrumb-sep">›</span>
  <a href="<?= BASE_URL ?>/ocorrencias">Ocorrências</a>
  <span class="breadcrumb-sep">›</span>
  <a href="<?= BASE_URL ?>/ocorrencias/ver/<?= $ocorrencia['id'] ?>"><?= e($ocorrencia['numero']) ?></a>
  <span class="breadcrumb-sep">›</span>
  <span style="color:var(--text-primary);">Editar</span>
</div>

<div class="page-header">
  <div>
    <h1 class="page-title"><?= $titulo ?></h1>
    <p class="page-subtitle">Actualize os campos necessários com rigor e exactidão</p>
  </div>
  <a href="<?= BASE_URL ?>/ocorrencias/ver/<?= $ocorrencia['id'] ?>" class="btn btn-secondary btn-sm">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
    Voltar
  </a>
</div>

<?php if (!empty($erros)): ?>
<div class="alert alert-error mb-20">
  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <ul><?php foreach ($erros as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="grid-2-1">
<div>
<form method="POST" action="<?= BASE_URL ?>/ocorrencias/editar/<?= $ocorrencia['id'] ?>" enctype="multipart/form-data" data-vld-form id="formOcorrenciaEdit">
<?= campoCsrf() ?>
<input type="hidden" name="versao" value="<?= (int)($dados['versao'] ?? $ocorrencia['versao']) ?>">
<?php require ROOT.'/views/partials/natureza_ocorrencia.php'; ?>
<!-- Dados Gerais -->
<div class="card mb-20">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Dados Gerais
    </div>
  </div>
  <div class="form-row cols-3">
    <div class="form-group">
      <label class="form-label">Tipo de Ocorrência <span class="required">*</span></label>
      <select name="tipo_id" class="form-control" required>
        <option value="">— Seleccione —</option>
        <?php foreach ($tipos as $t): ?>
        <option value="<?= $t['id'] ?>" <?= ($dados['tipo_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
          <?= e($t['nome']) ?><?= !$t['ativo'] ? ' (inactivo)' : '' ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Data da Ocorrência <span class="required">*</span></label>
      <input type="date" name="data_ocorrencia" class="form-control" required max="<?= date('Y-m-d') ?>"
        value="<?= e($dados['data_ocorrencia'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label class="form-label">Hora da Ocorrência <span class="required">*</span></label>
      <input type="time" name="hora_ocorrencia" class="form-control" required
        value="<?= e($dados['hora_ocorrencia'] ?? '') ?>">
    </div>
  </div>
  <div class="form-row cols-3">
    <div class="form-group">
      <label class="form-label">Prioridade</label>
      <select name="prioridade" class="form-control">
        <option value="baixa"   <?= ($dados['prioridade'] ?? '') === 'baixa' ? 'selected' : '' ?>>Baixa</option>
        <option value="media"   <?= ($dados['prioridade'] ?? '') === 'media' ? 'selected' : '' ?>>Média</option>
        <option value="alta"    <?= ($dados['prioridade'] ?? '') === 'alta' ? 'selected' : '' ?>>Alta</option>
        <option value="critica" <?= ($dados['prioridade'] ?? '') === 'critica' ? 'selected' : '' ?>>Crítica</option>
      </select>
    </div>
    
  </div>
</div>

<!-- Tipo de Peça de Expediente -->
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

<!-- Local -->
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
      <input type="text" name="bairro" id="bairroInput" class="form-control" required placeholder="Polana" data-validate="bairro" maxlength="80"
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

<!-- GPS -->
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
  <p style="font-size:12px;color:var(--text-muted);padding:0 0 10px 0;">Clique no mapa para actualizar a localização da ocorrência.</p>
  <div id="mapaPicker" style="height:280px;border-radius:8px;border:1px solid var(--border);"></div>
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

<!-- Participantes -->
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

  <?php if (!empty($participantes)): ?>
  <div style="margin-bottom:12px;">
    <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Participantes Registados</div>
    <?php foreach ($participantes as $p): ?>
    <div class="d-flex align-center gap-12" style="padding:8px 0;border-bottom:1px solid var(--border-light);" id="part_exist_<?= $p['id'] ?>">
      <input type="hidden" name="participantes_remover[]" id="rem_<?= $p['id'] ?>" value="" disabled>
      <div style="flex:1;font-size:12px;">
        <span class="badge badge-<?= $p['tipo'] === 'vitima' ? 'alta' : ($p['tipo'] === 'suspeito' ? 'critica' : 'media') ?>" style="font-size:9px;margin-right:6px;"><?= ucfirst($p['tipo']) ?></span>
        <strong><?= e($p['nome'] ?? '—') ?></strong>
        <?php if ($p['documento']): ?><span style="color:var(--text-muted);margin-left:8px;"><?= e($p['documento']) ?></span><?php endif; ?>
        <?php if ($p['telefone']): ?><span style="color:var(--text-muted);margin-left:8px;"><?= e($p['telefone']) ?></span><?php endif; ?>
        <?php if (!empty($p['email'])): ?><span style="color:#3B82F6;margin-left:8px;font-size:11px;"><?= e($p['email']) ?></span><?php endif; ?>
      </div>
      <button type="button" class="btn btn-danger btn-sm btn-icon" onclick="removerParticipanteExistente(<?= $p['id'] ?>)" title="Remover">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div id="participantesContainer">
    <div id="semParticipantes" style="text-align:center;padding:16px;color:var(--text-muted);font-size:13px;<?= !empty($participantes) ? 'display:none;' : '' ?>">
      Clique em "Adicionar" para registar novos participantes
    </div>
  </div>
</div>

<!-- Descrição -->
<div class="card mb-20">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>
      Descrição dos Factos
    </div>
  </div>
  <div class="form-group">
    <label class="form-label">Relato dos Factos <span class="required">*</span></label>
    <textarea name="descricao_factos" class="form-control" rows="6" required minlength="50"
      placeholder="Descreva os factos ocorridos: quem, o quê, quando, onde e como."><?= e($dados['descricao_factos'] ?? '') ?></textarea>
    <div class="form-hint">Mínimo 50 caracteres. Seja específico e objectivo.</div>
  </div>

  <div class="divider"></div>
  <div class="form-row cols-2">
    <div class="form-group">
      <label class="form-label">Objectos Recuperados</label>
      <textarea name="objetos_recuperados" class="form-control" rows="3"
        placeholder="Arma, telefone, carteira..."><?= e($dados['objetos_recuperados'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Observações</label>
      <textarea name="observacoes" class="form-control" rows="3"
        placeholder="Informações adicionais"><?= e($dados['observacoes'] ?? '') ?></textarea>
    </div>
  </div>
</div>

<!-- Anexos -->
<div class="card mb-20">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
      Adicionar Documentos / Evidências
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
  <div id="fileList" style="margin-top:10px;display:none;">
    <div style="font-size:12px;font-weight:600;color:var(--text-secondary);margin-bottom:8px;">Ficheiros seleccionados:</div>
    <div id="fileItems"></div>
  </div>
</div>

<!-- Botões -->
<div class="d-flex align-center gap-12" style="justify-content:flex-end; padding-bottom:20px;">
  <a href="<?= BASE_URL ?>/ocorrencias/ver/<?= $ocorrencia['id'] ?>" class="btn btn-ghost">Cancelar</a>
  <button type="submit" class="btn btn-primary btn-lg">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
    <?= $_SESSION['perfil'] === 'agente' && $ocorrencia['validacao_estado'] === 'rejeitada' ? 'Guardar e Resubmeter para Aprovação' : 'Guardar Alterações' ?>
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
let pickerMap = null, pickerMarker = null;

function iniciarPickerMapa() {
  if (!window.L) return;
  const latSalva = parseFloat(document.getElementById('latInput').value);
  const lngSalva = parseFloat(document.getElementById('lngInput').value);
  const centro = (!isNaN(latSalva) && !isNaN(lngSalva)) ? [latSalva, lngSalva] : [-25.966, 32.578];
  const zoom   = (!isNaN(latSalva) && !isNaN(lngSalva)) ? 16 : 13;

  pickerMap = L.map('mapaPicker').setView(centro, zoom);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19
  }).addTo(pickerMap);

  if (!isNaN(latSalva) && !isNaN(lngSalva)) {
    colocarMarcador(latSalva, lngSalva);
    document.getElementById('latDisplay').value = latSalva.toFixed(6);
    document.getElementById('lngDisplay').value = lngSalva.toFixed(6);
  }

  pickerMap.on('click', e => colocarMarcador(e.latlng.lat, e.latlng.lng));

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
  pickerMarker.on('dragend', () => {
    const pos = pickerMarker.getLatLng();
    actualizarInputs(pos.lat, pos.lng);
  });
}

function actualizarInputs(lat, lng) {
  const latR = parseFloat(lat).toFixed(6), lngR = parseFloat(lng).toFixed(6);
  document.getElementById('latInput').value = latR;
  document.getElementById('lngInput').value = lngR;
  document.getElementById('latDisplay').value = latR;
  document.getElementById('lngDisplay').value = lngR;
}

function limparCoordenadas() {
  if (pickerMarker) { pickerMarker.remove(); pickerMarker = null; }
  ['latInput','lngInput','latDisplay','lngDisplay'].forEach(id => document.getElementById(id).value = '');
}

document.addEventListener('DOMContentLoaded', iniciarPickerMapa);

// ---------- Participantes ----------
let partCount = 0;

function adicionarParticipante() {
  const n = partCount++;
  const el = document.createElement('div');
  el.className = 'd-flex gap-8 mb-12 align-start';
  el.id = 'part_' + n;
  el.innerHTML = `
    <div style="flex:1;">
      <div class="form-row cols-3" style="margin-bottom:8px;">
        <div class="form-group"><label class="form-label" style="font-size:11px;">Tipo</label>
          <select name="participantes[${n}][tipo]" class="form-control">
            <option value="vitima">Vítima</option>
            <option value="suspeito">Suspeito</option>
            <option value="testemunha">Testemunha</option>
            <option value="detido">Detido</option>
          </select></div>
        <div class="form-group" style="grid-column:span 2;"><label class="form-label" style="font-size:11px;">Nome</label>
          <input type="text" name="participantes[${n}][nome]" class="form-control" placeholder="Nome completo" data-validate="nome" maxlength="100"></div>
      </div>
      <div class="form-row cols-4">
        <div class="form-group"><label class="form-label" style="font-size:11px;">Idade</label>
          <input type="number" name="participantes[${n}][idade]" class="form-control" min="0" max="120" placeholder="Ex: 25"></div>
        <div class="form-group"><label class="form-label" style="font-size:11px;">Sexo</label>
          <select name="participantes[${n}][sexo]" class="form-control">
            <option value="">—</option>
            <option value="M">Masc.</option>
            <option value="F">Fem.</option>
            <option value="outro">Outro</option>
          </select></div>
        <div class="form-group" style="grid-column:span 2;"><label class="form-label" style="font-size:11px;">Documento (BI/Passaporte)</label>
          <input type="text" name="participantes[${n}][documento]" class="form-control" placeholder="123456789012L" data-validate="bi" maxlength="15"></div>
      </div>
      <div class="form-row cols-3">
        <div class="form-group"><label class="form-label" style="font-size:11px;">Telefone</label>
          <input type="text" name="participantes[${n}][telefone]" class="form-control" placeholder="+258 84 000 0000" data-validate="phone"></div>
        <div class="form-group"><label class="form-label" style="font-size:11px;">Email <span style="color:#3B82F6;font-size:9px;">(notificações)</span></label>
          <input type="email" name="participantes[${n}][email]" class="form-control" placeholder="utilizador@gmail.com" data-validate="email"></div>
        <div class="form-group"><label class="form-label" style="font-size:11px;">Endereço</label>
          <input type="text" name="participantes[${n}][endereco]" class="form-control" placeholder="Bairro, Rua"></div>
      </div>
      <div style="font-size:10.5px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin:8px 0 6px;padding-top:8px;border-top:1px dashed var(--border-light);">Identificação Civil (para o Auto)</div>
      <div class="form-row cols-4" style="margin-bottom:8px;">
        <div class="form-group"><label class="form-label" style="font-size:11px;">Estado Civil</label>
          <select name="participantes[${n}][estado_civil]" class="form-control">
            <option value="">—</option>
            <option value="solteiro">Solteiro(a)</option>
            <option value="casado">Casado(a)</option>
            <option value="divorciado">Divorciado(a)</option>
            <option value="viuvo">Viúvo(a)</option>
            <option value="uniao_de_facto">União de Facto</option>
          </select></div>
        <div class="form-group"><label class="form-label" style="font-size:11px;">Data de Nascimento</label>
          <input type="date" name="participantes[${n}][data_nascimento]" class="form-control"></div>
        <div class="form-group"><label class="form-label" style="font-size:11px;">Nome do Pai</label>
          <input type="text" name="participantes[${n}][nome_pai]" class="form-control" maxlength="100"></div>
        <div class="form-group"><label class="form-label" style="font-size:11px;">Nome da Mãe</label>
          <input type="text" name="participantes[${n}][nome_mae]" class="form-control" maxlength="100"></div>
      </div>
      <div class="form-row cols-4">
        <div class="form-group"><label class="form-label" style="font-size:11px;">Naturalidade</label>
          <input type="text" name="participantes[${n}][naturalidade]" class="form-control" placeholder="Ex: Maputo" maxlength="100"></div>
        <div class="form-group"><label class="form-label" style="font-size:11px;">Nacionalidade</label>
          <input type="text" name="participantes[${n}][nacionalidade]" class="form-control" value="Moçambicana" maxlength="100"></div>
        <div class="form-group"><label class="form-label" style="font-size:11px;">Profissão</label>
          <input type="text" name="participantes[${n}][profissao]" class="form-control" maxlength="100"></div>
        <div class="form-group"><label class="form-label" style="font-size:11px;">Quarteirão / Nº Casa</label>
          <div class="d-flex gap-8">
            <input type="text" name="participantes[${n}][quarteirao]" class="form-control" placeholder="Quarteirão" maxlength="20">
            <input type="text" name="participantes[${n}][numero_casa]" class="form-control" placeholder="Nº Casa" maxlength="20">
          </div></div>
      </div>
    </div>
    <button type="button" class="btn btn-danger btn-sm btn-icon" style="margin-top:20px;" onclick="document.getElementById('part_${n}').remove()">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>`;
  document.getElementById('semParticipantes').style.display = 'none';
  document.getElementById('participantesContainer').appendChild(el);
  if (window.VLD) VLD.initForm(document.getElementById('formOcorrenciaEdit'));
}

function removerParticipanteExistente(id) {
  const row = document.getElementById('part_exist_' + id);
  const input = document.getElementById('rem_' + id);
  if (row && input) {
    input.value = id;
    input.disabled = false;
    row.style.opacity = '0.3';
    row.style.textDecoration = 'line-through';
    const btn = row.querySelector('button');
    if (btn) btn.disabled = true;
  }
}

// ---------- Anexos ----------
function mostrarFicheiros(input) {
  const list = document.getElementById('fileList');
  const items = document.getElementById('fileItems');
  if (input.files.length === 0) { list.style.display = 'none'; return; }
  list.style.display = 'block';
  items.innerHTML = '';
  Array.from(input.files).forEach(f => {
    const d = document.createElement('div');
    d.style.cssText = 'font-size:12px;color:var(--text-secondary);padding:3px 0;';
    d.textContent = f.name + ' (' + (f.size/1024).toFixed(0) + ' KB)';
    items.appendChild(d);
  });
}

const ua = document.getElementById('uploadArea');
ua.addEventListener('dragover', function(e) { e.preventDefault(); ua.classList.add('drag-over'); });
ua.addEventListener('dragleave', function() { ua.classList.remove('drag-over'); });
ua.addEventListener('drop', function(e) {
  e.preventDefault();
  ua.classList.remove('drag-over');
  const fi = document.getElementById('fileInput');
  fi.files = e.dataTransfer.files;
  mostrarFicheiros(fi);
});

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
