<?php
/** @var array $ocorrencia */
/** @var array $participantes */

$tipoPecaTitulos = [
    'auto_noticia'  => 'Auto de Notícia',
    'auto_denuncia' => 'Auto de Denúncia',
    'participacao'  => 'Participação',
    'informacao'    => 'Informação',
];
$tituloAuto = $tipoPecaTitulos[$ocorrencia['tipo_peca'] ?? ''] ?? 'Auto';
$ehNoticia  = ($ocorrencia['tipo_peca'] ?? '') === 'auto_noticia';
$tituloAutoMin = mb_strtolower($tituloAuto, 'UTF-8');

$verboPeca = [
    'auto_denuncia' => 'denuncia',
    'participacao'  => 'participa',
    'informacao'    => 'presta informação',
][$ocorrencia['tipo_peca'] ?? ''] ?? 'declara';

$qualidadeLabels = ['vitima' => 'Vítima', 'suspeito' => 'Suspeito', 'testemunha' => 'Testemunha', 'detido' => 'Detido'];
$sexoExtenso      = ['M' => 'Masculino', 'F' => 'Feminino', 'outro' => 'Outro'];
$estadoCivilLabels = [
    'solteiro' => 'solteiro(a)', 'casado' => 'casado(a)', 'divorciado' => 'divorciado(a)',
    'viuvo' => 'viúvo(a)', 'uniao_de_facto' => 'em união de facto',
];

