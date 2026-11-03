<?php
$parametroPagina = $parametroPagina ?? 'pagina';
$paginaAtual = max(1, (int)($paginaAtual ?? 1));
$totalPaginas = max(1, (int)($totalPaginas ?? 1));
$totalRegistos = max(0, (int)($totalRegistos ?? 0));
if ($totalPaginas > 1):
    $qs = $_GET;
    unset($qs[$parametroPagina]);
?>
<div class="pagination">
  <div class="pagination-info">
    Página <?= $paginaAtual ?> de <?= $totalPaginas ?> (<?= number_format($totalRegistos) ?> registos)
  </div>
  <div class="pagination-pages">
    <?php $qs[$parametroPagina] = max(1, $paginaAtual - 1); ?>
    <a href="?<?= http_build_query($qs) ?>" class="page-link <?= $paginaAtual <= 1 ? 'disabled' : '' ?>" aria-label="Página anterior">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <?php for ($p = max(1, $paginaAtual - 2); $p <= min($totalPaginas, $paginaAtual + 2); $p++): $qs[$parametroPagina] = $p; ?>
    <a href="?<?= http_build_query($qs) ?>" class="page-link <?= $p === $paginaAtual ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php $qs[$parametroPagina] = min($totalPaginas, $paginaAtual + 1); ?>
    <a href="?<?= http_build_query($qs) ?>" class="page-link <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>" aria-label="Página seguinte">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
  </div>
</div>
<?php endif; ?>
