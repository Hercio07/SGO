<?php require ROOT . '/views/layouts/inicio.php'; ?>
<?php
/** @var array $utilizadores */
/** @var array $esquadras */
?>

<div class="breadcrumb mb-20">
  <a href="<?= BASE_URL ?>/dashboard">Início</a>
  <span class="breadcrumb-sep">›</span>
  <span style="color:var(--text-primary);">Utilizadores</span>
</div>

<div class="page-header">
  <div>
    <h1 class="page-title">Gestão de Utilizadores</h1>
    <p class="page-subtitle"><?= $total ?> utilizador<?= $total !== 1 ? 'es' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?></p>
  </div>
  <button class="btn btn-primary btn-sm" onclick="novoUtilizador()">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Novo Utilizador
  </button>
</div>

<!-- Filters -->
<form method="GET" action="<?= BASE_URL ?>/utilizadores">
<div class="filters-bar">
  <div class="filter-search">
    <svg class="filter-search-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="q" class="filter-input" placeholder="Pesquisar por nome, email..." value="<?= e($_GET['q'] ?? '') ?>" style="padding-left:32px;">
  </div>
  <select name="perfil" class="filter-input">
    <option value="">Todos os Perfis</option>
    <option value="admin"        <?= ($_GET['perfil'] ?? '') === 'admin'        ? 'selected' : '' ?>>Administrador</option>
    <option value="supervisor"   <?= ($_GET['perfil'] ?? '') === 'supervisor'   ? 'selected' : '' ?>>Supervisor</option>
    <option value="investigador" <?= ($_GET['perfil'] ?? '') === 'investigador' ? 'selected' : '' ?>>Investigador</option>
    <option value="agente"       <?= ($_GET['perfil'] ?? '') === 'agente'       ? 'selected' : '' ?>>Agente</option>
  </select>
  <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
  <a href="<?= BASE_URL ?>/utilizadores" class="btn btn-ghost btn-sm">Limpar</a>
</div>
</form>

