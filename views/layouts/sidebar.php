  <!-- ══════════════ SIDEBAR ══════════════ -->
  <aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
      <img src="<?= BASE_URL ?>/public/assets/img/logo.png" alt="PRM" class="sidebar-brand-logo">
      <div class="sidebar-brand-text">
        <h1><?= APP_NAME ?></h1>
        <span><?= $_SESSION['perfil'] === 'admin' ? COMMAND_NAME : e($_SESSION['esquadra_nome'] ?? COMMAND_NAME) ?></span>
      </div>
    </div>

    <nav class="sidebar-nav">

      <div class="nav-section-label">Principal</div>

      <a href="<?= BASE_URL ?>/dashboard" class="nav-item <?= ($paginaAtiva ?? '') === 'dashboard' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
          <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
        </svg>
        Dashboard
        <span class="live-indicator" style="margin-left:auto;">
          <span class="live-dot"></span>
        </span>
      </a>

      <a href="<?= BASE_URL ?>/ocorrencias" class="nav-item <?= ($paginaAtiva ?? '') === 'ocorrencias' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Ocorrências
      </a>

      <?php if ($_SESSION['perfil'] === 'agente'): ?>
      <a href="<?= BASE_URL ?>/ocorrencias/criar"
         class="nav-item <?= (($paginaAtiva ?? '') === 'ocorrencias' && ($vistaAtual ?? '') === 'criar') ? 'active' : '' ?>"
         style="background:var(--prm-orange);color:#fff;margin:4px 0 2px;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nova Ocorrência
      </a>
      <?php endif; ?>

      <?php if ($_SESSION['perfil'] === 'supervisor'): ?>

      <div class="nav-section-label" style="margin-top:8px;">Supervisão</div>
      <?php $nConclusoes=(new Ocorrencia())->contar(['conclusao_estado'=>'pendente']); ?>
      <a href="<?= BASE_URL ?>/ocorrencias?conclusao_estado=pendente" class="nav-item">
        Encerramentos Pendentes
        <span id="navConclusoesCount" style="margin-left:auto;background:#E87722;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;<?= $nConclusoes>0?'':'display:none;' ?>"><?= $nConclusoes ?></span>
      </a>
      <?php $nPendentes = (new Ocorrencia())->contarPorValidarEsquadra((int)$_SESSION['esquadra_id']); ?>
      <a href="<?= BASE_URL ?>/ocorrencias?validacao_estado=pendente" class="nav-item">
        Pendentes de Aprovação
        <span id="navPendentesCount" style="margin-left:auto;background:#EF4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;font-weight:700;<?= $nPendentes > 0 ? '' : 'display:none;' ?>"><?= $nPendentes ?></span>
      </a>

      <a href="<?= BASE_URL ?>/mapa" class="nav-item <?= ($paginaAtiva ?? '') === 'mapa' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
        </svg>
        Mapa
      </a>

      <a href="<?= BASE_URL ?>/relatorios" class="nav-item <?= ($paginaAtiva ?? '') === 'relatorios' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        Relatórios
      </a>

      <a href="<?= BASE_URL ?>/auditoria" class="nav-item <?= ($paginaAtiva ?? '') === 'auditoria' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
          <rect x="9" y="3" width="6" height="4" rx="2"/>
          <line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>
        </svg>
        Log de Auditoria
      </a>

      <?php endif; ?>

      <?php if ($_SESSION['perfil'] === 'investigador'): ?>

      <div class="nav-section-label" style="margin-top:8px;">Investigação</div>

      <?php $nPendentes = (new Ocorrencia())->contarPendentesEsquadra((int)($_SESSION['esquadra_id'] ?? 0)); ?>
      <a href="<?= BASE_URL ?>/ocorrencias?em_curso=1" class="nav-item <?= ($paginaAtiva ?? '') === 'ocorrencias' ? 'active' : '' ?>" style="position:relative;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Meus Casos em Curso
        <span id="navPendentesCount" style="margin-left:auto;background:#EF4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;font-weight:700;<?= $nPendentes > 0 ? '' : 'display:none;' ?>"><?= $nPendentes ?></span>
      </a>

      <a href="<?= BASE_URL ?>/mapa" class="nav-item <?= ($paginaAtiva ?? '') === 'mapa' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
        </svg>
        Mapa
      </a>

      <a href="<?= BASE_URL ?>/relatorios" class="nav-item <?= ($paginaAtiva ?? '') === 'relatorios' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        Relatórios
      </a>

      <?php endif; ?>

      <?php if ($_SESSION['perfil'] === 'admin'): ?>

      <a href="<?= BASE_URL ?>/mapa" class="nav-item <?= ($paginaAtiva ?? '') === 'mapa' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
        </svg>
        Mapa
      </a>

      <div class="nav-section-label" style="margin-top:8px;">Comando</div>

      <a href="<?= BASE_URL ?>/relatorios" class="nav-item <?= ($paginaAtiva ?? '') === 'relatorios' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        Relatórios
      </a>

      <div class="nav-section-label" style="margin-top:8px;">Administração</div>

      <a href="<?= BASE_URL ?>/utilizadores" class="nav-item <?= ($paginaAtiva ?? '') === 'utilizadores' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
        </svg>
        Utilizadores
      </a>

      <a href="<?= BASE_URL ?>/esquadras" class="nav-item <?= ($paginaAtiva ?? '') === 'esquadras' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
          <polyline points="9,22 9,12 15,12 15,22"/>
        </svg>
        Esquadras
      </a>

      <a href="<?= BASE_URL ?>/auditoria" class="nav-item <?= ($paginaAtiva ?? '') === 'auditoria' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
          <rect x="9" y="3" width="6" height="4" rx="2"/>
          <line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>
        </svg>
        Log de Auditoria
      </a>

      <?php endif; ?>

      <?php if ($_SESSION['perfil'] === 'admin'): ?>
      <a href="<?= BASE_URL ?>/backups" class="nav-item <?= ($paginaAtiva ?? '') === 'backups' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M4 7V4h16v3M5 7v13h14V7M9 12h6m-6 4h6"/><path d="M8 4v3m8-3v3"/>
        </svg>
        Backups
      </a>
      <?php endif; ?>

      <div class="nav-section-label" style="margin-top:8px;">Conta</div>

      <a href="<?= BASE_URL ?>/configuracoes" class="nav-item <?= ($paginaAtiva ?? '') === 'configuracoes' ? 'active' : '' ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
        </svg>
        Configurações
      </a>

      <button type="button" class="nav-item" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;padding:0;"
              onclick="confirmarSaida();">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Terminar Sessão
      </button>

    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <?php
      $_np = explode(' ', trim($_SESSION['nome'] ?? 'U'));
      $_av = mb_strtoupper(mb_substr($_np[0], 0, 1, 'UTF-8'), 'UTF-8')
           . (!empty($_np[1]) ? mb_strtoupper(mb_substr($_np[1], 0, 1, 'UTF-8'), 'UTF-8') : '');
    ?>
    <div class="sidebar-avatar"><?= e($_av) ?></div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name"><?= e($_SESSION['nome'] ?? '') ?></div>
          <div class="sidebar-user-role">
            <?= e($_SESSION['patente'] ?? ucfirst($_SESSION['perfil'] ?? '')) ?>
          </div>
        </div>
      </div>
    </div>

  </aside>
