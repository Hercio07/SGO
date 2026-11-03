<?php require ROOT . '/views/layouts/inicio.php'; ?>
<?php
/** @var array $utilizador */
/** @var array $erros */
/** @var array $tipos */
?>

<div class="breadcrumb mb-20">
  <a href="<?= BASE_URL ?>/dashboard">Início</a>
  <span class="breadcrumb-sep">›</span>
  <span style="color:var(--text-primary);">Configurações</span>
</div>

<div class="page-header">
  <div>
    <h1 class="page-title">Configurações da Conta</h1>
    <p class="page-subtitle">Gerir o seu perfil e credenciais de acesso</p>
  </div>
</div>

<?php if (!empty($erros)): ?>
<div class="alert alert-error mb-20">
  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <ul><?php foreach ($erros as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="grid-2-1">

  <div>
    <!-- Dados do Perfil -->
    <div class="card mb-20">
      <div class="card-header">
        <div class="card-title">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Informações Pessoais
        </div>
      </div>
      <form method="POST" action="<?= BASE_URL ?>/configuracoes" data-vld-form>
        <?= campoCsrf() ?>
        <input type="hidden" name="acao" value="perfil">
        <div class="form-row cols-2">
          <div class="form-group">
            <label class="form-label">Nome Completo</label>
            <input type="text" name="nome" class="form-control" value="<?= e($utilizador['nome'] ?? '') ?>" required placeholder="João Silva" data-validate="nome" maxlength="100">
          </div>
          <div class="form-group">
            <label class="form-label">Telefone</label>
            <input type="text" name="telefone" class="form-control" value="<?= e($utilizador['telefone'] ?? '') ?>" placeholder="+258 84 000 0000"
              data-validate="phone" maxlength="20">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email de acesso <span class="required">*</span></label>
          <input type="email" name="email" class="form-control" required maxlength="150"
            value="<?= e($utilizador['email'] ?? '') ?>"
            placeholder="utilizador@gmail.com" data-validate="email">
        </div>
        <div class="d-flex justify-end">
          <button type="submit" class="btn btn-primary btn-sm">Actualizar Perfil</button>
        </div>
      </form>
    </div>

    <!-- Alterar Senha -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          Alterar Senha
        </div>
      </div>
      <form method="POST" action="<?= BASE_URL ?>/configuracoes" data-vld-form>
        <?= campoCsrf() ?>
        <input type="hidden" name="acao" value="senha">
        <div class="form-group">
          <label class="form-label">Senha Actual <span class="required">*</span></label>
          <input type="password" name="senha_atual" id="cfgSenhaAtual" class="form-control" required placeholder="••••••••"
            autocomplete="current-password">
        </div>
        <div class="form-row cols-2">
          <div class="form-group">
            <label class="form-label">Nova Senha <span class="required">*</span></label>
            <input type="password" name="senha_nova" id="cfgSenhaNova" class="form-control" required placeholder="••••••••"
              data-validate="password" minlength="<?= SenhaService::MINIMO ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar Nova Senha <span class="required">*</span></label>
            <input type="password" name="senha_confirmacao" class="form-control" required placeholder="••••••••"
              data-validate="password-confirm" data-match-field="cfgSenhaNova">
          </div>
        </div>
        <div class="d-flex justify-end">
          <button type="submit" class="btn btn-warning btn-sm">Alterar Senha</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Info Card -->
  <div>
    <div class="card mb-16">
      <div style="text-align:center; padding:20px 0;">
        <div style="width:72px;height:72px;background:linear-gradient(135deg,var(--blue-700),var(--blue-500));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:24px;color:white;margin:0 auto 16px;">
          <?php $_np=explode(' ',trim($utilizador['nome']??'U')); echo e(mb_strtoupper(mb_substr($_np[0],0,1,'UTF-8'),'UTF-8').(isset($_np[1])?mb_strtoupper(mb_substr($_np[1],0,1,'UTF-8'),'UTF-8'):'')); ?>
        </div>
        <div style="font-size:16px; font-weight:700; color:var(--text-primary);"><?= e($utilizador['nome'] ?? '') ?></div>
        <div style="font-size:12px; color:var(--text-muted); margin-top:4px;"><?= e($utilizador['patente'] ?? '') ?></div>
        <div style="margin-top:10px;">
          <span class="badge badge-<?= $utilizador['perfil'] ?>"><?= ['admin'=>'Administrador','supervisor'=>'Supervisor','investigador'=>'Investigador','agente'=>'Agente'][$utilizador['perfil']] ?? ucfirst($utilizador['perfil']) ?></span>
        </div>
      </div>
      <div class="divider"></div>
      <div class="detail-grid" style="grid-template-columns:1fr;">
        <div class="detail-field">
          <div class="detail-label">NIP</div>
          <div class="detail-value"><?= e($utilizador['numero_mecanografico'] ?? '—') ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Esquadra / Unidade</div>
          <div class="detail-value"><?= e($utilizador['esquadra_nome'] ?? COMMAND_NAME) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Último Acesso</div>
          <div class="detail-value" style="font-size:13px;">
            <?= $utilizador['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($utilizador['ultimo_acesso'])) : 'Primeiro acesso' ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Tipos de Ocorrência -->
    <?php if ($_SESSION['perfil'] === 'admin'): ?>
    <div class="card">
      <div class="card-header">
        <div class="card-title" style="font-size:12px;">Tipos de Ocorrência</div>
        <button class="btn btn-secondary btn-sm" style="font-size:11px;" onclick="openModal('modalCriarTipo')">
          <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Novo
        </button>
      </div>
      <?php foreach ($tipos as $t): ?>
      <div class="d-flex align-center gap-8" style="padding:5px 0; border-bottom:1px solid var(--border-light);">
        <span style="font-size:12px; color:<?= $t['ativo'] ? 'var(--text-secondary)' : 'var(--text-muted)' ?>; flex:1; <?= !$t['ativo'] ? 'text-decoration:line-through;' : '' ?>"><?= e($t['nome']) ?></span>
        <span class="badge badge-<?= $t['prioridade_default'] ?>" style="font-size:9px;"><?= ucfirst($t['prioridade_default']) ?></span>
        <button class="btn btn-ghost btn-sm btn-icon" title="Editar" onclick='editarTipo(<?= e(json_encode(['id'=>$t['id'],'nome'=>$t['nome'],'codigo'=>$t['codigo']??'','descricao'=>$t['descricao']??'','prioridade_default'=>$t['prioridade_default']])) ?>)'>
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </button>
        <form method="POST" action="<?= BASE_URL ?>/tipos/toggle/<?= $t['id'] ?>" style="display:inline;"
              onsubmit="return confirm(<?= e(json_encode(($t['ativo'] ? 'Desactivar' : 'Activar') . ' este tipo?')) ?>)">
          <?= campoCsrf() ?>
          <button type="submit" class="btn btn-ghost btn-sm btn-icon" title="<?= e($t['ativo'] ? 'Desactivar' : 'Activar') ?>">
            <?php if ($t['ativo']): ?>
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18.36 6.64A9 9 0 015.64 19.36"/><path d="M6.34 6.34A9 9 0 0019.66 19.66"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
            <?php else: ?>
            <svg width="12" height="12" fill="none" stroke="var(--green-400)" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            <?php endif; ?>
          </button>
        </form>
      </div>
      <?php endforeach; ?>
      <?php
      $parametroPagina = 'pagina_tipos';
      $paginaAtual = $paginaTipos;
      $totalPaginas = $totalPgsTipos;
      $totalRegistos = $totalTipos;
      require ROOT . '/views/partials/paginacao.php';
      ?>
    </div>

    <!-- Modal Criar Tipo -->
    <div class="modal-overlay" id="modalCriarTipo">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title" id="tipoModalTitle">Novo Tipo de Ocorrência</div>
          <button class="modal-close" onclick="closeModal('modalCriarTipo')">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <form id="formTipo" method="POST" action="<?= BASE_URL ?>/tipos/criar">
          <?= campoCsrf() ?>
          <div class="form-row cols-2">
            <div class="form-group" style="grid-column:span 2;">
              <label class="form-label">Nome <span class="required">*</span></label>
              <input type="text" name="nome" id="tipoNome" class="form-control" required placeholder="Furto com Arrombamento">
            </div>
          </div>
          <div class="form-row cols-2">
            <div class="form-group">
              <label class="form-label">Código</label>
              <input type="text" name="codigo" id="tipoCodigo" class="form-control" placeholder="FCA">
            </div>
            <div class="form-group">
              <label class="form-label">Prioridade Padrão</label>
              <select name="prioridade_default" id="tipoPrioridade" class="form-control">
                <option value="baixa">Baixa</option>
                <option value="media" selected>Média</option>
                <option value="alta">Alta</option>
                <option value="critica">Crítica</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" id="tipoDescricao" class="form-control" rows="2" placeholder="Opcional"></textarea>
          </div>
          <div class="modal-footer" style="margin-top:0;padding-top:16px;border-top:1px solid var(--border);justify-content:flex-end;gap:8px;">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalCriarTipo')">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>

    <script>
    function editarTipo(t) {
      document.getElementById('tipoModalTitle').textContent = 'Editar Tipo de Ocorrência';
      document.getElementById('formTipo').action = '<?= BASE_URL ?>/tipos/editar/' + t.id;
      document.getElementById('tipoNome').value = t.nome;
      document.getElementById('tipoCodigo').value = t.codigo || '';
      document.getElementById('tipoPrioridade').value = t.prioridade_default;
      document.getElementById('tipoDescricao').value = t.descricao || '';
      openModal('modalCriarTipo');
    }
    </script>
    <?php endif; ?>
  </div>

</div>

<?php require ROOT . '/views/layouts/fim.php'; ?>
