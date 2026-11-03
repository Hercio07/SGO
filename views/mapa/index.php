<?php require ROOT . '/views/layouts/inicio.php'; ?>
<form method="GET" class="d-flex gap-8 mb-20" style="align-items:center;flex-wrap:wrap;">
  <label class="form-label" style="margin:0;">Natureza dos dados</label>
  <?php require ROOT.'/views/partials/filtro_natureza.php'; ?>
  <button type="submit" class="btn btn-secondary btn-sm">Aplicar</button>
  <span class="text-muted" style="font-size:11px;">Seleccione dados confirmados para excluir registos por validar e simulados.</span>
</form>

<?php
$prioridades = [
    'baixa'   => ['label' => 'Baixa',   'cor' => '#22C55E'],
    'media'   => ['label' => 'Média',   'cor' => '#F59E0B'],
    'alta'    => ['label' => 'Alta',    'cor' => '#F97316'],
    'critica' => ['label' => 'Crítica', 'cor' => '#EF4444'],
];
$estados = [
    'aberta'          => ['label' => 'Aberta',          'cor' => '#64748B'],
    'em_investigacao' => ['label' => 'Em Investigação', 'cor' => '#3B82F6'],
    'resolvida'       => ['label' => 'Resolvida',       'cor' => '#22C55E'],
    'arquivada'       => ['label' => 'Arquivada',       'cor' => '#94A3B8'],
];
$isAdmin = ($_SESSION['perfil'] ?? '') === 'admin';
?>