$mesesPt = [1=>'Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
if (!function_exists('dataExtensoPt')) {
function dataExtensoPt(?string $dados, array $meses): string
{
    if (!$dados) return 'não informada';
    $ts = strtotime($dados);
    return date('d', $ts) . ' de ' . $meses[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
}
}

// Descreve uma pessoa em prosa corrida (usado no Auto de Notícia, ao estilo
// "solteiro de 50 anos de idade, filho de X e de Y, natural de Z, residente
// em W, contactável pelo número N") — só inclui o que estiver preenchido.
if (!function_exists('pessoaProsa')) {
function pessoaProsa(array $p, array $estadoCivilLabels, array $mesesPt): string
{
    $partes = [];
    $idadeTxt = '';
    if (!empty($p['idade'])) $idadeTxt = (int)$p['idade'] . ' anos de idade';
    $estadoTxt = !empty($p['estado_civil']) ? ($estadoCivilLabels[$p['estado_civil']] ?? '') : '';
    if ($estadoTxt && $idadeTxt) $partes[] = $estadoTxt . ' de ' . $idadeTxt;
    elseif ($estadoTxt) $partes[] = $estadoTxt;
    elseif ($idadeTxt) $partes[] = $idadeTxt;

    if (!empty($p['nome_pai']) || !empty($p['nome_mae'])) {
        $partes[] = 'filho de ' . trim(implode(' e de ', array_filter([$p['nome_pai'] ?? null, $p['nome_mae'] ?? null])));
    }
    if (!empty($p['naturalidade'])) $partes[] = 'natural de ' . $p['naturalidade'];
    if (!empty($p['endereco'])) $partes[] = 'residente em ' . $p['endereco'];
    if (!empty($p['telefone'])) $partes[] = 'contactável pelo número ' . $p['telefone'];
    elseif (!empty($p['documento'])) $partes[] = 'portador do documento ' . $p['documento'];

    return trim(implode(', ', $partes));
}
}

$declarante = null;
if (!$ehNoticia && !empty($participantes)) {
    foreach (['vitima', 'testemunha', 'suspeito', 'detido'] as $prioridade) {
        foreach ($participantes as $p) {
            if ($p['tipo'] === $prioridade) { $declarante = $p; break 2; }
        }
    }
}
$outrosParticipantes = $declarante
    ? array_filter($participantes, fn($p) => $p['id'] !== $declarante['id'])
    : $participantes;

$localOcorrencia = array_filter([
    $ocorrencia['bairro'] ?? null,
    !empty($ocorrencia['rua']) ? 'Rua/Av. ' . $ocorrencia['rua'] : null,
    $ocorrencia['referencia_local'] ?? null,
]);
$localTexto = $localOcorrencia ? implode(', ', $localOcorrencia) : 'local não especificado';

$ordinalEsquadra = $ocorrencia['esquadra_nome'];
if (preg_match('/(\d+)/', $ocorrencia['esquadra_nome'], $m)) {
    $ordinalEsquadra = $m[1] . 'ª Esquadra';
}

if (!function_exists('campo')) {
function campo(string $label, string $valor): string
{
    return '<span class="auto-inline-field"><span class="auto-field-label">' . e($label) . ':</span> '
         . '<span class="auto-value">' . e($valor ?: '—') . '</span></span>';
}
}

// Estado civil com concordância de género (M/F) — "Solteiro" vs "Solteira" etc.
if (!function_exists('estadoCivilTexto')) {
function estadoCivilTexto(?string $estado, ?string $sexo): string
{
    if (!$estado) return '';
    $masc = ['solteiro' => 'Solteiro', 'casado' => 'Casado', 'divorciado' => 'Divorciado', 'viuvo' => 'Viúvo', 'uniao_de_facto' => 'em União de Facto'];
    $fem  = ['solteiro' => 'Solteira', 'casado' => 'Casada', 'divorciado' => 'Divorciada', 'viuvo' => 'Viúva', 'uniao_de_facto' => 'em União de Facto'];
    return ($sexo === 'F' ? $fem : $masc)[$estado] ?? ucfirst($estado);
}
}

// "da vítima" / "do suspeito" / "da suspeita" — vítima e testemunha são
// gramaticalmente femininas em português independentemente do sexo da pessoa;
// suspeito/detido concordam com o sexo.
if (!function_exists('qualidadeComArtigo')) {
function qualidadeComArtigo(string $tipo, ?string $sexo): string
{
    $mapa = [
        'vitima'     => ['M' => 'da vítima',    'F' => 'da vítima',    '_' => 'da vítima'],
        'testemunha' => ['M' => 'da testemunha', 'F' => 'da testemunha', '_' => 'da testemunha'],
        'suspeito'   => ['M' => 'do suspeito',  'F' => 'da suspeita',  '_' => 'do(a) suspeito(a)'],
        'detido'     => ['M' => 'do detido',    'F' => 'da detida',    '_' => 'do(a) detido(a)'],
    ];
    $sexoKey = in_array($sexo, ['M', 'F'], true) ? $sexo : '_';
    return $mapa[$tipo][$sexoKey] ?? ('do(a) ' . $tipo);
}
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title><?= mb_strtoupper($tituloAuto, "UTF-8") ?> — <?= e($ocorrencia['numero_auto'] ?? $ocorrencia['numero']) ?></title>
<?php require ROOT . '/views/documentos/estilos.php'; ?>
</head>
<body class="auto-body">

<?php
$cabecalhoFormal = true;
$unidadeDocumento = mb_strtoupper($ordinalEsquadra, 'UTF-8');
require ROOT . '/views/documentos/cabecalho.php';
require ROOT . '/views/documentos/natureza.php';
?>

<?php if ($ehNoticia): ?>
<!-- ===================== AUTO DE NOTÍCIA — narrativa corrida ===================== -->
<div class="auto-title">AUTO DE NOTÍCIA Nº <?= e($ocorrencia['numero_auto'] ?? $ocorrencia['numero']) ?></div>

<?php
  $dataExtensa = dataExtensoPt($ocorrencia['data_ocorrencia'], $mesesPt);
  $horaTxt = horaOcorrenciaFormatada($ocorrencia['hora_ocorrencia']);
  $autorNome = trim(($ocorrencia['agente_patente'] ?? '') . ' ' . $ocorrencia['agente_nome']);
?>
<div class="auto-paragraph">
  Tomamos conhecimento pelas <?= e($horaTxt) ?> horas do dia <?= e($dataExtensa) ?>,
  no bairro <?= e($localTexto) ?>, através de <?= e($autorNome) ?>, dos seguintes factos:
  <?= nl2br(e($ocorrencia['descricao_factos'])) ?>
</div>

<?php if (!empty($participantes)): ?>
<div class="auto-paragraph">
  Foram identificados os seguintes intervenientes:
  <?php foreach ($participantes as $i => $p):
      $prosa = pessoaProsa($p, $estadoCivilLabels, $mesesPt);
      $qualidade = $qualidadeLabels[$p['tipo']] ?? ucfirst($p['tipo']);
  ?>
  <strong><?= e($p['nome'] ?? 'Não identificado') ?></strong><?= $prosa ? ', ' . e($prosa) : '' ?>, na qualidade de <?= e($qualidade) ?><?= $i < count($participantes) - 1 ? '; ' : '.' ?>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="auto-paragraph">
  Para constar lavrou-se o presente Auto de Notícia para os efeitos subsequentes.
</div>

<div class="auto-signature" style="text-align:center;">
  <div class="doc-signature-line" style="display:inline-block; min-width:65mm;">
    <div>O Agente Autuante:</div>
    <div class="auto-signature-name"><?= e($ocorrencia['agente_nome']) ?></div>
    <?php if (!empty($ocorrencia['agente_patente'])): ?>
    <div>(<?= e($ocorrencia['agente_patente']) ?>)</div>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- ===================== Auto de Denúncia / Participação / Informação ===================== -->
<div class="auto-info">
  <?= e($tituloAuto) ?> nº <span class="auto-value"><?= e($ocorrencia['numero_auto'] ?? $ocorrencia['numero']) ?></span>
  <?= campo('Unidade', $ocorrencia['esquadra_nome']) ?>
  <?= campo('Distrito Municipal', $ocorrencia['esquadra_distrito'] ?? '—') ?>
</div>
<div class="auto-info">
  <?= campo('Província', 'Maputo Cidade') ?>
  <?= campo('Zona', 'Urbana') ?>
  <?= campo('Data', dataOcorrenciaFormatada($ocorrencia['data_ocorrencia'])) ?>
</div>
<div class="auto-info">
  <?= campo('Horas', str_replace(':', 'h', horaOcorrenciaFormatada($ocorrencia['hora_ocorrencia']))) ?>
</div>

<div class="auto-classification">
  <div class="texto"><?= e($ocorrencia['tipo_nome']) ?></div>
  <div class="legenda">(Classificação do Crime)</div>
</div>

<?php if ($declarante): ?>
<?php $tratamentoCidadao = $declarante['sexo'] === 'F' ? 'a cidadã' : 'o cidadão'; ?>
<div class="auto-paragraph">
  Nesta subunidade apresentou-se <?= $tratamentoCidadao ?>: <strong><?= e($declarante['nome'] ?? '—') ?></strong>
  <?php if (!empty($declarante['telefone'])): ?>
    <span class="auto-value"><?= e($declarante['telefone']) ?></span>
  <?php elseif (!empty($declarante['documento'])): ?>
    , portador(a) do documento <span class="auto-value"><?= e($declarante['documento']) ?></span>
  <?php endif; ?>
</div>

<?php
  $idadeTexto = !empty($declarante['idade']) ? 'de ' . (int)$declarante['idade'] . ' anos de idade' : '';
  $nascTexto  = !empty($declarante['data_nascimento']) ? 'nascido em ' . dataExtensoPt($declarante['data_nascimento'], $mesesPt) : '';
  $sexoAbrev  = in_array($declarante['sexo'], ['M', 'F'], true) ? $declarante['sexo'] : ($sexoExtenso[$declarante['sexo']] ?? '—');
  $sexoPartes = array_filter([estadoCivilTexto($declarante['estado_civil'] ?? null, $declarante['sexo'] ?? null), $idadeTexto, $nascTexto]);
?>
<div class="auto-field">Sexo <span class="auto-value"><?= e($sexoAbrev . ($sexoPartes ? ', ' . implode(', ', $sexoPartes) : '')) ?></span></div>

<?php if (!empty($declarante['nome_pai']) || !empty($declarante['nome_mae'])): ?>
<div class="auto-field">Filho de <span class="auto-value"><?= e(trim(implode(' e ', array_filter([$declarante['nome_pai'] ?? null, $declarante['nome_mae'] ?? null])))) ?></span></div>
<?php endif; ?>

<div class="auto-field">Natural de <span class="auto-value"><?= e($declarante['naturalidade'] ?? '—') ?></span></div>
<div class="auto-field"><span class="auto-field-label">Nacionalidade:</span> <span class="auto-value"><?= e(DocumentoService::texto($declarante['nacionalidade'] ?? null)) ?></span></div>
<div class="auto-field"><span class="auto-field-label">Profissão:</span> <span class="auto-value"><?= e($declarante['profissao'] ?? '—') ?></span></div>

<?php
  $residencia = trim(($declarante['endereco'] ?? '—')
      . (!empty($declarante['quarteirao']) ? ', Q.' . $declarante['quarteirao'] : '')
      . (!empty($declarante['numero_casa']) ? ', casa nº ' . $declarante['numero_casa'] : ''));
?>
<div class="auto-field">Residente no bairro de <span class="auto-value"><?= e($residencia) ?></span></div>

<div class="auto-field" style="margin-top:10px;">
  Que <?= e($verboPeca) ?> na qualidade de <strong><?= $qualidadeLabels[$declarante['tipo']] ?? ucfirst($declarante['tipo']) ?></strong>.
</div>
<?php else: ?>
<div class="auto-paragraph">
  Nesta subunidade, o(a) <?= e(trim(($ocorrencia['agente_patente'] ?? '') . ' ' . $ocorrencia['agente_nome'])) ?>
  lavra o presente <?= $tituloAutoMin ?>, não tendo sido identificado declarante no momento do registo.
</div>
<?php endif; ?>

<div class="auto-case">
  <table cellpadding="0" cellspacing="0">
    <tr>
      <td class="rotulo">Característica<br>do caso</td>
      <td>
        Data: <?= dataOcorrenciaFormatada($ocorrencia['data_ocorrencia']) ?>. Horas: <?= horaOcorrenciaFormatada($ocorrencia['hora_ocorrencia']) ?>.
        Local: <?= e($localTexto) ?>.
      </td>
    </tr>
  </table>
</div>

<div class="doc-section"><?= $declarante ? 'Declara ou ' . ucfirst($verboPeca) . ':' : 'Relata:' ?></div>
<div class="auto-narrative"><?= e($ocorrencia['descricao_factos']) ?></div>

<div class="auto-paragraph">
<?php if ($declarante): ?>
  Nada mais se disse, lidas as suas declarações as achou conforme, e junto vamos assinar comigo o oficial de permanência
  que lavrei e revi o presente <?= $tituloAutoMin ?>.
<?php else: ?>
  E para constar se lavrou o presente <?= $tituloAutoMin ?>, que assino.
<?php endif; ?>
</div>

<?php if (!empty($outrosParticipantes)): ?>
<div style="margin-top:16px;">
  <div style="font-weight:bold; font-size:10.5px; text-transform:uppercase; margin-bottom:4px; font-family: Helvetica, Arial, sans-serif;">Outros Intervenientes</div>
  <table class="doc-table" cellpadding="0" cellspacing="0">
    <thead><tr><th>Tipo</th><th>Nome</th><th>Documento</th><th>Telefone</th></tr></thead>
    <tbody>
      <?php foreach ($outrosParticipantes as $p): ?>
      <tr>
        <td><?= $qualidadeLabels[$p['tipo']] ?? ucfirst($p['tipo']) ?></td>
        <td><?= e($p['nome'] ?? '—') ?></td>
        <td><?= e($p['documento'] ?? '—') ?></td>
        <td><?= e($p['telefone'] ?? '—') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php renderView('documentos/assinaturas',['assinaturasDocumento'=>[
  ['nome'=>'','legenda'=>'Assinatura '.($declarante ? qualidadeComArtigo($declarante['tipo'],$declarante['sexo']??null) : 'do Agente Autuante')],
  ['nome'=>'','legenda'=>'Assinatura do Oficial'],
]]); ?>
<?php endif; ?>

<div class="doc-footer">SIGOP-PRM — <?= e($ocorrencia['esquadra_nome']) ?> — Documento oficial, uso exclusivo da PRM — Emitido em <?= date('d/m/Y H:i') ?></div>

</body>
</html>
