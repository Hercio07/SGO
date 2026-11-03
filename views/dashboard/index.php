<?php require ROOT . '/views/layouts/inicio.php'; ?>
<form method="GET" class="d-flex gap-8 mb-20" style="align-items:center;flex-wrap:wrap;">
  <label class="form-label" style="margin:0;">Natureza dos dados</label>
  <?php require ROOT.'/views/partials/filtro_natureza.php'; ?>
  <button type="submit" class="btn btn-secondary btn-sm">Aplicar</button>
  <span class="text-muted" style="font-size:11px;">Seleccione dados confirmados para excluir registos por validar e simulados.</span>
</form>

<?php
/** @var array $stats */
/** @var array $recentes */
/** @var array $criticas */
/** @var int $pendentes */
/** @var array $evolucaoData */
/** @var array $porTipoData */
/** @var array $porEsquadra */
$meses      = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
$mesesExtenso = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$evoLabels  = array_map(fn($r) => $meses[$r['mes']-1], $evolucaoData);
$evoValues  = array_map(fn($r) => $r['total'], $evolucaoData);
$tipoLabels = array_map(fn($r) => $r['nome'], $porTipoData);
$tipoValues = array_map(fn($r) => $r['total'], $porTipoData);
$esqLabels  = array_map(fn($r) => mb_substr($r['nome'], 0, 20, 'UTF-8'), $porEsquadra);
$esqValues  = array_map(fn($r) => $r['total'], $porEsquadra);
?>

<!-- Breadcrumb -->
<div class="breadcrumb mb-20">
  <span>Início</span>
  <span class="breadcrumb-sep">›</span>
  <span style="color:var(--text-primary);">Dashboard</span>
</div>

<!-- Page Header -->
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Centro de Comando</h1>
    <p class="page-subtitle">Visão geral das ocorrências — <?= date('d') . ' de ' . $mesesExtenso[(int)date('n') - 1] . ' de ' . date('Y') ?></p>
  </div>
  <div class="d-flex align-center gap-8">
    <span class="live-indicator">
      <span class="live-dot"></span>
      Actualizando em tempo real
    </span>
    <?php if ($_SESSION['perfil'] === 'admin'): ?>
    <a href="<?= BASE_URL ?>/relatorios" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Relatórios
    </a>
    <?php endif; ?>
    <?php if ($_SESSION['perfil'] === 'agente'): ?>
    <a href="<?= BASE_URL ?>/ocorrencias/criar" class="btn btn-primary btn-sm">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Nova Ocorrência
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Command Status Bar -->
<?php if ($_SESSION['perfil'] === 'supervisor'): ?>
<div class="card mb-20">
  <a href="<?= BASE_URL ?>/ocorrencias?validacao_estado=pendente"><?= (int)$pendentes ?> ocorrência(s) pendente(s) de aprovação</a>
  <div style="margin-top:10px;"><a href="<?= BASE_URL ?>/ocorrencias?conclusao_estado=pendente"><?= (int)$conclusoesPendentes ?> encerramento(s) por validar</a></div>
</div>
<?php endif; ?>
<?php if (!empty($criticas)): ?>
<div class="alert-banner mb-20">
  <div class="alert-banner-icon">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
    </svg>
  </div>
  <div style="flex:1;">
    <div style="font-size:13px; font-weight:700; color:var(--red-400);">
      <?= count($criticas) ?> Ocorrência<?= count($criticas) > 1 ? 's' : '' ?> Crítica<?= count($criticas) > 1 ? 's' : '' ?> em Aberto
    </div>
    <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">
      <?= implode(' · ', array_map(fn($c) => $c['numero'] . ' — ' . $c['tipo_nome'], array_slice($criticas, 0, 3))) ?>
      <?= count($criticas) > 3 ? ' e mais ' . (count($criticas)-3) . '...' : '' ?>
    </div>
  </div>
  <a href="<?= BASE_URL ?>/ocorrencias?prioridade=critica&estado=aberta" class="btn btn-danger btn-sm">Ver Todas</a>
