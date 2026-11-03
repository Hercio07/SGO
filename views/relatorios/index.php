<?php require ROOT . '/views/layouts/inicio.php'; ?>
<?php
/** @var array $filtros */
/** @var array $ocorrencias */
/** @var int $total */
/** @var array $stats */
/** @var array $porTipo */
/** @var array $porEsquadra */
/** @var array $porBairro */
/** @var array $evolucao */
/** @var array $esquadras */
/** @var array $tipos */
$tipoLabels = array_map(function($r) { return $r['nome']; }, $porTipo);
$tipoValues = array_map(function($r) { return (int)$r['total']; }, $porTipo);
$esqLabels  = array_map(function($r) { return mb_substr($r['nome'], 0, 18, 'UTF-8'); }, $porEsquadra);
$esqValues  = array_map(function($r) { return (int)$r['total']; }, $porEsquadra);
$evoLabels  = array_map(function($r) { return $r['label']; }, $evolucao);
$evoValues  = array_map(function($r) { return (int)$r['total']; }, $evolucao);
?>

<div class="breadcrumb mb-20">
  <a href="<?= BASE_URL ?>/dashboard">Início</a>
  <span class="breadcrumb-sep">›</span>
  <span style="color:var(--text-primary);">Relatórios</span>
</div>

<div class="page-header">
  <div>
    <h1 class="page-title">Relatórios e Estatísticas</h1>
    <p class="page-subtitle"><?= number_format($total) ?> ocorrências no período seleccionado</p>
  </div>
  <div class="d-flex align-center gap-8">
    <a href="<?= BASE_URL ?>/relatorios/exportar-excel?<?= http_build_query(array_filter($filtros)) ?>" class="btn btn-success btn-sm">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Exportar Excel
    </a>
    <button type="button" onclick="exportarPDFComGraficos()" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Exportar PDF
    </button>
  </div>
</div>

