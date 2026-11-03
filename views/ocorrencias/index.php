<?php require ROOT . '/views/layouts/inicio.php'; ?>
<?php
/** @var array $filtros */
/** @var array $ocorrencias */
/** @var array $tipos */
/** @var array $esquadras */
/** @var array $agentes */
/** @var int $total */
/** @var int $pagina */
/** @var int $totalPgs */
$estadoLabels = [
  'aberta'         => 'Aberta',
  'em_investigacao'=> 'Em Investigação',
  'resolvida'      => 'Resolvida',
  'arquivada'      => 'Arquivada',
];
?>

<div class="breadcrumb mb-20">
  <a href="<?= BASE_URL ?>/dashboard">Início</a>
  <span class="breadcrumb-sep">›</span>
  <span style="color:var(--text-primary);">Ocorrências</span>
</div>

<div class="page-header">
  <div>
    <h1 class="page-title">Gestão de Ocorrências</h1>
    <p class="page-subtitle">
      <?= number_format($total) ?> ocorrência<?= $total !== 1 ? 's' : '' ?> encontrada<?= $total !== 1 ? 's' : '' ?>
    </p>
  </div>
  <div class="d-flex align-center gap-8">
    <?php if ($_SESSION['perfil'] === 'admin'): ?>
    <a href="<?= BASE_URL ?>/relatorios/exportar-excel?<?= http_build_query(array_filter($filtros)) ?>" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Excel
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

<!-- Filters -->
<form method="GET" action="<?= BASE_URL ?>/ocorrencias">
<div class="filters-bar">
  <div class="filter-search">
    <svg class="filter-search-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="q" class="filter-input" placeholder="Pesquisar por número, bairro..." value="<?= e($filtros['pesquisa']) ?>" style="padding-left:32px;">
  </div>

  <select name="tipo" class="filter-input">
    <option value="">Todos os Tipos</option>
    <?php foreach ($tipos as $t): ?>
    <option value="<?= $t['id'] ?>" <?= $filtros['tipo_id'] == $t['id'] ? 'selected' : '' ?>>
      <?= e($t['nome']) ?>
    </option>
    <?php endforeach; ?>
  </select>

  <?php if (in_array($_SESSION['perfil'],['agente','supervisor'],true)): ?>
  <select name="validacao_estado" class="filter-input" aria-label="Validação pelo supervisor">
    <option value="">Todas as Validações</option>
    <?php foreach (OcorrenciaService::VALIDACOES as $valor=>$rotulo): ?>
    <option value="<?= $valor ?>" <?= ($filtros['validacao_estado'] ?? '') === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>
  <?php if ($_SESSION['perfil']!=='agente'): ?>
  <select name="conclusao_estado" class="filter-input" aria-label="Conclusão">
    <option value="">Todas as Conclusões</option>
    <?php foreach(OcorrenciaService::CONCLUSOES as $valor=>$rotulo): ?>
    <option value="<?= $valor ?>" <?= ($filtros['conclusao_estado'] ?? '')===$valor?'selected':'' ?>><?= e($rotulo) ?></option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>
  <select name="estado" class="filter-input">
    <option value="">Todos os Estados</option>
    <?php foreach ($estadoLabels as $val => $lab): ?>
    <option value="<?= $val ?>" <?= $filtros['estado'] === $val ? 'selected' : '' ?>><?= $lab ?></option>
    <?php endforeach; ?>
  </select>

  <select name="prioridade" class="filter-input">
    <option value="">Todas as Prioridades</option>
    <option value="critica"  <?= $filtros['prioridade'] === 'critica' ? 'selected' : '' ?>>Crítica</option>
    <option value="alta"     <?= $filtros['prioridade'] === 'alta' ? 'selected' : '' ?>>Alta</option>
    <option value="media"    <?= $filtros['prioridade'] === 'media' ? 'selected' : '' ?>>Média</option>
    <option value="baixa"    <?= $filtros['prioridade'] === 'baixa' ? 'selected' : '' ?>>Baixa</option>
  </select>

  <?php if ($_SESSION['perfil'] === 'admin'): ?>
  <select name="esquadra" class="filter-input">
    <option value="">Todas as Esquadras</option>
    <?php foreach ($esquadras as $e): ?>
    <option value="<?= $e['id'] ?>" <?= $filtros['esquadra_id'] == $e['id'] ? 'selected' : '' ?>>
      <?= e($e['nome']) ?>
    </option>
    <?php endforeach; ?>
  </select>
  <select name="agente" class="filter-input">
    <option value="">Todos os Agentes</option>
    <?php
    $esquadraAtual = '';
    foreach ($agentes as $ag):
      if ($ag['esquadra_nome'] !== $esquadraAtual):
        if ($esquadraAtual !== '') echo '</optgroup>';
        echo '<optgroup label="' . e($ag['esquadra_nome'] ?? 'Sem esquadra') . '">';
        $esquadraAtual = $ag['esquadra_nome'];
      endif;
    ?>
    <option value="<?= $ag['id'] ?>" <?= ($filtros['agente_id'] ?? '') == $ag['id'] ? 'selected' : '' ?>>
      <?= e($ag['nome']) ?><?= !empty($ag['patente']) ? ' (' . e($ag['patente']) . ')' : '' ?>
    </option>
    <?php endforeach; ?>
    <?php if ($esquadraAtual !== '') echo '</optgroup>'; ?>
  </select>
  <?php endif; ?>

  <div style="display:flex;align-items:center;gap:5px;">
    <span style="font-size:11px;color:var(--text-muted);white-space:nowrap;">De</span>
    <input type="date" name="data_inicio" class="filter-input" value="<?= e($filtros['data_inicio']) ?>">
  </div>
  <div style="display:flex;align-items:center;gap:5px;">
    <span style="font-size:11px;color:var(--text-muted);white-space:nowrap;">Até</span>
    <input type="date" name="data_fim" class="filter-input" value="<?= e($filtros['data_fim']) ?>">
  </div>