</div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="stat-grid mb-24">
  <a href="<?= BASE_URL ?>/ocorrencias" style="text-decoration:none;">
    <div class="stat-card stat-blue">
      <div class="stat-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <div class="stat-value"><?= number_format($stats['total']) ?></div>
      <div class="stat-label">Total de Ocorrências</div>
    </div>
  </a>

  <a href="<?= BASE_URL ?>/ocorrencias?data_inicio=<?= date('Y-m-d') ?>&data_fim=<?= date('Y-m-d') ?>" style="text-decoration:none;">
    <div class="stat-card stat-purple">
      <div class="stat-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
      </div>
      <div class="stat-value"><?= number_format($stats['hoje']) ?></div>
      <div class="stat-label">Ocorrências Hoje</div>
    </div>
  </a>

  <a href="<?= BASE_URL ?>/ocorrencias?estado=aberta" style="text-decoration:none;">
    <div class="stat-card stat-yellow">
      <div class="stat-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <div class="stat-value"><?= number_format($stats['abertas']) ?></div>
      <div class="stat-label">Em Aberto</div>
    </div>
  </a>

  <a href="<?= BASE_URL ?>/ocorrencias?estado=em_investigacao" style="text-decoration:none;">
    <div class="stat-card stat-blue">
      <div class="stat-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
        </svg>
      </div>
      <div class="stat-value"><?= number_format($stats['em_investigacao']) ?></div>
      <div class="stat-label">Em Investigação</div>
    </div>
  </a>

  <a href="<?= BASE_URL ?>/ocorrencias?estado=resolvida" style="text-decoration:none;">
    <div class="stat-card stat-green">
      <div class="stat-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
      </div>
      <div class="stat-value"><?= number_format($stats['resolvidas']) ?></div>
      <div class="stat-label">Resolvidas</div>
    </div>
  </a>

  <a href="<?= BASE_URL ?>/ocorrencias?estado=arquivada" style="text-decoration:none;">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(100,116,139,0.1); color:var(--text-muted);">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/>
        </svg>
      </div>
      <div class="stat-value"><?= number_format($stats['arquivadas']) ?></div>
      <div class="stat-label">Arquivadas</div>
    </div>
  </a>
</div>

<?php if ($stats['total'] === 0): ?>
<div class="card mb-20">
  <div class="empty-state" style="padding:48px 20px;">
    <div class="empty-state-icon">
      <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </div>
    <div class="empty-state-title">Sem Ocorrências Registadas</div>
    <div class="empty-state-text">Os gráficos e o mapa serão exibidos assim que existirem ocorrências no sistema.</div>
    <?php if ($_SESSION['perfil'] !== 'admin'): ?>
    <a href="<?= BASE_URL ?>/ocorrencias/criar" class="btn btn-primary btn-sm" style="margin-top:16px;">Registar Primeira Ocorrência</a>
    <?php endif; ?>
  </div>
</div>
<?php else: ?>

<!-- CHARTS ROW 1 -->
<div class="grid-2-1 mb-20">

  <!-- Evolução Mensal -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Evolução Mensal — <?= date('Y') ?>
      </div>
      <div class="card-actions">
        <select class="filter-input" style="font-size:12px;padding:4px 10px;" onchange="atualizarGrafico(this.value)">
          <option value="mensal">Mensal</option>
          <option value="diaria">Diária (30 dias)</option>
        </select>
      </div>
    </div>
    <div class="chart-container-lg">
      <canvas id="chartEvolucao"></canvas>
    </div>
  </div>

  <!-- Por Estado -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Por Estado
      </div>
    </div>
    <div class="chart-container">
      <canvas id="chartEstado"></canvas>
    </div>
  </div>

</div>

<!-- CHARTS ROW 2 -->
<div class="grid-2 mb-20">

  <!-- Por Tipo -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Ocorrências por Tipo
      </div>
    </div>
    <div class="chart-container">
      <canvas id="chartTipo"></canvas>
    </div>
  </div>

  <!-- Por Esquadra -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
        Ocorrências por Esquadra
      </div>
    </div>
    <div class="chart-container">
      <canvas id="chartEsquadra"></canvas>
    </div>
  </div>

</div>

