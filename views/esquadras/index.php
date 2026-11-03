<?php require ROOT . '/views/layouts/inicio.php'; ?>
<?php
/** @var array $esquadras */
?>

<div class="breadcrumb mb-20">
  <a href="<?= BASE_URL ?>/dashboard">Início</a>
  <span class="breadcrumb-sep">›</span>
  <span style="color:var(--text-primary);">Esquadras e Postos</span>
</div>

<div class="page-header">
  <div>
    <h1 class="page-title">Esquadras e Postos Policiais</h1>
    <p class="page-subtitle"><?= $total ?> unidade<?= $total !== 1 ? 's' : '' ?> registada<?= $total !== 1 ? 's' : '' ?></p>
  </div>
  <button class="btn btn-primary btn-sm" onclick="openModal('modalEsquadra')">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Nova Esquadra / Posto
  </button>
</div>

<!-- Cards Grid -->
<div class="grid-3 mb-20">
  <?php foreach ($esquadras as $esq): ?>
  <div class="card" style="padding:0; overflow:hidden;">
    <div style="background:linear-gradient(135deg, rgba(30,58,138,0.3) 0%, rgba(37,99,235,0.1) 100%); padding:20px; border-bottom:1px solid var(--border);">
      <div class="d-flex align-center justify-between mb-8">
        <span class="badge <?= $esq['tipo'] === 'esquadra' ? 'badge-em_investigacao' : 'badge-media' ?>">
          <?= ucfirst($esq['tipo']) ?>
        </span>
        <?php if (!empty($esq['codigo'])): ?>
        <code style="font-size:11px; color:var(--text-muted); background:var(--bg-darkest); padding:2px 8px; border-radius:4px;"><?= e($esq['codigo']) ?></code>
        <?php endif; ?>
      </div>
      <div style="font-size:15px; font-weight:700; color:var(--text-primary); line-height:1.3;"><?= e($esq['nome']) ?></div>
      <?php if (!empty($esq['distrito'])): ?>
      <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;margin-right:4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <?= e($esq['bairro'] ?? $esq['distrito']) ?>, <?= e($esq['distrito']) ?>
      </div>
      <?php endif; ?>
    </div>
    <div style="padding:16px;">
      <div class="d-flex justify-between mb-12">
        <div style="text-align:center;">
          <div style="font-size:22px; font-weight:800; color:var(--blue-400);"><?= $esq['total_agentes'] ?></div>
          <div style="font-size:11px; color:var(--text-muted);">Agentes</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:22px; font-weight:800; color:var(--text-primary);"><?= $esq['total_ocorrencias'] ?></div>
          <div style="font-size:11px; color:var(--text-muted);">Ocorrências</div>
        </div>
        <?php if (!empty($esq['telefone'])): ?>
        <div style="text-align:center;">
          <div style="font-size:12px; font-weight:600; color:var(--text-primary);"><?= e($esq['telefone']) ?></div>
          <div style="font-size:11px; color:var(--text-muted);">Telefone</div>
        </div>
        <?php endif; ?>
      </div>
      <?php if (!empty($esq['comandante_nome'])): ?>
      <div style="font-size:12px; color:var(--text-secondary); padding-top:10px; border-top:1px solid var(--border);">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline; margin-right:4px;"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <?= e($esq['comandante_nome']) ?>
      </div>
      <?php endif; ?>
    </div>
    <div style="padding:10px 16px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-card-2);">
      <a href="<?= BASE_URL ?>/ocorrencias?esquadra=<?= $esq['id'] ?>" class="btn btn-ghost btn-sm">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Ocorrências
      </a>
      <button class="btn btn-ghost btn-sm" onclick="editarEsquadra(<?= e(json_encode($esq)) ?>)">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Editar
      </button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php
$paginaAtual = $pagina;
$totalPaginas = $totalPgs;
$totalRegistos = $total;
require ROOT . '/views/partials/paginacao.php';
?>

<!-- Modal Esquadra -->
<div class="modal-overlay" id="modalEsquadra">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="modalEsqTitle">Nova Esquadra / Posto</div>
      <button class="modal-close" onclick="closeModal('modalEsquadra')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form id="formEsquadra" method="POST" action="<?= BASE_URL ?>/esquadras/criar" data-vld-form>
      <?= campoCsrf() ?>
      <div class="form-row cols-3">
        <div class="form-group" style="grid-column:span 2;">
          <label class="form-label">Nome da Esquadra / Posto <span class="required">*</span></label>
          <input type="text" name="nome" id="esqNome" class="form-control" required placeholder="1ª Esquadra de KaMpfumo"
            minlength="3" maxlength="150">
        </div>
        <div class="form-group">
          <label class="form-label">Código</label>
          <input type="text" name="codigo" id="esqCodigo" class="form-control" placeholder="ECM-01"
            data-validate="codigo" maxlength="10">
        </div>
      </div>
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Tipo <span class="required">*</span></label>
          <select name="tipo" id="esqTipo" class="form-control" required>
            <option value="esquadra">Esquadra</option>
            <option value="posto">Posto Policial</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Distrito</label>
          <input type="text" name="distrito" id="esqDistrito" class="form-control" placeholder="KaMpfumo"
            maxlength="100">
        </div>
      </div>
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Bairro</label>
          <input type="text" name="bairro" id="esqBairro" class="form-control" placeholder="Polana"
            data-validate="bairro" maxlength="100">
        </div>
        <div class="form-group">
          <label class="form-label">Endereço</label>
          <input type="text" name="endereco" id="esqEndereco" class="form-control" placeholder="Av. Julius Nyerere, 123"
            maxlength="200">
        </div>
      </div>
      <div class="form-row cols-3">
        <div class="form-group">
          <label class="form-label">Telefone</label>
          <input type="text" name="telefone" id="esqTel" class="form-control" placeholder="+258 21 000 000"
            data-validate="phone">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" id="esqEmail" class="form-control" placeholder="esquadra@gmail.com"
            data-validate="email">
        </div>
        <div class="form-group">
          <label class="form-label">Nome do Comandante</label>
          <input type="text" name="comandante_nome" id="esqCom" class="form-control" placeholder="Insp. João Silva"
            maxlength="150">
        </div>
      </div>
      <div class="modal-footer" style="margin-top:0; padding-top:16px; border-top:1px solid var(--border); justify-content:flex-end; gap:8px;">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalEsquadra')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
function editarEsquadra(e) {
  document.getElementById('modalEsqTitle').textContent = 'Editar: ' + e.nome;
  document.getElementById('formEsquadra').action = '<?= BASE_URL ?>/esquadras/editar/' + e.id;
  document.getElementById('esqNome').value = e.nome || '';
  document.getElementById('esqCodigo').value = e.codigo || '';
  document.getElementById('esqTipo').value = e.tipo || 'esquadra';
  document.getElementById('esqDistrito').value = e.distrito || '';
  document.getElementById('esqBairro').value = e.bairro || '';
  document.getElementById('esqEndereco').value = e.endereco || '';
  document.getElementById('esqTel').value = e.telefone || '';
  document.getElementById('esqEmail').value = e.email || '';
  document.getElementById('esqCom').value = e.comandante_nome || '';
  openModal('modalEsquadra');
}
</script>

<?php require ROOT . '/views/layouts/fim.php'; ?>
