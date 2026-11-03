/* ============================================================
   SIGOP-PRM — JavaScript Principal
   ============================================================ */

'use strict';

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[char]);
}

function internalUrl(value) {
  try {
    const base = new URL(BASE_URL + '/');
    const url = new URL(value, base);
    return url.origin === base.origin && url.pathname.startsWith(base.pathname)
      ? url.pathname + url.search + url.hash : '#';
  } catch (_) {
    return '#';
  }
}

function csrfHeaders() {
  return { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' };
}

// ── Dark Mode Toggle ──
(function() {
  var saved = localStorage.getItem('sigop-theme') || 'light';
  document.documentElement.setAttribute('data-theme', saved);
})();

function toggleTheme() {
  var current = document.documentElement.getAttribute('data-theme') || 'light';
  var next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('sigop-theme', next);
  updateThemeIcon(next);
}

function updateThemeIcon(theme) {
  var btn = document.getElementById('themeToggle');
  if (!btn) return;
  if (theme === 'dark') {
    btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
    btn.setAttribute('data-tooltip', 'Modo Claro');
  } else {
    btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>';
    btn.setAttribute('data-tooltip', 'Modo Escuro');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  var theme = document.documentElement.getAttribute('data-theme') || 'light';
  updateThemeIcon(theme);
});

// ── Relógio em Tempo Real ──
function updateClock() {
  const now = new Date();
  const h = String(now.getHours()).padStart(2, '0');
  const m = String(now.getMinutes()).padStart(2, '0');
  const s = String(now.getSeconds()).padStart(2, '0');

  const clockEl = document.getElementById('clock');
  if (clockEl) clockEl.textContent = `${h}:${m}:${s}`;

  const dias = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
  const meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
  const dateEl = document.getElementById('clockDate');
  if (dateEl) {
    dateEl.textContent = `${dias[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${meses[now.getMonth()]} ${now.getFullYear()}`;
  }
}

setInterval(updateClock, 1000);
updateClock();

// ── Toast Notifications ──
function showToast(msg, tipo = 'sucesso', titulo = null) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const icons = {
    sucesso: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
    erro:    `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
    aviso:   `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
    info:    `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
  };

  const titles = { sucesso: 'Sucesso', erro: 'Erro', aviso: 'Aviso', info: 'Informação' };

  const toast = document.createElement('div');
  toast.className = `toast toast-${tipo}`;
  toast.innerHTML = `
    <div class="toast-icon">${icons[tipo] || icons.info}</div>
    <div class="toast-body">
      <div class="toast-title">${escapeHtml(titulo || titles[tipo] || 'Notificação')}</div>
      <div class="toast-msg">${escapeHtml(msg)}</div>
    </div>
    <button class="toast-close" onclick="removeToast(this.parentElement)">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="toast-progress"></div>
  `;

  container.appendChild(toast);

  setTimeout(() => removeToast(toast), 4200);
}

function removeToast(toast) {
  if (!toast || !toast.parentElement) return;
  toast.style.animation = 'toastOut 0.3s ease forwards';
  setTimeout(() => toast.remove(), 300);
}

// ── Modal ──
function openModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.classList.remove('active');
    document.body.style.overflow = '';
  }
}

// Fechar modal ao clicar no overlay
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('active');
    document.body.style.overflow = '';
  }
});

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.active').forEach(m => {
      m.classList.remove('active');
      document.body.style.overflow = '';
    });
    document.querySelectorAll('.dropdown-menu.active').forEach(d => d.classList.remove('active'));
  }
});

// ── Dropdown ──
function toggleDropdown(id) {
  const el = document.getElementById(id);
  if (!el) return;
  const isActive = el.classList.contains('active');
  document.querySelectorAll('.dropdown-menu.active').forEach(d => d.classList.remove('active'));
  if (!isActive) el.classList.add('active');
}

document.addEventListener('click', function(e) {
  if (!e.target.closest('.dropdown')) {
    document.querySelectorAll('.dropdown-menu.active').forEach(d => d.classList.remove('active'));
  }
});

// ── Sidebar Mobile Toggle ──
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function closeSidebar() {
  sidebar && sidebar.classList.remove('open');
  sidebarOverlay && sidebarOverlay.classList.remove('active');
}

if (sidebarToggle && sidebar) {
  sidebarToggle.addEventListener('click', () => {
    const isOpen = sidebar.classList.toggle('open');
    sidebarOverlay && sidebarOverlay.classList.toggle('active', isOpen);
  });
}

if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', closeSidebar);
}

// Fechar sidebar ao navegar (em mobile)
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => {
    if (window.innerWidth <= 1024) closeSidebar();
  });
});

// ── Global Search ──
const globalSearch = document.getElementById('globalSearch');
const searchResults = document.getElementById('searchResults');

if (globalSearch && searchResults) {
  let searchTimeout;

  globalSearch.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();

    if (q.length < 2) {
      searchResults.classList.remove('active');
      return;
    }

    searchTimeout = setTimeout(async () => {
      try {
        const resp = await fetch(BASE_URL + '/api/pesquisa?q=' + encodeURIComponent(q));
        const data = await resp.json();

        if (data.length === 0) {
          searchResults.innerHTML = '<div style="padding:14px 16px; font-size:13px; color:var(--text-muted);">Sem resultados encontrados</div>';
        } else {
          searchResults.innerHTML = data.map(r => `
            <a href="${escapeHtml(internalUrl(r.url))}" class="search-result-item">
              <div style="width:32px;height:32px;background:rgba(232,119,34,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="14" height="14" fill="none" stroke="#E87722" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              </div>
              <div style="flex:1; min-width:0;">
                <div style="font-size:12px; font-weight:700; color:#E87722; font-family:monospace;">${escapeHtml(r.numero)}</div>
                <div style="font-size:12px; color:var(--text-secondary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(r.tipo)} — ${escapeHtml(r.bairro || '—')}</div>
              </div>
              <span class="badge badge-${escapeHtml(r.estado)}" style="font-size:10px;">${escapeHtml(r.estado.replace('_', ' '))}</span>
            </a>
          `).join('');
        }
        searchResults.classList.add('active');
      } catch (e) {
        console.error('Search error:', e);
      }
    }, 300);
  });

  globalSearch.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      searchResults.classList.remove('active');
      this.blur();
    }
  });

  document.addEventListener('click', function(e) {
    if (!globalSearch.contains(e.target) && !searchResults.contains(e.target)) {
      searchResults.classList.remove('active');
    }
  });
}

// Fechar sidebar ao redimensionar para desktop
window.addEventListener('resize', () => {
  if (window.innerWidth > 1024) closeSidebar();
});

// ── Table Sort ──
document.querySelectorAll('.table th[data-sort]').forEach(th => {
  th.style.cursor = 'pointer';
  th.addEventListener('click', function() {
    const table = this.closest('table');
    const col = Array.from(this.parentElement.children).indexOf(this);
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const asc = this.dataset.dir !== 'asc';
    this.dataset.dir = asc ? 'asc' : 'desc';

    rows.sort((a, b) => {
      const va = a.cells[col]?.textContent?.trim() ?? '';
      const vb = b.cells[col]?.textContent?.trim() ?? '';
      return asc ? va.localeCompare(vb, 'pt') : vb.localeCompare(va, 'pt');
    });

    const tbody = table.querySelector('tbody');
    rows.forEach(r => tbody.appendChild(r));
  });
});

// ── Confirm Dialog ──
function confirmAction(msg, url, title = 'Confirmar Acção') {
  document.getElementById('confirmTitle').textContent = title;
  document.getElementById('confirmMessage').textContent = msg;
  document.getElementById('confirmBtn').onclick = () => { window.location.href = url; };
  openModal('confirmModal');
}

// Os dois botões de saída usam o mesmo formulário POST e a confirmação da aplicação.
function confirmarSaida() {
  document.getElementById('confirmTitle').textContent = 'Terminar Sessão';
  document.getElementById('confirmMessage').textContent = 'Deseja terminar a sessão?';
  document.getElementById('confirmBtn').onclick = () => document.getElementById('logoutForm').submit();
  openModal('confirmModal');
}

// ── Form Validation UX ──
document.querySelectorAll('form').forEach(form => {
  form.addEventListener('submit', function(e) {
    const btn = this.querySelector('[type="submit"]');
    if (btn && !btn.disabled) {
      // Prevent double submit
      setTimeout(() => { btn.disabled = true; }, 10);
      setTimeout(() => { btn.disabled = false; }, 3000);
    }
  });
});

// ── Auto-hide alerts ──
document.querySelectorAll('.alert').forEach(alert => {
  if (!alert.querySelector('ul')) {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 6000);
  }
});

// ── Highlight active nav on page load ──
document.addEventListener('DOMContentLoaded', function() {
  const path = window.location.pathname;
  document.querySelectorAll('.nav-item').forEach(item => {
    const href = item.getAttribute('href');
    if (href && path.startsWith(href) && href !== '/registo/') {
      item.classList.add('active');
    }
  });
});

// ── Notificações ──
const NOTIF_ICONS = {
  sucesso: `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
  alerta:  `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
  aviso:   `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
  info:    `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
};

function formatarTempo(dtStr) {
  if (!dtStr) return '';
  const dt = new Date(dtStr.replace(' ', 'T'));
  const diff = Math.floor((Date.now() - dt.getTime()) / 1000);
  if (diff < 60)   return 'agora';
  if (diff < 3600) return Math.floor(diff/60) + ' min';
  if (diff < 86400)return Math.floor(diff/3600) + 'h';
  return Math.floor(diff/86400) + 'd';
}

let ultimoTotalNotif = 0;

async function carregarNotificacoes() {
  const badge = document.getElementById('notifBadge');
  const lista = document.getElementById('notifLista');
  if (!badge || !lista) return;

  try {
    const resp = await fetch(BASE_URL + '/api/notificacoes');
    if (!resp.ok) return;
    const data = await resp.json();

    // Badge sino
    if (data.total > 0) {
      badge.textContent = data.total > 9 ? '9+' : data.total;
      badge.style.display = 'flex';
      // Toast se chegaram novas notificações
      if (data.total > ultimoTotalNotif && ultimoTotalNotif > 0) {
        const nova = data.lista[0];
        if (nova) showToast(nova.titulo, nova.tipo || 'info');
      }
    } else {
      badge.style.display = 'none';
    }
    ultimoTotalNotif = data.total;

    // Badge sidebar "Casos Criminais" (investigador)
    const conclusoesBadge = document.getElementById('navConclusoesCount');
    if (conclusoesBadge && data.conclusoes !== null && data.conclusoes !== undefined) {
      conclusoesBadge.textContent = data.conclusoes;
      conclusoesBadge.style.display = data.conclusoes > 0 ? 'inline-block' : 'none';
    }
    const navBadge = document.getElementById('navPendentesCount');
    if (navBadge && data.pendentes !== null && data.pendentes !== undefined) {
      navBadge.textContent = data.pendentes;
      navBadge.style.display = data.pendentes > 0 ? 'inline-block' : 'none';
    }

    // Lista
    if (!data.lista || data.lista.length === 0) {
      lista.innerHTML = '<div style="padding:28px 16px;text-align:center;color:var(--text-muted);font-size:13px;">Sem notificações</div>';
      return;
    }

    lista.innerHTML = data.lista.map(n => {
      const tipo = Object.hasOwn(NOTIF_ICONS, n.tipo) ? n.tipo : 'info';
      const lida = n.lida == 1;
      return `<a href="${escapeHtml(internalUrl(n.url || '#'))}" class="notif-item${lida ? '' : ' unread'}"
                onclick="notifClick(event, ${Number(n.id) || 0}, this.getAttribute('href'))">
        <div class="notif-icon ${tipo}">${NOTIF_ICONS[tipo] || NOTIF_ICONS.info}</div>
        <div style="flex:1;min-width:0;">
          <div class="notif-titulo">${escapeHtml(n.titulo)}</div>
          <div class="notif-msg">${escapeHtml(n.mensagem || '')}</div>
          <div class="notif-tempo">${escapeHtml(formatarTempo(n.created_at))}</div>
        </div>
      </a>`;
    }).join('');

  } catch(e) { /* silencioso */ }
}

async function notifClick(e, id, url) {
  e.preventDefault();
  await fetch(BASE_URL + '/api/marcar-lida/' + id, { method: 'POST', headers: csrfHeaders() });
  carregarNotificacoes();
  if (url && url !== '#') window.location.href = url;
}

async function marcarTodasLidas() {
  await fetch(BASE_URL + '/api/marcar-todas-lidas', { method: 'POST', headers: csrfHeaders() });
  carregarNotificacoes();
  document.querySelectorAll('.dropdown-menu.active').forEach(d => d.classList.remove('active'));
}

// Iniciar polling
document.addEventListener('DOMContentLoaded', function() {
  carregarNotificacoes();
  setInterval(carregarNotificacoes, 30000);
});