<?php require ROOT.'/views/partials/filtro_natureza.php'; ?>
  <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
  <a href="<?= BASE_URL ?>/ocorrencias" class="btn btn-ghost btn-sm">Limpar</a>
</div>
</form>

<!-- Table -->
<div class="card" style="padding:0;">
  <?php if (empty($ocorrencias)): ?>
  <div class="empty-state">
    <div class="empty-state-icon">
      <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </div>
    <div class="empty-state-title">Nenhuma Ocorrência Encontrada</div>
    <div class="empty-state-text">Tente ajustar os filtros ou registe uma nova ocorrência.</div>
  </div>
  <?php else: ?>
  <div class="table-container" style="border:none; border-radius:var(--radius);">
    <table class="table">
      <thead>
        <tr>
          <th>Nº Ocorrência</th>
          <th>Tipo</th>
          <th>Data</th>
          <th>Hora</th>
          <th>Bairro</th>
          <?php if ($_SESSION['perfil'] === 'admin'): ?>
          <th>Esquadra</th>
          <th>Agente</th>
          <?php endif; ?>
          <th>Prioridade</th>
          <th>Estado</th>
          <th>Validação</th>
          <th style="text-align:right;">Acções</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ocorrencias as $oc): ?>
        <tr>
          <td>
            <a href="<?= BASE_URL ?>/ocorrencias/ver/<?= $oc['id'] ?>" class="table-num">
              <?= e($oc['numero']) ?>
            </a>
          </td>
          <td style="color:var(--text-primary);"><?= e($oc['tipo_nome']) ?><div style="font-size:10px;color:var(--text-muted);margin-top:3px;"><?= e(OcorrenciaService::NATUREZAS[$oc['natureza_dados']]) ?></div></td>
          <td><?= dataOcorrenciaFormatada($oc['data_ocorrencia']) ?></td>
          <td><?= horaOcorrenciaFormatada($oc['hora_ocorrencia']) ?></td>
          <td><?= e($oc['bairro'] ?? '—') ?></td>
          <?php if ($_SESSION['perfil'] === 'admin'): ?>
          <td style="font-size:12px; max-width:140px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            <?= e($oc['esquadra_nome']) ?>
          </td>
          <td style="font-size:12px;">
            <?php $np = explode(' ', $oc['agente_nome']); ?>
            <div><?= e(count($np) > 1 ? $np[0] . ' ' . end($np) : $np[0]) ?></div>
            <?php if (!empty($oc['patente'])): ?>
            <div style="font-size:10px; color:var(--text-muted);"><?= e($oc['patente']) ?></div>
            <?php endif; ?>
          </td>
          <?php endif; ?>
          <td><span class="badge badge-<?= $oc['prioridade'] ?>"><?= ucfirst($oc['prioridade']) ?></span></td>
          <td>
            <span class="badge badge-<?= $oc['estado'] ?>"><?= $estadoLabels[$oc['estado']] ?? ucfirst($oc['estado']) ?></span>
            <?php if (($oc['conclusao_estado'] ?? '')==='pendente'): ?><div class="text-muted" style="font-size:11px;margin-top:4px;">Conclusão em análise</div><?php endif; ?>
          </td>
          <td style="text-align:right;">
            <span class="badge badge-<?= e($oc['validacao_estado']) ?>"><?= e(OcorrenciaService::VALIDACOES[$oc['validacao_estado']]) ?></span>
          </td>
          <td style="text-align:right;">
            <div class="d-flex gap-4" style="justify-content:flex-end;">
              <a href="<?= BASE_URL ?>/ocorrencias/ver/<?= $oc['id'] ?>" class="btn btn-ghost btn-sm btn-icon" data-tooltip="Ver Detalhes">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </a>
              <a href="<?= BASE_URL ?>/ocorrencias/auto/<?= $oc['id'] ?>" target="_blank" class="btn btn-ghost btn-sm btn-icon" data-tooltip="Gerar Auto">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              </a>
              <?php if ($_SESSION['perfil'] === 'agente' && OcorrenciaService::podeEditar($oc)): ?>
              <a href="<?= BASE_URL ?>/ocorrencias/editar/<?= $oc['id'] ?>" class="btn btn-ghost btn-sm btn-icon" data-tooltip="Editar">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <?php endif; ?>
              <?php if (in_array($_SESSION['perfil'], ['supervisor', 'investigador']) && OcorrenciaService::podeEditar($oc)): ?>
              <a href="<?= BASE_URL ?>/ocorrencias/editar/<?= $oc['id'] ?>" class="btn btn-ghost btn-sm btn-icon" data-tooltip="Editar">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPgs > 1): ?>
  <div class="pagination">
    <div class="pagination-info">
      Página <?= $pagina ?> de <?= $totalPgs ?> (<?= number_format($total) ?> registos)
    </div>
    <div class="pagination-pages">
      <?php
      // Partir dos parâmetros GET originais (nomes correctos do formulário de filtros)
      // para não perder q, tipo, esquadra, etc. entre páginas
      $qs = $_GET;
      unset($qs['pagina']);

      $qs['pagina'] = max(1, $pagina - 1);
      ?>
      <a href="?<?= http_build_query($qs) ?>" class="page-link <?= $pagina <= 1 ? 'disabled' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      </a>
      <?php for ($p = max(1, $pagina-2); $p <= min($totalPgs, $pagina+2); $p++): ?>
      <?php $qs['pagina'] = $p; ?>
      <a href="?<?= http_build_query($qs) ?>" class="page-link <?= $p === $pagina ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php $qs['pagina'] = min($totalPgs, $pagina + 1); ?>
      <a href="?<?= http_build_query($qs) ?>" class="page-link <?= $pagina >= $totalPgs ? 'disabled' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require ROOT . '/views/layouts/fim.php'; ?>
