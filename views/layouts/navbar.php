    <!-- NAVBAR -->
    <header class="navbar">

      <button class="navbar-btn" id="sidebarToggle" title="Menu">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>

      <!-- Pesquisa Global -->
      <div class="navbar-search">
        <svg class="navbar-search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" id="globalSearch" placeholder="Pesquisar ocorrências..." autocomplete="off">
        <div class="search-results" id="searchResults"></div>
      </div>

      <div class="navbar-spacer"></div>

      <!-- Clock -->
      <div class="navbar-clock">
        <div class="navbar-time" id="clock">--:--:--</div>
        <div class="navbar-date" id="clockDate">--/--/----</div>
      </div>

      <div class="navbar-divider"></div>

      <!-- Esquadra/Comando -->
      <div style="font-size:11px; color:var(--text-muted); text-align:right; padding:0 4px;">
        <?php if ($_SESSION['perfil'] === 'admin'): ?>
          <div style="font-weight:700; color:var(--prm-orange); font-size:12px;"><?= COMMAND_NAME ?></div>
          <div>Administrador do Sistema</div>
        <?php elseif ($_SESSION['perfil'] === 'supervisor'): ?>
          <div style="font-weight:700; color:#F59E0B; font-size:12px;">
            <?= e($_SESSION['esquadra_nome'] ?? 'Sem esquadra') ?>
          </div>
          <div>Supervisor / Chefe de Esquadra</div>
        <?php elseif ($_SESSION['perfil'] === 'investigador'): ?>
          <div style="font-weight:700; color:#EF4444; font-size:12px;">
            <?= e($_SESSION['esquadra_nome'] ?? 'Sem esquadra') ?>
          </div>
          <div>Investigador — SERNIC</div>
        <?php else: ?>
          <div style="font-weight:700; color:var(--prm-orange); font-size:12px;">
            <?= e($_SESSION['esquadra_nome'] ?? 'Sem esquadra') ?>
          </div>
          <div><?= e($_SESSION['patente'] ?? 'Agente') ?></div>
        <?php endif; ?>
      </div>

      <div class="navbar-divider"></div>

      <!-- Dark Mode Toggle -->
      <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" data-tooltip="Modo Escuro">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
        </svg>
      </button>

      <?php if ($_SESSION['perfil'] === 'agente'): ?>
      <a href="<?= BASE_URL ?>/ocorrencias/criar" class="btn btn-primary btn-sm">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nova
      </a>
      <?php endif; ?>

      <!-- Notificações -->
      <div class="dropdown notif-bell-wrap">
        <button class="navbar-btn" onclick="toggleDropdown('notifMenu')" id="notifBell" title="Notificações">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>
          </svg>
          <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
        </button>
        <div class="dropdown-menu notif-menu" id="notifMenu">
          <div style="padding:12px 16px;font-weight:700;font-size:13px;color:var(--text-primary);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <span>Notificações</span>
            <button onclick="marcarTodasLidas()" style="font-size:11px;color:var(--text-muted);background:none;border:none;cursor:pointer;padding:0;">Marcar todas lidas</button>
          </div>
          <div id="notifLista">
            <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">A carregar...</div>
          </div>
        </div>
      </div>

      <!-- User Menu -->
      <div class="dropdown">
        <div class="navbar-user-menu" onclick="toggleDropdown('userMenu')">
          <div class="navbar-avatar"><?= e($_av) ?></div>
          <div class="navbar-user-info">
            <div class="navbar-user-name"><?= e(explode(' ', $_SESSION['nome'] ?? '')[0]) ?></div>
            <div class="navbar-user-role"><?= ['admin'=>'Administrador','supervisor'=>'Supervisor','investigador'=>'Investigador','agente'=>'Agente'][$_SESSION['perfil']] ?? ucfirst($_SESSION['perfil']) ?></div>
          </div>
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--text-muted);">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </div>
        <div class="dropdown-menu" id="userMenu">
          <a href="<?= BASE_URL ?>/configuracoes" class="dropdown-item">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Meu Perfil
          </a>
          <a href="<?= BASE_URL ?>/configuracoes" class="dropdown-item">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            Configurações
          </a>
          <div class="dropdown-divider"></div>
          <button type="button" class="dropdown-item danger" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;padding:0;"
                  onclick="confirmarSaida();">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Terminar Sessão
          </button>
        </div>
      </div>

    </header>