<!-- Table -->
<div class="card" style="padding:0;">
  <?php if (empty($utilizadores)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
    <div class="empty-state-title">Nenhum Utilizador Encontrado</div>
  </div>
  <?php else: ?>
  <div class="table-container" style="border:none; border-radius:var(--radius);">
    <table class="table">
      <thead>
        <tr>
          <th>Nome completo</th>
          <th>Patente</th>
          <th>NIP</th>
          <th>Perfil</th>
          <th>Esquadra</th>
          <th>Estado</th>
          <th>Último Acesso</th>
          <th style="text-align:right;">Acções</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($utilizadores as $u): ?>
        <tr>
          <td>
            <div class="d-flex align-center gap-10">
              <div style="width:32px;height:32px;background:linear-gradient(135deg,var(--blue-700),var(--blue-500));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;color:white;flex-shrink:0;">
                <?php $_np=explode(' ',trim($u['nome']??'U')); echo e(mb_strtoupper(mb_substr($_np[0],0,1,'UTF-8'),'UTF-8').(isset($_np[1])?mb_strtoupper(mb_substr($_np[1],0,1,'UTF-8'),'UTF-8'):'')); ?>
              </div>
              <div>
                <div style="font-size:13px; font-weight:600; color:var(--text-primary);"><?= e($u['nome']) ?></div>
                <?php if (!empty($u['email'])): ?>
                <div style="font-size:11px; color:var(--text-muted);"><?= e($u['email']) ?></div>
                <?php else: ?>
                <div style="font-size:11px; color:var(--amber-400);">Email de acesso por preencher</div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td><?= e($u['patente'] ?? '—') ?></td>
          <td><?= e($u['numero_mecanografico'] ?? '—') ?></td>
          <?php
            $perfilLabel = ['admin'=>'Administrador','supervisor'=>'Supervisor','investigador'=>'Investigador','agente'=>'Agente'];
          ?>
          <td><span class="badge badge-<?= $u['perfil'] ?>"><?= $perfilLabel[$u['perfil']] ?? ucfirst($u['perfil']) ?></span></td>
          <td style="font-size:12px;"><?= e($u['esquadra_nome'] ?? '— Comando —') ?></td>
          <td>
            <span class="badge badge-<?= $u['ativo'] ? 'ativo' : 'inativo' ?>">
              <?= $u['ativo'] ? 'Activo' : 'Inactivo' ?>
            </span>
          </td>
          <td style="font-size:12px; color:var(--text-muted);">
            <?= $u['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acesso'])) : 'Nunca' ?>
          </td>
          <td style="text-align:right;">
            <div class="d-flex gap-4" style="justify-content:flex-end;">
              <button class="btn btn-ghost btn-sm btn-icon" data-tooltip="Editar"
                onclick="editarUtilizador(<?= e(json_encode($u)) ?>)">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <?php if ($u['id'] != $_SESSION['user_id']): ?>
              <?php $toggleLabel = $u['ativo'] ? 'Desactivar' : 'Activar'; ?>
              <form method="POST" action="<?= BASE_URL ?>/utilizadores/toggle/<?= $u['id'] ?>" style="display:inline;"
                    onsubmit="return confirm(<?= e(json_encode($toggleLabel . ' o utilizador ' . $u['nome'] . '?')) ?>)">
                <?= campoCsrf() ?>
                <button type="submit" class="btn btn-ghost btn-sm btn-icon"
                        style="color:<?= $u['ativo'] ? 'var(--red-400)' : 'var(--green-400)' ?>"
                        data-tooltip="<?= e($toggleLabel) ?>">
                  <?php if ($u['ativo']): ?>
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                  <?php else: ?>
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  <?php endif; ?>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php
  $paginaAtual = $pagina;
  $totalPaginas = $totalPgs;
  $totalRegistos = $total;
  require ROOT . '/views/partials/paginacao.php';
  ?>
  <?php endif; ?>
</div>