<!-- RECENTES TABLE -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Últimas Ocorrências
    </div>
    <a href="<?= BASE_URL ?>/ocorrencias" class="btn btn-secondary btn-sm">Ver Todas</a>
  </div>

  <?php if (empty($recentes)): ?>
  <div class="empty-state">
    <div class="empty-state-icon">
      <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </div>
    <div class="empty-state-title">Sem Ocorrências</div>
    <div class="empty-state-text">Ainda não existem ocorrências registadas no sistema.</div>
  </div>
  <?php else: ?>
  <div class="table-container" style="border:none;">
    <table class="table">
      <thead>
        <tr>
          <th>Nº Ocorrência</th>
          <th>Tipo</th>
          <th>Data / Hora</th>
          <th>Bairro</th>
          <th>Esquadra</th>
          <th>Prioridade</th>
          <th>Estado</th>
          <th>Acções</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentes as $oc): ?>
        <tr>
          <td>
            <a href="<?= BASE_URL ?>/ocorrencias/ver/<?= $oc['id'] ?>" class="table-num">
              <?= e($oc['numero']) ?>
            </a>
          </td>
          <td><?= e($oc['tipo_nome']) ?></td>
          <td>
            <div style="font-size:13px;"><?= dataOcorrenciaFormatada($oc['data_ocorrencia']) ?></div>
            <div style="font-size:11px; color:var(--text-muted);"><?= horaOcorrenciaFormatada($oc['hora_ocorrencia']) ?></div>
          </td>
          <td><?= e($oc['bairro'] ?? '—') ?></td>
          <td>
            <div style="font-size:12px; max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
              <?= e($oc['esquadra_nome']) ?>
            </div>
          </td>
          <td><span class="badge badge-<?= $oc['prioridade'] ?>"><?= ucfirst($oc['prioridade']) ?></span></td>
          <td>
            <?php
            $estadoLabelMap = ['aberta'=>'Aberta','em_investigacao'=>'Em Investigação','resolvida'=>'Resolvida','arquivada'=>'Arquivada'];
            $estadoLabel = isset($estadoLabelMap[$oc['estado']]) ? $estadoLabelMap[$oc['estado']] : ucfirst($oc['estado']);
            ?>
            <span class="badge badge-<?= $oc['estado'] ?>"><?= $estadoLabel ?></span>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/ocorrencias/ver/<?= $oc['id'] ?>" class="btn btn-ghost btn-sm" data-tooltip="Ver Detalhes">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ══ MAPA DE OCORRÊNCIAS ══ -->
<div class="card mb-20">
  <div class="card-header">
    <div class="card-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
      Mapa de Ocorrências
    </div>
    <div style="font-size:12px;color:var(--text-muted);">
      <span style="display:inline-flex;align-items:center;gap:4px;margin-right:10px;"><span style="width:10px;height:10px;border-radius:50%;background:#EF4444;display:inline-block;"></span>Crítica</span>
      <span style="display:inline-flex;align-items:center;gap:4px;margin-right:10px;"><span style="width:10px;height:10px;border-radius:50%;background:#F97316;display:inline-block;"></span>Alta</span>
      <span style="display:inline-flex;align-items:center;gap:4px;margin-right:10px;"><span style="width:10px;height:10px;border-radius:50%;background:#3B82F6;display:inline-block;"></span>Média</span>
      <span style="display:inline-flex;align-items:center;gap:4px;"><span style="width:10px;height:10px;border-radius:50%;background:#22C55E;display:inline-block;"></span>Baixa</span>
    </div>
  </div>
  <div id="mapaOcorrencias"></div>
</div>

<script>
// Chart defaults
Chart.defaults.color = '#94A3B8';
Chart.defaults.borderColor = 'rgba(37,99,235,0.1)';
Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 12;

const COLORS = ['#3B82F6','#22C55E','#FACC15','#EF4444','#A855F7','#F97316','#06B6D4','#EC4899','#14B8A6','#8B5CF6'];

// Evolução Mensal
const evoLabels = <?= json_encode($evoLabels) ?>;
const evoValues = <?= json_encode($evoValues) ?>;

