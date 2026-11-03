<?php
$h = static fn($v): string => e((string)$v);
$rotulosFiltros = $rotulosFiltros ?? DocumentoService::filtros($filtros);
$graficosAtivos = [];
foreach (['evolucao'=>'Evolução no período','tipo'=>'Ocorrências por tipo','esquadra'=>'Ocorrências por esquadra','bairro'=>'Bairros com mais ocorrências'] as $chave=>$tituloGrafico) {
    if (!empty($graficos[$chave])) $graficosAtivos[] = ['titulo'=>$tituloGrafico,'img'=>$graficos[$chave]];
}
?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>Relatório de Ocorrências</title>
<?php require ROOT . '/views/documentos/estilos.php'; ?>
</head><body>
<?php require ROOT . '/views/documentos/cabecalho.php'; ?>
<h1 class="doc-title">Relatório de Ocorrências</h1>
<p class="doc-subtitle">Período de <?= dataOcorrenciaFormatada($filtros['data_inicio']) ?> a <?= dataOcorrenciaFormatada($filtros['data_fim']) ?></p>
<table class="doc-meta"><tr>
  <td style="width:50%"><div class="label">Referência</div><?= $h($referencia) ?></td>
  <td style="width:50%"><div class="label">Emissão</div><?= date('d/m/Y H:i') ?></td>
</tr><tr><td colspan="2"><?php $partes=[]; foreach($rotulosFiltros as $rotulo=>$valor) $partes[]='<strong>'.$h($rotulo).':</strong> '.$h($valor); echo implode(' &nbsp; · &nbsp; ', $partes); ?></td></tr></table>
<h2 class="doc-section">Resumo geral</h2>
<table class="report-stats"><tr>
<?php foreach(['total'=>'Total','abertas'=>'Em aberto','em_investigacao'=>'Em investigação','resolvidas'=>'Resolvidas','arquivadas'=>'Arquivadas','criticas'=>'Críticas activas'] as $chave=>$rotulo): ?>
  <td><div class="report-stat-value"><?= number_format((int)$stats[$chave],0,',',' ') ?></div><div class="report-stat-label"><?= $h($rotulo) ?></div></td>
<?php endforeach; ?>
</tr></table>
<?php if($graficosAtivos): ?>
<h2 class="doc-section">Distribuição das ocorrências</h2>
<?php foreach(array_chunk($graficosAtivos,2) as $linha): ?>
<table class="report-charts"><tr>
<?php foreach($linha as $grafico): ?><td><div class="report-chart-title"><?= $h($grafico['titulo']) ?></div><img width="450" style="width:120mm;height:auto;" src="<?= $h($grafico['img']) ?>" alt="<?= $h($grafico['titulo']) ?>"></td><?php endforeach; ?>
<?php if(count($linha)===1): ?><td></td><?php endif; ?>
</tr></table><?php endforeach; ?>
<?php endif; ?>
<h2 class="doc-section<?= $graficosAtivos ? ' report-page' : '' ?>">Listagem detalhada</h2>
<table class="doc-table" autosize="1">
<thead><tr>
  <th style="width:12%">N.º de ocorrência</th><th style="width:14%">Tipo</th><th style="width:10%" class="date">Data</th><th style="width:6%" class="date">Hora</th>
  <th style="width:12%">Bairro</th><th style="width:12%">Esquadra</th><th style="width:15%">Agente</th><th style="width:9%">Prioridade</th><th style="width:10%">Estado</th>
</tr></thead><tbody>
<?php foreach($ocorrencias as $i=>$oc): ?><tr<?= $i%2 ? ' class="shade"' : '' ?>>
  <td><?= $h($oc['numero']) ?><div class="doc-note"><?= $h(OcorrenciaService::NATUREZAS[$oc['natureza_dados']]) ?></div></td><td><?= $h(DocumentoService::texto($oc['tipo_nome']??null)) ?></td>
  <td class="date"><?= dataOcorrenciaFormatada($oc['data_ocorrencia']) ?></td><td class="date"><?= horaOcorrenciaFormatada($oc['hora_ocorrencia']) ?></td>
  <td><?= $h(DocumentoService::texto($oc['bairro']??null)) ?></td><td><?= $h(DocumentoService::texto($oc['esquadra_nome']??null)) ?></td><td><?= $h(DocumentoService::texto($oc['agente_nome']??null)) ?></td>
  <td><?= $h(DocumentoService::PRIORIDADES[$oc['prioridade']]??$oc['prioridade']) ?></td><td><?= $h(DocumentoService::ESTADOS[$oc['estado']]??$oc['estado']) ?></td>
</tr><?php endforeach; ?>
<?php if(!$ocorrencias): ?><tr><td colspan="9" class="doc-empty">Nenhuma ocorrência corresponde aos filtros seleccionados.</td></tr><?php endif; ?>
</tbody></table>
<p class="doc-note">Total da listagem: <?= number_format(count($ocorrencias),0,',',' ') ?> ocorrência(s).</p>
<?php renderView('documentos/assinaturas',['assinaturasDocumento'=>[
  ['nome'=>DocumentoService::emitente(),'legenda'=>'Elaborado por'.(!empty($_SESSION['perfil']) ? ' - '.(DocumentoService::PERFIS[$_SESSION['perfil']]??$_SESSION['perfil']) : '')],
  ['nome'=>'','legenda'=>'Validação'],
]]); ?>
</body></html>