<style>
/* Botões de toggle do mapa */
.btn-mapa-toggle{display:inline-flex;align-items:center;gap:6px;font-size:12px;padding:5px 12px;height:32px;border-radius:6px;border:1px solid var(--border);background:var(--bg-card);color:var(--text-secondary);cursor:pointer;transition:all .15s;}
.btn-mapa-toggle.active{background:#2563EB;color:#fff;border-color:#2563EB;}
/* Spinner de auto-refresh */
@keyframes spin{to{transform:rotate(360deg);}}
.spin{animation:spin .8s linear infinite;display:inline-block;}
/* Controlo de localização no mapa */
.leaflet-control-localizacao a{
  display:flex;align-items:center;justify-content:center;
  width:30px;height:30px;background:#fff;border-radius:4px;
  box-shadow:0 1px 5px rgba(0,0,0,.4);cursor:pointer;text-decoration:none;color:#333;
  font-size:16px;
}
.leaflet-control-localizacao a:hover{background:#f4f4f4;}
/* Marcador de localização do utilizador */
.marker-localizacao{
  width:16px;height:16px;border-radius:50%;
  background:#2563EB;border:3px solid #fff;
  box-shadow:0 0 0 4px rgba(37,99,235,.3),0 2px 8px rgba(0,0,0,.4);
}
</style>

<!-- Filtros -->
<div class="card mb-16">
  <div class="d-flex align-center gap-12 flex-wrap" style="padding:4px 0;">
    <div style="font-size:13px;font-weight:700;color:var(--text-primary);white-space:nowrap;">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:-3px;margin-right:6px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
      Mapa de Ocorrências
    </div>
    <div class="d-flex align-center gap-8 flex-wrap" style="margin-left:auto;">

      <?php if ($isAdmin): ?>
      <select id="filtroEsquadra" class="form-control" style="width:auto;font-size:12px;padding:6px 10px;height:32px;">
        <option value="">Todas as esquadras</option>
      </select>
      <?php endif; ?>

      <select id="filtroTipo" class="form-control" style="width:auto;font-size:12px;padding:6px 10px;height:32px;">
        <option value="">Todos os tipos</option>
      </select>
      <select id="filtroPrioridade" class="form-control" style="width:auto;font-size:12px;padding:6px 10px;height:32px;">
        <option value="">Todas as prioridades</option>
        <option value="baixa">Baixa</option>
        <option value="media">Média</option>
        <option value="alta">Alta</option>
        <option value="critica">Crítica</option>
      </select>
      <select id="filtroEstado" class="form-control" style="width:auto;font-size:12px;padding:6px 10px;height:32px;">
        <option value="">Todos os estados</option>
        <option value="aberta">Aberta</option>
        <option value="em_investigacao">Em Investigação</option>
        <option value="resolvida">Resolvida</option>
        <option value="arquivada">Arquivada</option>
      </select>
      <input type="date" id="filtroDataDe" class="form-control" title="Data de" style="width:auto;font-size:12px;padding:6px 10px;height:32px;">
      <span style="font-size:12px;color:var(--text-muted);white-space:nowrap;">até</span>
      <input type="date" id="filtroDataAte" class="form-control" title="Data até" style="width:auto;font-size:12px;padding:6px 10px;height:32px;">
      <button onclick="aplicarFiltros()" class="btn btn-primary btn-sm">Filtrar</button>
      <button onclick="limparFiltros()" class="btn btn-ghost btn-sm">Limpar</button>

      <!-- Heatmap toggle -->
      <button id="btnHeatmap" class="btn-mapa-toggle" onclick="toggleHeatmap()" title="Alternar entre marcadores e mapa de calor">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2a7 7 0 017 7c0 5-7 13-7 13S5 14 5 9a7 7 0 017-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
        Mapa de Calor
      </button>

      <!-- Melhoria 7: indicador de auto-actualização -->
      <span id="autoRefreshStatus" style="font-size:11px;color:var(--text-muted);white-space:nowrap;" title="Actualização automática a cada 60 segundos">
        <svg id="refreshIcon" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:-1px;margin-right:3px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        <span id="refreshLabel">—</span>
      </span>

      <span id="contadorPins" style="font-size:12px;color:var(--text-muted);white-space:nowrap;padding-left:4px;"></span>
    </div>
  </div>
</div>

<!-- Mapa -->
<div class="card" style="padding:0;overflow:hidden;">
  <div id="mapaOcorrencias" style="height:calc(100vh - 240px);min-height:480px;width:100%;"></div>
</div>

<!-- Legenda -->
<div class="card mt-12" style="padding:12px 20px;">
  <div class="d-flex align-center gap-20 flex-wrap">
    <span style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Prioridade:</span>
    <?php foreach ($prioridades as $k => $p): ?>
    <span style="font-size:12px;display:flex;align-items:center;gap:6px;">
      <span style="width:12px;height:12px;border-radius:50%;background:<?= $p['cor'] ?>;display:inline-block;box-shadow:0 0 0 2px <?= $p['cor'] ?>44;"></span>
      <?= $p['label'] ?>
    </span>
    <?php endforeach; ?>
    <span style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-left:12px;">Estado:</span>
    <?php foreach ($estados as $k => $e): ?>
    <span style="font-size:12px;display:flex;align-items:center;gap:6px;">
      <span style="width:10px;height:10px;border-radius:2px;background:<?= $e['cor'] ?>;display:inline-block;"></span>
      <?= $e['label'] ?>
    </span>
    <?php endforeach; ?>
    <span id="legendaHeatmap" style="display:none;margin-left:12px;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Intensidade:</span>
    <span id="legendaHeatmapGradient" style="display:none;height:12px;width:120px;border-radius:4px;background:linear-gradient(to right,#00f,#0ff,#0f0,#ff0,#f00);border:1px solid var(--border);"></span>
    <span id="legendaHeatmapLabels" style="display:none;font-size:11px;color:var(--text-muted);">Baixa → Crítica</span>
  </div>
</div>

<script>
const PERFIL   = <?= json_encode($_SESSION['perfil'] ?? '') ?>;
const IS_ADMIN = PERFIL === 'admin';

function escapeHtml(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

const PRIO_COR         = { baixa:'#22C55E', media:'#F59E0B', alta:'#F97316', critica:'#EF4444' };
const PRIO_INTENSIDADE = { baixa:0.25, media:0.5, alta:0.75, critica:1.0 };
const ESTADO_LABEL     = { aberta:'Aberta', em_investigacao:'Em Investigação', resolvida:'Resolvida', arquivada:'Arquivada' };
const ESTADO_COR       = { aberta:'#64748B', em_investigacao:'#3B82F6', resolvida:'#22C55E', arquivada:'#94A3B8' };

let map, clusterGroup, heatLayer, marcadorLocalizacao;
let modoHeatmap    = false;
let todosOsDados   = [];
let dadosFiltrados = [];
let timerRefresh   = null;

// ── Marcador individual ──
function criarMarcador(oc) {
  const cor = PRIO_COR[oc.prioridade] || '#64748B';
  const icon = L.divIcon({
    className: '',
    html: `<div style="width:16px;height:16px;border-radius:50%;background:${cor};border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35);cursor:pointer;"></div>`,
    iconSize:[16,16], iconAnchor:[8,8], popupAnchor:[0,-10],
  });
  const estadoCor   = ESTADO_COR[oc.estado]  || '#64748B';
  const estadoLabel = ESTADO_LABEL[oc.estado] || oc.estado;
  const data        = oc.data_ocorrencia ? oc.data_ocorrencia.split('-').reverse().join('/') : '—';
  const esquadraInfo = oc.esquadra_nome ? `<div style="font-size:11px;color:#64748b;margin-bottom:4px;">🏢 ${escapeHtml(oc.esquadra_nome)}</div>` : '';
  const popup = `
    <div style="min-width:220px;font-family:inherit;">
      <div style="font-size:13px;font-weight:800;color:#1E3A8A;margin-bottom:6px;font-family:monospace;">${escapeHtml(oc.numero)}</div>
      <div style="font-size:12px;font-weight:600;color:#1e293b;margin-bottom:4px;">${escapeHtml(oc.tipo_nome)}</div>
      ${oc.bairro ? `<div style="font-size:11px;color:#64748b;margin-bottom:4px;">📍 ${escapeHtml(oc.bairro)}</div>` : ''}
      ${esquadraInfo}
      <div style="font-size:11px;color:#64748b;margin-bottom:8px;">📅 ${escapeHtml(data)}</div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;">
        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;background:${cor}22;color:${cor};border:1px solid ${cor}44;">${escapeHtml(oc.prioridade.charAt(0).toUpperCase()+oc.prioridade.slice(1))}</span>
        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;background:${estadoCor}22;color:${estadoCor};border:1px solid ${estadoCor}44;">${escapeHtml(estadoLabel)}</span>
      </div>
      <a href="${BASE_URL}/ocorrencias/ver/${escapeHtml(String(oc.id))}" style="display:block;text-align:center;padding:6px;background:#1E3A8A;color:#fff;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;">Ver Ocorrência</a>
    </div>`;
  return L.marker([parseFloat(oc.latitude), parseFloat(oc.longitude)], { icon }).bindPopup(popup, { maxWidth:260 });
}

// ── Preencher selects ──
function popularFiltros(dados) {
  const tipos = {};
  dados.forEach(oc => { tipos[oc.tipo_nome] = true; });
  const selTipo = document.getElementById('filtroTipo');
  // Limpar opções anteriores (excepto a primeira)
  while (selTipo.options.length > 1) selTipo.remove(1);
  Object.keys(tipos).sort().forEach(nome => {
    const opt = document.createElement('option');
    opt.value = nome; opt.textContent = nome;
    selTipo.appendChild(opt);
  });

  if (IS_ADMIN) {
    const esquadras = {};
    dados.forEach(oc => { if (oc.esquadra_id) esquadras[oc.esquadra_id] = oc.esquadra_nome; });
    const selEsq = document.getElementById('filtroEsquadra');
    while (selEsq.options.length > 1) selEsq.remove(1);
    Object.entries(esquadras).sort((a,b) => a[1].localeCompare(b[1])).forEach(([id, nome]) => {
      const opt = document.createElement('option');
      opt.value = id; opt.textContent = nome;
      selEsq.appendChild(opt);
    });
  }
}

// ── Renderizar marcadores e heatmap ──
function renderizarMarcadores(dados) {
  dadosFiltrados = dados;

  clusterGroup.clearLayers();
  dados.forEach(oc => criarMarcador(oc).addTo(clusterGroup));

  const pontos = dados.map(oc => [parseFloat(oc.latitude), parseFloat(oc.longitude), PRIO_INTENSIDADE[oc.prioridade] || 0.5]);
  if (heatLayer) map.removeLayer(heatLayer);
  heatLayer = L.heatLayer(pontos, { radius:30, blur:20, maxZoom:17, gradient:{0.25:'blue',0.5:'cyan',0.65:'lime',0.8:'yellow',1.0:'red'} });
  if (modoHeatmap) heatLayer.addTo(map);

  document.getElementById('contadorPins').textContent =
    dados.length === 0 ? 'Nenhuma ocorrência com GPS' :
    dados.length + ' ocorrência' + (dados.length !== 1 ? 's' : '') + ' no mapa';

  if (dados.length > 0) {
    const bounds = dados.map(oc => [parseFloat(oc.latitude), parseFloat(oc.longitude)]);
    map.fitBounds(bounds, { padding:[40,40], maxZoom:15 });
  }
}

// ── Filtros ──
function getFiltrosActivos() {
  return {
    esquadra: IS_ADMIN ? document.getElementById('filtroEsquadra').value : '',
    tipo:     document.getElementById('filtroTipo').value,
    prio:     document.getElementById('filtroPrioridade').value,
    estado:   document.getElementById('filtroEstado').value,
    dataDe:   document.getElementById('filtroDataDe').value,
    dataAte:  document.getElementById('filtroDataAte').value,
  };
}

function filtrarDados(dados, f) {
  let r = dados;
  if (f.esquadra) r = r.filter(o => String(o.esquadra_id) === f.esquadra);
  if (f.tipo)     r = r.filter(o => o.tipo_nome === f.tipo);
  if (f.prio)     r = r.filter(o => o.prioridade === f.prio);
  if (f.estado)   r = r.filter(o => o.estado === f.estado);
  if (f.dataDe)   r = r.filter(o => o.data_ocorrencia && o.data_ocorrencia >= f.dataDe);
  if (f.dataAte)  r = r.filter(o => o.data_ocorrencia && o.data_ocorrencia <= f.dataAte);
  return r;
}

function aplicarFiltros() {
  renderizarMarcadores(filtrarDados(todosOsDados, getFiltrosActivos()));
}

function limparFiltros() {
  if (IS_ADMIN) document.getElementById('filtroEsquadra').value = '';
  document.getElementById('filtroTipo').value       = '';
  document.getElementById('filtroPrioridade').value = '';
  document.getElementById('filtroEstado').value     = '';
  document.getElementById('filtroDataDe').value     = '';
  document.getElementById('filtroDataAte').value    = '';
  renderizarMarcadores(todosOsDados);
}

// ── Toggle heatmap ──
function toggleHeatmap() {
  modoHeatmap = !modoHeatmap;
  const btn = document.getElementById('btnHeatmap');
  if (modoHeatmap) {
    map.removeLayer(clusterGroup);
    if (heatLayer) heatLayer.addTo(map);
    btn.classList.add('active');
    ['legendaHeatmap','legendaHeatmapGradient','legendaHeatmapLabels'].forEach(id => document.getElementById(id).style.display = '');
  } else {
    if (heatLayer) map.removeLayer(heatLayer);
    map.addLayer(clusterGroup);
    btn.classList.remove('active');
    ['legendaHeatmap','legendaHeatmapGradient','legendaHeatmapLabels'].forEach(id => document.getElementById(id).style.display = 'none');
  }
}

// ── Melhoria 7: auto-actualização (60 segundos) ──
function actualizarDados(primeraCarga) {
  const icone  = document.getElementById('refreshIcon');
  const label  = document.getElementById('refreshLabel');
  icone.classList.add('spin');

  fetch(BASE_URL + '/api/mapa?natureza_dados=<?= rawurlencode($filtros['natureza_dados']) ?>')
    .then(r => r.json())
    .then(dados => {
      const filtrosActivos = getFiltrosActivos();
      const haFiltros = Object.values(filtrosActivos).some(v => v !== '');

      // Actualizar tipos/esquadras no select sem apagar selecção actual
      const tipoSel = document.getElementById('filtroTipo').value;
      const esqSel  = IS_ADMIN ? document.getElementById('filtroEsquadra').value : '';
      todosOsDados = dados;
      popularFiltros(dados);
      if (tipoSel) document.getElementById('filtroTipo').value = tipoSel;
      if (IS_ADMIN && esqSel) document.getElementById('filtroEsquadra').value = esqSel;

      // Re-aplicar filtros activos ou mostrar todos
      if (haFiltros) {
        renderizarMarcadores(filtrarDados(dados, filtrosActivos));
      } else if (!primeraCarga) {
        // Só re-renderiza sem resetar o viewport em actualizações automáticas
        dadosFiltrados = dados;
        clusterGroup.clearLayers();
        dados.forEach(oc => criarMarcador(oc).addTo(clusterGroup));
        const pontos = dados.map(oc => [parseFloat(oc.latitude), parseFloat(oc.longitude), PRIO_INTENSIDADE[oc.prioridade] || 0.5]);
        if (heatLayer) map.removeLayer(heatLayer);
        heatLayer = L.heatLayer(pontos, { radius:30, blur:20, maxZoom:17, gradient:{0.25:'blue',0.5:'cyan',0.65:'lime',0.8:'yellow',1.0:'red'} });
        if (modoHeatmap) heatLayer.addTo(map);
        document.getElementById('contadorPins').textContent =
          dados.length === 0 ? 'Nenhuma ocorrência com GPS' :
          dados.length + ' ocorrência' + (dados.length !== 1 ? 's' : '') + ' no mapa';
      } else {
        renderizarMarcadores(dados);
      }

      const agora = new Date();
      label.textContent = agora.getHours().toString().padStart(2,'0') + ':' + agora.getMinutes().toString().padStart(2,'0');
      icone.classList.remove('spin');
    })
    .catch(() => {
      label.textContent = 'Erro';
      icone.classList.remove('spin');
    });
}

function iniciarAutoRefresh() {
  timerRefresh = setInterval(() => actualizarDados(false), 60000);
}

// ── Melhoria 6: controlo de camadas (street/satélite) ──
function adicionarControloCamadas() {
  const ruas = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  });
  const satelite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: '© Esri — Source: Esri, Maxar, Earthstar Geographics',
    maxZoom: 19,
  });

  ruas.addTo(map);
  L.control.layers({ 'Ruas': ruas, 'Satélite': satelite }, {}, { position:'topright', collapsed:false }).addTo(map);
}

