<?php
$h = static fn($v): string => e(DocumentoService::texto($v));
$tiposParticipante=['vitima'=>'Vítima','suspeito'=>'Suspeito','testemunha'=>'Testemunha','detido'=>'Detido'];
$sexoLabels=['M'=>'Masculino','F'=>'Feminino','outro'=>'Outro'];
?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ficha de Ocorrência <?= $h($ocorrencia['numero']) ?></title>
<?php require ROOT . '/views/documentos/estilos.php'; ?>
</head><body<?= empty($paraPdf) ? ' class="print-body"' : '' ?>>
<?php if(empty($paraPdf)): ?>
<div class="print-toolbar"><span><?= $h($ocorrencia['numero']) ?> — <?= $h(APP_NAME) ?></span>
<button type="button" onclick="window.print()">Imprimir</button><button type="button" onclick="window.close()">Fechar</button></div>
<?php endif; ?>
<div<?= empty($paraPdf) ? ' class="print-page"' : '' ?>>
<?php require ROOT . '/views/documentos/cabecalho.php'; ?>
<?php require ROOT . '/views/documentos/natureza.php'; ?>
<h1 class="doc-title">Ficha de Ocorrência</h1>
<p class="doc-subtitle"><?= $h($ocorrencia['numero']) ?> — <?= $h($ocorrencia['tipo_nome']) ?></p>
<table class="doc-meta"><tr>
<td style="width:50%"><div class="label">Estado</div><?= $h(DocumentoService::ESTADOS[$ocorrencia['estado']]??$ocorrencia['estado']) ?></td>
<td style="width:50%"><div class="label">Prioridade</div><?= $h(DocumentoService::PRIORIDADES[$ocorrencia['prioridade']]??$ocorrencia['prioridade']) ?></td>
</tr></table>

<h2 class="doc-section">Identificação e registo</h2>
<table class="doc-meta">
<tr><td style="width:50%"><div class="label">Esquadra / posto</div><?= $h($ocorrencia['esquadra_nome']) ?></td>
<td style="width:50%"><div class="label">Agente registador</div><?= $h(trim(($ocorrencia['agente_patente']??'').' '.$ocorrencia['agente_nome'])) ?></td></tr>
<tr><td><div class="label">Data da ocorrência</div><?= dataOcorrenciaFormatada($ocorrencia['data_ocorrencia']) ?></td>
<td><div class="label">Hora</div><?= horaOcorrenciaFormatada($ocorrencia['hora_ocorrencia']) ?></td></tr>
<tr><td><div class="label">Data de registo</div><?= date('d/m/Y H:i',strtotime($ocorrencia['created_at'])) ?></td>
<td><div class="label">NIP</div><?= $h($ocorrencia['numero_mecanografico']??null) ?></td></tr>
<tr><td colspan="2"><div class="label">Investigador responsável</div><?= $h($ocorrencia['investigador_nome']??'Aguarda atribuição') ?></td></tr>
</table>

<h2 class="doc-section">Local da ocorrência</h2>
<table class="doc-meta">
<tr><td style="width:50%"><div class="label">Bairro</div><?= $h($ocorrencia['bairro']??null) ?></td>
<td style="width:50%"><div class="label">Rua / avenida</div><?= $h($ocorrencia['rua']??null) ?></td></tr>
<?php if(!empty($ocorrencia['referencia_local'])): ?><tr><td colspan="2"><div class="label">Referência do local</div><?= $h($ocorrencia['referencia_local']) ?></td></tr><?php endif; ?>
<?php if(($ocorrencia['latitude']??null)!==null && ($ocorrencia['latitude']??'')!=='' && ($ocorrencia['longitude']??null)!==null && ($ocorrencia['longitude']??'')!==''): ?>
<tr><td colspan="2"><div class="label">Coordenadas GPS</div><?= $h($ocorrencia['latitude']) ?>, <?= $h($ocorrencia['longitude']) ?></td></tr>
<?php endif; ?>
</table>

<?php if($participantes): ?>
<h2 class="doc-section">Participantes (<?= count($participantes) ?>)</h2>
<table class="doc-table"><thead><tr><th style="width:13%">Tipo</th><th style="width:25%">Nome</th><th style="width:15%">Idade / sexo</th><th style="width:17%">Documento</th><th style="width:15%">Telefone</th><th style="width:15%">Endereço</th></tr></thead><tbody>
<?php foreach($participantes as $p): ?><tr>
<td><?= $h($tiposParticipante[$p['tipo']]??$p['tipo']) ?></td><td><?= $h($p['nome']??null) ?></td>
<td><?= ($p['idade']??null)!==null && $p['idade']!=='' ? $h($p['idade']).' anos' : '—' ?><br><?= $h($sexoLabels[$p['sexo']??'']??null) ?></td>
<td><?= $h($p['documento']??null) ?></td><td><?= $h($p['telefone']??null) ?></td><td><?= $h($p['endereco']??null) ?></td>
</tr><?php endforeach; ?></tbody></table>
<?php endif; ?>

<h2 class="doc-section">Relato dos factos</h2>
<div class="doc-text"><?= $h($ocorrencia['descricao_factos']) ?></div>
<?php foreach(['objetos_recuperados'=>'Objectos recuperados / apreendidos','observacoes'=>'Observações'] as $campo=>$rotulo): if(empty($ocorrencia[$campo])) continue; ?>
<h2 class="doc-section"><?= $h($rotulo) ?></h2>
<div class="doc-text"><?= $h($ocorrencia[$campo]) ?></div>
<?php endforeach; ?>

<h2 class="doc-section">Documentos anexos (<?= count($anexos) ?>)</h2>
<?php if(!$anexos): ?><p class="doc-note">Nenhum documento anexado.</p><?php else: ?>
<table class="doc-table"><thead><tr><th style="width:50%">Nome do ficheiro</th><th style="width:35%">Tipo</th><th style="width:15%" class="number">Tamanho</th></tr></thead><tbody>
<?php foreach($anexos as $a): ?><tr><td><?= $h($a['nome_original']) ?></td><td><?= $h($a['tipo_mime']) ?></td><td class="number"><?= number_format($a['tamanho']/1024,1,',',' ') ?> KB</td></tr><?php endforeach; ?>
</tbody></table><?php endif; ?>

<?php if($historico): ?>
<h2 class="doc-section">Histórico de acções</h2>
<?php foreach($historico as $entrada): ?>
<h3 class="doc-section" style="font-size:9.5pt;border:0;margin-bottom:1.5mm;"><?= $h($entrada['acao']) ?></h3>
<p class="doc-note"><?= date('d/m/Y H:i',strtotime($entrada['created_at'])) ?> — <?= $h($entrada['utilizador_nome']??null) ?></p>
<?php if(!empty($entrada['descricao'])): ?><div class="doc-text" style="font-size:9.5pt;"><?= $h($entrada['descricao']) ?></div><?php endif; ?>
<?php endforeach; endif; ?>

<?php renderView('documentos/assinaturas',['assinaturasDocumento'=>[
  ['nome'=>$ocorrencia['agente_nome'],'legenda'=>'Agente registador','detalhe'=>$ocorrencia['agente_patente']??''],
  ['nome'=>$ocorrencia['investigador_nome']??'','legenda'=>'Investigador responsável'],
]]); ?>
<?php require ROOT . '/views/documentos/rodape.php'; ?>
</div>
<?php if(empty($paraPdf)) require ROOT . '/views/partials/sessao.php'; ?>
</body></html>