<!-- Modal Criar Utilizador -->
<div class="modal-overlay" id="modalCriar">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="modalUtilTitle">Novo Utilizador</div>
      <button class="modal-close" onclick="closeModal('modalCriar')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form id="formUtilizador" method="POST" action="<?= BASE_URL ?>/utilizadores/criar" data-vld-form>
      <?= campoCsrf() ?>
      <input type="hidden" name="_id" id="utilId">
      <div class="form-group">
        <label class="form-label">Nome Completo <span class="required">*</span></label>
        <input type="text" name="nome" id="utilNome" class="form-control" required placeholder="João Silva"
          data-validate="nome" maxlength="100">
      </div>
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Email de acesso <span class="required">*</span></label>
          <input type="email" name="email" id="utilEmail" class="form-control" placeholder="utilizador@gmail.com" required maxlength="150"
            data-validate="email">
        </div>
        <div class="form-group">
          <label class="form-label">Telefone</label>
          <input type="text" name="telefone" id="utilTelefone" class="form-control" placeholder="+258 84 000 0000"
            data-validate="phone">
        </div>
      </div>
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Patente / Categoria</label>
          <select name="patente" id="utilPatente" class="form-control">
            <option value="">— Seleccionar Patente —</option>
            <option value="Inspector-Geral da Polícia">Inspector-Geral da Polícia</option>
            <option value="Comissário">Comissário</option>
            <option value="Primeiro-Adjunto do Comissário">Primeiro-Adjunto do Comissário</option>
            <option value="Adjunto do Comissário">Adjunto do Comissário</option>
            <option value="Superintendente Principal">Superintendente Principal</option>
            <option value="Superintendente">Superintendente</option>
            <option value="Adjunto do Superintendente">Adjunto do Superintendente</option>
            <option value="Inspector">Inspector</option>
            <option value="Subinspector">Subinspector</option>
            <option value="Aspirante">Aspirante</option>
            <option value="Sargento Principal">Sargento Principal</option>
            <option value="Sargento">Sargento</option>
            <option value="Primeiro Cabo">Primeiro Cabo</option>
            <option value="Segundo Cabo">Segundo Cabo</option>
            <option value="Guarda da Polícia">Guarda da Polícia</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">NIP</label>
          <input type="text" name="numero_mecanografico" id="utilMecan" class="form-control" placeholder="20180347"
            data-validate="mecan" maxlength="8" inputmode="numeric">
        </div>
      </div>
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Perfil <span class="required">*</span></label>
          <select name="perfil" id="utilPerfil" class="form-control" required onchange="toggleEsquadra(this.value)">
            <option value="agente">Agente</option>
            <option value="supervisor">Supervisor</option>
            <option value="investigador">Investigador</option>
            <option value="admin">Administrador</option>
          </select>
        </div>
        <div class="form-group" id="grupoEsquadra">
          <label class="form-label">Esquadra / Posto <span class="required">*</span></label>
          <select name="esquadra_id" id="utilEsquadra" class="form-control">
            <option value="">— Seleccione —</option>
            <?php foreach ($esquadras as $e): ?>
            <option value="<?= $e['id'] ?>"><?= e($e['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Senha <span class="required" id="senhaRequired">*</span></label>
          <input type="password" name="password" id="utilSenha" class="form-control" placeholder="••••••••"
            data-validate="password" minlength="<?= SenhaService::MINIMO ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Confirmar Senha</label>
          <input type="password" name="password_conf" id="utilSenhaConf" class="form-control" placeholder="••••••••"
            data-validate="password-confirm" data-match-field="utilSenha">
        </div>
      </div>
      <div class="modal-footer" style="margin-top:0; padding-top:16px; border-top:1px solid var(--border); justify-content:flex-end; gap:8px;">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalCriar')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar Utilizador</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleEsquadra(perfil) {
  const g = document.getElementById('grupoEsquadra');
  const e = document.getElementById('utilEsquadra');
  const needsEsquadra = perfil === 'agente' || perfil === 'supervisor' || perfil === 'investigador';
  g.style.display = needsEsquadra ? 'block' : 'none';
  e.required = needsEsquadra;
}

function novoUtilizador() {
  document.getElementById('modalUtilTitle').textContent = 'Novo Utilizador';
  document.getElementById('formUtilizador').action = '<?= BASE_URL ?>/utilizadores/criar';
  document.getElementById('formUtilizador').reset();
  document.getElementById('utilId').value = '';
  document.getElementById('utilSenha').placeholder = '••••••••';
  document.getElementById('senhaRequired').style.display = '';
  toggleEsquadra('agente');
  openModal('modalCriar');
  if (window.VLD) VLD.initForm(document.getElementById('formUtilizador'));
}

function editarUtilizador(u) {
  document.getElementById('modalUtilTitle').textContent = 'Editar Utilizador';
  document.getElementById('formUtilizador').action = '<?= BASE_URL ?>/utilizadores/editar/' + u.id;
  document.getElementById('utilId').value = u.id;
  document.getElementById('utilNome').value = u.nome;
  document.getElementById('utilEmail').value = u.email || '';
  document.getElementById('utilTelefone').value = u.telefone || '';
  document.getElementById('utilPatente').value = u.patente || '';
  document.getElementById('utilMecan').value = u.numero_mecanografico || '';
  document.getElementById('utilPerfil').value = u.perfil;
  document.getElementById('utilEsquadra').value = u.esquadra_id || '';
  document.getElementById('utilSenha').placeholder = 'Deixar vazio para manter a senha actual';
  document.getElementById('senhaRequired').style.display = 'none';
  toggleEsquadra(u.perfil);
  openModal('modalCriar');
}
</script>

<?php require ROOT . '/views/layouts/fim.php'; ?>