// ── Melhoria 8: botão de localização ──
function adicionarBotaoLocalizacao() {
  const ControloLocalizacao = L.Control.extend({
    options: { position: 'topright' },
    onAdd: function() {
      const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-localizacao');
      const a   = L.DomUtil.create('a', '', div);
      a.title   = 'Centrar na minha localização';
      a.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="8" stroke-dasharray="2 2" stroke-width="1"/></svg>';
      L.DomEvent.on(a, 'click', function(e) {
        L.DomEvent.stopPropagation(e);
        centrarNaMinhaLocalizacao();
      });
      return div;
    }
  });
  new ControloLocalizacao().addTo(map);
}

function centrarNaMinhaLocalizacao() {
  if (!navigator.geolocation) {
    alert('O seu browser não suporta geolocalização.');
    return;
  }
  navigator.geolocation.getCurrentPosition(
    function(pos) {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      map.setView([lat, lng], 15);

      if (marcadorLocalizacao) marcadorLocalizacao.remove();
      marcadorLocalizacao = L.marker([lat, lng], {
        icon: L.divIcon({
          className: '',
          html: '<div class="marker-localizacao"></div>',
          iconSize:[16,16], iconAnchor:[8,8],
        }),
        zIndexOffset: 1000,
      }).addTo(map).bindPopup('A sua localização actual').openPopup();
    },
    function() {
      alert('Não foi possível obter a sua localização.\nVerifique as permissões do browser.');
    },
    { enableHighAccuracy: true, timeout: 8000 }
  );
}

// ── Inicializar ──
document.addEventListener('DOMContentLoaded', function() {
  map = L.map('mapaOcorrencias', { zoomControl: true }).setView([-25.9667, 32.5833], 12);

  // Melhoria 6: camadas street + satélite com controlo nativo
  adicionarControloCamadas();

  // Melhoria 8: botão de localização
  adicionarBotaoLocalizacao();

  clusterGroup = L.markerClusterGroup({
    maxClusterRadius: 50,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    zoomToBoundsOnClick: true,
  });
  map.addLayer(clusterGroup);

  // Melhoria 7: primeira carga + arrancar auto-refresh
  actualizarDados(true);
  iniciarAutoRefresh();
});
</script>

<?php require ROOT . '/views/layouts/fim.php'; ?>