<!-- Filtros -->
<form method="GET" action="<?= BASE_URL ?>/relatorios" id="formRelatorio">
<div class="filters-bar mb-20">
  <div class="form-group" style="margin:0; min-width:150px;">
    <label class="form-label" style="font-size:10px;">Data Início</label>
    <input type="date" name="data_inicio" class="filter-input" value="<?= e($filtros['data_inicio']) ?>">
  </div>
  <div class="form-group" style="margin:0; min-width:150px;">
    <label class="form-label" style="font-size:10px;">Data Fim</label>
    <input type="date" name="data_fim" class="filter-input" value="<?= e($filtros['data_fim']) ?>">
  </div>
  <?php if (in_array($_SESSION['perfil'], ['supervisor', 'investigador'])): ?>
  <div class="filter-input" style="background:var(--bg-secondary);color:var(--text-muted);cursor:default;display:flex;align-items:center;gap:6px;">
    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
    <?= e($_SESSION['esquadra_nome'] ?? 'A minha esquadra') ?>
  </div>
  <?php else: ?>
  <select name="esquadra" class="filter-input">
    <option value="">Todas as Esquadras</option>
    <?php foreach ($esquadras as $e): ?>
    <option value="<?= $e['id'] ?>" <?= $filtros['esquadra_id'] == $e['id'] ? 'selected' : '' ?>><?= e($e['nome']) ?></option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>
  <select name="tipo" class="filter-input">
    <option value="">Todos os Tipos</option>
    <?php foreach ($tipos as $t): ?>
    <option value="<?= $t['id'] ?>" <?= $filtros['tipo_id'] == $t['id'] ? 'selected' : '' ?>><?= e($t['nome']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="estado" class="filter-input">
    <option value="">Todos os Estados</option>
    <option value="aberta"          <?= $filtros['estado'] === 'aberta' ? 'selected' : '' ?>>Aberta</option>
    <option value="em_investigacao" <?= $filtros['estado'] === 'em_investigacao' ? 'selected' : '' ?>>Em Investigação</option>
    <option value="resolvida"       <?= $filtros['estado'] === 'resolvida' ? 'selected' : '' ?>>Resolvida</option>
    <option value="arquivada"       <?= $filtros['estado'] === 'arquivada' ? 'selected' : '' ?>>Arquivada</option>
  </select>
  <div class="d-flex gap-8">
<?php require ROOT.'/views/partials/filtro_natureza.php'; ?>
    <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
    <button type="button" class="btn btn-ghost btn-sm" onclick="setPeriodo('mes')">Mês Actual</button>
    <button type="button" class="btn btn-ghost btn-sm" onclick="setPeriodo('ano')">Ano Actual</button>
  </div>
</div>
</form>

<!-- Stats Summary -->
<div class="stat-grid mb-20">
  <div class="stat-card stat-blue">
    <div class="stat-icon"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
    <div class="stat-value"><?= number_format($total) ?></div>
    <div class="stat-label">No Período</div>
  </div>
  <div class="stat-card stat-yellow">
    <div class="stat-icon"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
    <div class="stat-value"><?= number_format($stats['abertas']) ?></div>
    <div class="stat-label">Em Aberto</div>
  </div>
  <div class="stat-card stat-blue">
    <div class="stat-icon"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></div>
    <div class="stat-value"><?= number_format($stats['em_investigacao']) ?></div>
    <div class="stat-label">Em Investigação</div>
  </div>
  <div class="stat-card stat-green">
    <div class="stat-icon"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
    <div class="stat-value"><?= number_format($stats['resolvidas']) ?></div>
    <div class="stat-label">Resolvidas</div>
  </div>
</div>

<!-- Charts -->
<div class="grid-2 mb-20">
  <div class="card">
    <div class="card-header"><div class="card-title">Evolução no Período</div></div>
    <div class="chart-container-lg"><canvas id="rChartEvo"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Ocorrências por Tipo</div></div>
    <div class="chart-container-lg"><canvas id="rChartTipo"></canvas></div>
  </div>
</div>

<div class="grid-2 mb-20">
  <div class="card">
    <div class="card-header"><div class="card-title">Por Esquadra / Posto</div></div>
    <div class="chart-container"><canvas id="rChartEsq"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Ocorrências por Bairro (Top 10)</div></div>
    <div class="chart-container">
      <?php if (!empty($porBairro)): ?>
      <canvas id="rChartBairro"></canvas>
      <?php else: ?>
      <div class="empty-state" style="padding:24px;"><div class="empty-state-text">Sem dados</div></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Table -->
<div class="card" style="padding:0;">
  <div class="card-header" style="padding:16px 20px; border-radius:var(--radius) var(--radius) 0 0;">
    <div class="card-title">Listagem Detalhada</div>
    <span style="font-size:12px; color:var(--text-muted);"><?= number_format($total) ?> registos</span>
  </div>
  <?php if (empty($ocorrencias)): ?>
  <div class="empty-state"><div class="empty-state-text">Sem ocorrências no período seleccionado.</div></div>
  <?php else: ?>
  <div class="table-container" style="border:none; border-radius:0 0 var(--radius) var(--radius);">
    <table class="table">
      <thead>
        <tr>
          <th>Nº</th>
          <th>Tipo</th>
          <th>Data</th>
          <th>Bairro</th>
          <th>Esquadra</th>
          <th>Agente</th>
          <th>Prioridade</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $estadoLabels = ['aberta'=>'Aberta','em_investigacao'=>'Em Investigação','resolvida'=>'Resolvida','arquivada'=>'Arquivada'];
        foreach ($ocorrencias as $oc):
        ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/ocorrencias/ver/<?= $oc['id'] ?>" class="table-num"><?= e($oc['numero']) ?></a></td>
          <td><?= e($oc['tipo_nome']) ?><div style="font-size:10px;color:var(--text-muted);margin-top:3px;"><?= e(OcorrenciaService::NATUREZAS[$oc['natureza_dados']]) ?></div></td>
          <td><?= dataOcorrenciaFormatada($oc['data_ocorrencia']) ?></td>
          <td><?= e($oc['bairro'] ?? '—') ?></td>
          <td style="font-size:12px;"><?= e($oc['esquadra_nome']) ?></td>
          <td style="font-size:12px;"><?= e(explode(' ', $oc['agente_nome'])[0] ?? $oc['agente_nome']) ?></td>
          <td><span class="badge badge-<?= $oc['prioridade'] ?>"><?= ucfirst($oc['prioridade']) ?></span></td>
          <td><span class="badge badge-<?= $oc['estado'] ?>"><?= $estadoLabels[$oc['estado']] ?? ucfirst(str_replace('_', ' ', $oc['estado'])) ?></span></td>
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

<script>
Chart.defaults.color = '#94A3B8';
Chart.defaults.borderColor = 'rgba(37,99,235,0.1)';
Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 12;
const COLORS = ['#3B82F6','#22C55E','#FACC15','#EF4444','#A855F7','#F97316','#06B6D4','#EC4899','#14B8A6','#8B5CF6'];

// Evolução
const chartEvo = new Chart(document.getElementById('rChartEvo').getContext('2d'), {
  type: 'line',
  data: {
    labels: <?= json_encode($evoLabels) ?>,
    datasets: [{ label: 'Ocorrências', data: <?= json_encode($evoValues) ?>, borderColor:'#3B82F6', backgroundColor:'rgba(59,130,246,0.08)', fill:true, tension:0.4, borderWidth:2.5, pointRadius:4 }]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, scales:{ x:{ grid:{color:'rgba(37,99,235,0.06)'} }, y:{ grid:{color:'rgba(37,99,235,0.06)'}, beginAtZero:true, ticks:{precision:0} } } }
});

// Por Tipo
const tipoLabels = <?= json_encode($tipoLabels) ?>;
const tipoValues = <?= json_encode($tipoValues) ?>;
const chartTipo = new Chart(document.getElementById('rChartTipo').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: tipoLabels,
    datasets: [{ data: tipoValues, backgroundColor: tipoValues.map((_, i) => COLORS[i % COLORS.length]), borderColor:'#1E293B', borderWidth:2 }]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'right', labels:{ padding:10, usePointStyle:true, font:{size:11} } } } }
});