const ctxEvo = document.getElementById('chartEvolucao').getContext('2d');
const chartEvo = new Chart(ctxEvo, {
  type: 'line',
  data: {
    labels: evoLabels.length ? evoLabels : ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
    datasets: [{
      label: 'Ocorrências',
      data: evoValues.length ? evoValues : [],
      borderColor: '#3B82F6',
      backgroundColor: 'rgba(59,130,246,0.08)',
      borderWidth: 2.5,
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#3B82F6',
      pointRadius: 4,
      pointHoverRadius: 7,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
    scales: {
      x: { grid: { color: 'rgba(37,99,235,0.06)' }, ticks: { maxRotation: 0 } },
      y: { grid: { color: 'rgba(37,99,235,0.06)' }, beginAtZero: true, ticks: { precision: 0 } }
    }
  }
});

// Por Estado (Doughnut)
const ctxEst = document.getElementById('chartEstado').getContext('2d');
new Chart(ctxEst, {
  type: 'doughnut',
  data: {
    labels: ['Em Aberto','Em Investigação','Resolvidas','Arquivadas'],
    datasets: [{
      data: [<?= $stats['abertas'] ?>, <?= $stats['em_investigacao'] ?>, <?= $stats['resolvidas'] ?>, <?= $stats['arquivadas'] ?>],
      backgroundColor: ['#FACC15','#3B82F6','#22C55E','#64748B'],
      borderColor: '#1E293B',
      borderWidth: 3,
      hoverOffset: 8,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } }
      }
    }
  }
});

// Por Tipo (Bar)
const tipoLabels = <?= json_encode($tipoLabels) ?>;
const tipoValues = <?= json_encode($tipoValues) ?>;
const ctxTipo = document.getElementById('chartTipo').getContext('2d');
new Chart(ctxTipo, {
  type: 'bar',
  data: {
    labels: tipoLabels,
    datasets: [{
      label: 'Total',
      data: tipoValues,
      backgroundColor: tipoValues.map((_, i) => COLORS[i % COLORS.length] + 'CC'),
      borderColor: tipoValues.map((_, i) => COLORS[i % COLORS.length]),
      borderWidth: 1,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: 'rgba(37,99,235,0.06)' }, beginAtZero: true, ticks: { precision: 0 } },
      y: { grid: { display: false } }
    }
  }
});

// Por Esquadra (Bar)
const esqLabels = <?= json_encode($esqLabels) ?>;
const esqValues = <?= json_encode($esqValues) ?>;
const ctxEsq = document.getElementById('chartEsquadra').getContext('2d');
new Chart(ctxEsq, {
  type: 'bar',
  data: {
    labels: esqLabels,
    datasets: [{
      label: 'Ocorrências',
      data: esqValues,
      backgroundColor: 'rgba(59,130,246,0.6)',
      borderColor: '#3B82F6',
      borderWidth: 1,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { maxRotation: 30, font: { size: 11 } } },
      y: { grid: { color: 'rgba(37,99,235,0.06)' }, beginAtZero: true, ticks: { precision: 0 } }
    }
  }
});

// Atualizar gráfico de evolução
async function atualizarGrafico(tipo) {
  const resp = await fetch(BASE_URL + '/api/ocorrencias-chart?natureza_dados=<?= rawurlencode($filtros['natureza_dados']) ?>&tipo=' + tipo);
  const data = await resp.json();
  const meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

  if (tipo === 'mensal') {
    chartEvo.data.labels = data.map(r => meses[r.mes-1]);
    chartEvo.data.datasets[0].data = data.map(r => r.total);
  } else {
    chartEvo.data.labels = data.map(r => r.dia ? r.dia.substring(5) : r.dia);
    chartEvo.data.datasets[0].data = data.map(r => r.total);
  }
  chartEvo.update();
}

// Auto-refresh stats every 60s
setInterval(async () => {
  const r = await fetch(BASE_URL + '/api/stats?natureza_dados=<?= rawurlencode($filtros['natureza_dados']) ?>');
  // Silently refresh — full page logic here if needed
}, 60000);