// Por Esquadra
const chartEsq = new Chart(document.getElementById('rChartEsq').getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($esqLabels) ?>,
    datasets: [{ label:'Ocorrências', data: <?= json_encode($esqValues) ?>, backgroundColor:'rgba(59,130,246,0.6)', borderColor:'#3B82F6', borderWidth:1, borderRadius:4 }]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false}, ticks:{maxRotation:30, font:{size:10}}}, y:{grid:{color:'rgba(37,99,235,0.06)'}, beginAtZero:true, ticks:{precision:0}} } }
});

<?php if (!empty($porBairro)): ?>
// Por Bairro
const chartBairro = new Chart(document.getElementById('rChartBairro').getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_map(fn($r) => $r['bairro'], $porBairro)) ?>,
    datasets: [{ label:'Total', data: <?= json_encode(array_map(fn($r) => $r['total'], $porBairro)) ?>, backgroundColor:'rgba(168,85,247,0.6)', borderColor:'#A855F7', borderWidth:1, borderRadius:4 }]
  },
  options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{grid:{color:'rgba(37,99,235,0.06)'}, beginAtZero:true, ticks:{precision:0}}, y:{grid:{display:false}} } }
});
<?php endif; ?>

// Renderiza uma cópia para papel, mantendo os mesmos dados dos gráficos no ecrã.
function imagemGraficoDocumento(origem, linha = false) {
  const canvas = document.createElement('canvas');
  canvas.width = 1200;
  canvas.height = linha ? 540 : Math.max(540, origem.data.labels.length * 42 + 100);
  const cor = '#<?= DocumentoService::DESTAQUE ?>';
  const grafico = new Chart(canvas, {
    type: linha ? 'line' : 'bar',
    data: {
      labels: [...origem.data.labels],
      datasets: [{ label:'Ocorrências', data:[...origem.data.datasets[0].data],
        backgroundColor:cor, borderColor:cor, borderWidth:2, pointRadius:4, fill:false }]
    },
    plugins:[{ id:'papelBranco', beforeDraw(chart) {
      const ctx = chart.ctx;
      ctx.save(); ctx.fillStyle = '#FFFFFF'; ctx.fillRect(0,0,chart.width,chart.height); ctx.restore();
    }}],
    options: {
      responsive:false, animation:false, devicePixelRatio:1, indexAxis:linha ? 'x' : 'y',
      layout:{padding:12}, plugins:{legend:{display:false}},
      scales:{
        x:{grid:{color:'#E5E7EB'}, ticks:{color:'#25313B',font:{size:28},precision:0,maxRotation:0}, beginAtZero:!linha},
        y:{grid:{display:linha}, ticks:{color:'#25313B',font:{size:28},precision:0}, beginAtZero:linha}
      }
    }
  });
  const imagem = canvas.toDataURL('image/png');
  grafico.destroy();
  return imagem;
}

// Envia apenas as imagens de exportação; os filtros e valores continuam no controller.
function exportarPDFComGraficos() {
  const csrfToken = <?= json_encode(gerarTokenCsrf()) ?>;
  const params = <?= json_encode(array_filter($filtros)) ?>;

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '<?= BASE_URL ?>/relatorios/exportar-pdf?' + new URLSearchParams(params).toString();
  form.target = '_blank';

  const campos = { '_csrf': csrfToken };
  if (typeof chartEvo !== 'undefined')    campos.chart_evolucao = imagemGraficoDocumento(chartEvo,true);
  if (typeof chartTipo !== 'undefined')   campos.chart_tipo     = imagemGraficoDocumento(chartTipo);
  if (typeof chartEsq !== 'undefined')    campos.chart_esquadra = imagemGraficoDocumento(chartEsq);
  if (typeof chartBairro !== 'undefined') campos.chart_bairro   = imagemGraficoDocumento(chartBairro);

  for (const [nome, valor] of Object.entries(campos)) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = nome;
    input.value = valor;
    form.appendChild(input);
  }

  document.body.appendChild(form);
  form.submit();
  form.remove();
}

// Period shortcuts
function setPeriodo(tipo) {
  const now = new Date();
  const dataLocal = data => data.getFullYear() + '-' + String(data.getMonth()+1).padStart(2,'0') + '-' + String(data.getDate()).padStart(2,'0');
  let ini, fim = dataLocal(now);
  if (tipo === 'mes') {
    ini = dataLocal(new Date(now.getFullYear(), now.getMonth(), 1));
  } else {
    ini = dataLocal(new Date(now.getFullYear(), 0, 1));
  }
  document.querySelector('[name=data_inicio]').value = ini;
  document.querySelector('[name=data_fim]').value = fim;
  document.getElementById('formRelatorio').submit();
}
</script>

<?php require ROOT . '/views/layouts/fim.php'; ?>