// ── Mapa de Ocorrências ──
const PRIORIDADE_CORES = { critica:'#EF4444', alta:'#F97316', media:'#3B82F6', baixa:'#22C55E' };

function criarIconeMapa(prioridade) {
  const cor = PRIORIDADE_CORES[prioridade] || '#3B82F6';
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="36" viewBox="0 0 28 36">
    <path d="M14 0C6.268 0 0 6.268 0 14c0 9.333 14 22 14 22s14-12.667 14-22C28 6.268 21.732 0 14 0z" fill="${cor}" opacity="0.9"/>
    <circle cx="14" cy="14" r="6" fill="white" opacity="0.95"/>
  </svg>`;
  return L.divIcon({
    html: svg, className: '', iconSize: [28, 36], iconAnchor: [14, 36], popupAnchor: [0, -36]
  });
}

async function iniciarMapa() {
  if (!window.L) return;
  const mapa = L.map('mapaOcorrencias', { zoomControl: true }).setView([-25.966, 32.578], 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
  }).addTo(mapa);

  try {
    const resp = await fetch(BASE_URL + '/api/mapa?natureza_dados=<?= rawurlencode($filtros['natureza_dados']) ?>');
    const ocorrencias = await resp.json();

    if (!ocorrencias.length) {
      document.getElementById('mapaOcorrencias').insertAdjacentHTML('afterend',
        '<p style="text-align:center;color:var(--text-muted);font-size:13px;padding:8px 0;">Sem ocorrências com coordenadas no mapa.</p>');
      return;
    }

    const bounds = [];
    ocorrencias.forEach(oc => {
      const lat = parseFloat(oc.latitude);
      const lng = parseFloat(oc.longitude);
      if (isNaN(lat) || isNaN(lng)) return;

      const estadoMap = { aberta:'Em Aberto', em_investigacao:'Em Investigação', resolvida:'Resolvida', arquivada:'Arquivada' };
      const marcador = L.marker([lat, lng], { icon: criarIconeMapa(oc.prioridade) });
      marcador.bindPopup(`
        <div style="min-width:180px;">
          <div style="font-weight:700;font-size:13px;margin-bottom:4px;">${oc.numero}</div>
          <div style="font-size:12px;color:#94A3B8;margin-bottom:6px;">${oc.tipo_nome}</div>
          <div style="font-size:12px;margin-bottom:2px;">📍 ${oc.bairro || '—'}</div>
          <div style="font-size:12px;margin-bottom:6px;">📅 ${oc.data_ocorrencia || ''} ${oc.hora_ocorrencia ? oc.hora_ocorrencia.substring(0,5) : ''}</div>
          <div style="font-size:12px;margin-bottom:8px;">Estado: <strong>${estadoMap[oc.estado] || oc.estado}</strong></div>
          <a href="${BASE_URL}/ocorrencias/ver/${oc.id}" style="display:inline-block;background:#3B82F6;color:white;padding:4px 10px;border-radius:4px;font-size:11px;text-decoration:none;">Ver Detalhes</a>
        </div>
      `);
      marcador.addTo(mapa);
      bounds.push([lat, lng]);
    });

    if (bounds.length > 1) mapa.fitBounds(bounds, { padding: [30, 30] });
    else if (bounds.length === 1) mapa.setView(bounds[0], 15);
  } catch(e) { console.error('Erro mapa:', e); }
}

document.addEventListener('DOMContentLoaded', iniciarMapa);
</script>
<?php endif; /* fim bloco stats['total'] > 0 */ ?>

<script>
// Conserva a natureza seleccionada ao abrir as listagens dos indicadores.
(function () {
  const natureza = <?= json_encode($filtros['natureza_dados']) ?>;
  if (!natureza) return;
  const destino = new URL(<?= json_encode(BASE_URL.'/ocorrencias') ?>);
  document.querySelectorAll('a[href]').forEach(link => {
    const url = new URL(link.href);
    if (url.origin === destino.origin && url.pathname === destino.pathname) {
      url.searchParams.set('natureza_dados',natureza); link.href = url.toString();
    }
  });
})();
</script>

<?php require ROOT . '/views/layouts/fim.php'; ?>
