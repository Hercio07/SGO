<?php require ROOT . '/views/layouts/inicio.php'; ?>
<?php
/** @var array $ocorrencia */
/** @var array $participantes */
/** @var array $historico */
/** @var array $anexos */
/** @var array $investigadoresEsquadra */
$estadoLabels = [
  'aberta'         => 'Aberta',
  'em_investigacao'=> 'Em Investigação',
  'resolvida'      => 'Resolvida',
  'arquivada'      => 'Arquivada',
];
$prioridadeLabels = [
  'baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta', 'critica' => 'Crítica'
];
?>

<div class="breadcrumb mb-20">
  <a href="<?= BASE_URL ?>/dashboard">Início</a>
  <span class="breadcrumb-sep">›</span>
  <a href="<?= BASE_URL ?>/ocorrencias">Ocorrências</a>
  <span class="breadcrumb-sep">›</span>
  <span style="color:var(--text-primary);"><?= e($ocorrencia['numero']) ?></span>
</div>

<div class="page-header">
  <div>
    <div class="d-flex align-center gap-12 mb-4" style="flex-wrap:wrap;">
      <h1 class="page-title" style="font-family:monospace; letter-spacing:1px;"><?= e($ocorrencia['numero']) ?></h1>
      <span class="badge badge-<?= $ocorrencia['prioridade'] ?>"><?= $prioridadeLabels[$ocorrencia['prioridade']] ?? ucfirst($ocorrencia['prioridade']) ?></span>
      <span class="badge badge-<?= $ocorrencia['estado'] ?>"><?= $estadoLabels[$ocorrencia['estado']] ?? ucfirst(str_replace('_', ' ', $ocorrencia['estado'])) ?></span>
      <span class="badge badge-<?= e($ocorrencia['validacao_estado']) ?>"><?= e(OcorrenciaService::VALIDACOES[$ocorrencia['validacao_estado']]) ?></span>
    </div>
    <p class="page-subtitle"><?= e($ocorrencia['tipo_nome']) ?> — Data: <?= dataOcorrenciaFormatada($ocorrencia['data_ocorrencia']) ?> · Hora: <?= horaOcorrenciaFormatada($ocorrencia['hora_ocorrencia']) ?></p>
  </div>
  <div class="d-flex align-center gap-8">
    <?php if (OcorrenciaService::podeEditar($ocorrencia)): ?>
    <a href="<?= BASE_URL ?>/ocorrencias/editar/<?= $ocorrencia['id'] ?>" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Editar
    </a>
    <?php endif; ?>
    <?php if (in_array($_SESSION['perfil'], ['supervisor', 'investigador']) && OcorrenciaService::podeEditar($ocorrencia)): ?>
    <a href="<?= BASE_URL ?>/ocorrencias/editar/<?= $ocorrencia['id'] ?>" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Editar
    </a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/ocorrencias/imprimir/<?= $ocorrencia['id'] ?>" target="_blank" class="btn btn-secondary btn-sm no-print">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Imprimir
    </a>
    <a href="<?= BASE_URL ?>/ocorrencias/auto/<?= $ocorrencia['id'] ?>" target="_blank" class="btn btn-secondary btn-sm no-print">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Gerar Auto
    </a>
    <a href="<?= BASE_URL ?>/ocorrencias" class="btn btn-ghost btn-sm no-print">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      Voltar
    </a>
  </div>
</div>

<div class="card mb-20" id="validacao">
  <div class="card-header"><div class="card-title">Validação pelo Supervisor</div></div>
  <?php if ($ocorrencia['validacao_estado'] === 'rejeitada'): ?>
  <p><strong>Motivo da rejeição:</strong> <?= nl2br(e($ocorrencia['motivo_rejeicao'])) ?></p>
  <?php if ($_SESSION['perfil'] === 'agente'): ?>
  <p class="text-muted">Edite a ocorrência para corrigir os dados. Ao guardar, será submetida novamente para aprovação.</p>
  <?php endif; ?>
  <?php elseif ($ocorrencia['validacao_estado'] === 'aprovada'): ?>
  <p>Ocorrência aprovada e disponível para consulta pelo Comando da Cidade.</p>
  <?php else: ?>
  <p>A ocorrência aguarda análise e aprovação pelo supervisor da esquadra.</p>
  <?php endif; ?>
  <?php if (!empty($ocorrencia['data_validacao'])): ?>
  <p class="text-muted">Decisão registada em <?= date('d/m/Y H:i',strtotime($ocorrencia['data_validacao'])) ?>.</p>
  <?php endif; ?>
  <?php if ($_SESSION['perfil'] === 'supervisor' && $ocorrencia['validacao_estado'] === 'pendente' && $ocorrencia['estado'] !== 'arquivada'): ?>
  <div class="no-print">
    <form method="POST" action="<?= BASE_URL ?>/ocorrencias/aprovar/<?= (int)$ocorrencia['id'] ?>" class="mb-16">
      <?= campoCsrf() ?><input type="hidden" name="versao" value="<?= (int)$ocorrencia['versao'] ?>">
      <button type="submit" class="btn btn-primary">Aprovar e Enviar ao Comando</button>
    </form>
    <form method="POST" action="<?= BASE_URL ?>/ocorrencias/rejeitar/<?= (int)$ocorrencia['id'] ?>">
      <?= campoCsrf() ?><input type="hidden" name="versao" value="<?= (int)$ocorrencia['versao'] ?>">
      <label class="form-label" for="motivoRejeicao">Motivo da rejeição</label>
      <textarea id="motivoRejeicao" name="motivo_rejeicao" class="form-control mb-12" required rows="3" placeholder="Indique os dados que o agente deve corrigir."></textarea>
      <button type="submit" class="btn btn-danger">Rejeitar e Devolver ao Agente</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<style>
@media print {
  .no-print, .sidebar, nav, .notif-bell-wrap, .breadcrumb, .page-header .d-flex { display: none !important; }
  .main-content { margin: 0 !important; padding: 0 !important; }
  .card { border: 1px solid #ccc !important; box-shadow: none !important; page-break-inside: avoid; }
  body { background: white !important; color: #000 !important; }
  .badge { border: 1px solid #999; color: #000 !important; background: #eee !important; }
  .grid-3-1 { display: block !important; }
  a { color: #000 !important; }
  .page-title { font-size: 20px !important; }
}
</style>

<div class="grid-3-1">

  <!-- Coluna Principal -->
  <div>

    <!-- Dados Gerais -->
    <div class="card mb-20">
      <div class="card-header">
        <div class="card-title">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Dados Gerais
        </div>
      </div>
      <div class="detail-grid">
        <div class="detail-field">
          <div class="detail-label">Número</div>
          <div class="detail-value" style="font-family:monospace; color:var(--blue-400); font-weight:700;"><?= e($ocorrencia['numero']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Tipo de Crime</div>
          <div class="detail-value"><?= e($ocorrencia['tipo_nome']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Data da Ocorrência</div>
          <div class="detail-value"><?= dataOcorrenciaFormatada($ocorrencia['data_ocorrencia']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Hora</div>
          <div class="detail-value"><?= horaOcorrenciaFormatada($ocorrencia['hora_ocorrencia']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Esquadra / Posto</div>
          <div class="detail-value"><?= e($ocorrencia['esquadra_nome']) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Agente Responsável</div>
          <div class="detail-value"><?= e($ocorrencia['agente_nome']) ?></div>
          <?php if (!empty($ocorrencia['agente_patente'])): ?>
          <div style="font-size:11px; color:var(--text-muted);"><?= e($ocorrencia['agente_patente']) ?></div>
          <?php endif; ?>
        </div>
        <div class="detail-field">
          <div class="detail-label">Registado em</div>
          <div class="detail-value"><?= date('d/m/Y H:i', strtotime($ocorrencia['created_at'])) ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Última Actualização</div>
          <div class="detail-value"><?= date('d/m/Y H:i', strtotime($ocorrencia['updated_at'])) ?></div>
        </div>
      </div>
    </div>

    <!-- Local -->
    <div class="card mb-20">
      <div class="card-header">
        <div class="card-title">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
          Local da Ocorrência
        </div>
      </div>
      <div class="detail-grid">
        <div class="detail-field">
          <div class="detail-label">Bairro</div>
          <div class="detail-value"><?= e($ocorrencia['bairro'] ?? '—') ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Rua / Avenida</div>
          <div class="detail-value"><?= e($ocorrencia['rua'] ?? '—') ?></div>
        </div>
        <div class="detail-field">
          <div class="detail-label">Referência do Local</div>
          <div class="detail-value"><?= e($ocorrencia['referencia_local'] ?? '—') ?></div>
        </div>
      </div>
    </div>

    <!-- Descrição -->
    <div class="card mb-20">
      <div class="card-header">
        <div class="card-title">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>
          Descrição dos Factos
        </div>
        <?php
          $tipoPecaLabels = ['auto_noticia'=>'Auto de Notícia','auto_denuncia'=>'Auto de Denúncia','participacao'=>'Participação','informacao'=>'Informação'];
          $tipoPecaLbl = $tipoPecaLabels[$ocorrencia['tipo_peca'] ?? ''] ?? null;
        ?>
        <?php if ($tipoPecaLbl): ?>
        <span class="badge" style="font-size:10px;"><?= $tipoPecaLbl ?></span>
        <?php endif; ?>
      </div>
      <div style="font-size:14px; line-height:1.8; color:var(--text-secondary); white-space:pre-wrap;">
        <?= e($ocorrencia['descricao_factos']) ?>
      </div>

      <?php if (!empty($ocorrencia['objetos_recuperados'])): ?>
      <div class="divider"></div>
      <div class="detail-section-title" style="margin-top:0;">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        Objectos Recuperados / Apreendidos
      </div>
      <div style="font-size:14px; line-height:1.8; color:var(--text-secondary);"><?= nl2br(e($ocorrencia['objetos_recuperados'])) ?></div>
      <?php endif; ?>

      <?php if (!empty($ocorrencia['observacoes'])): ?>
      <div class="divider"></div>
      <div class="detail-section-title" style="margin-top:0;">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Observações
      </div>
      <div style="font-size:14px; line-height:1.8; color:var(--text-secondary);"><?= nl2br(e($ocorrencia['observacoes'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Participantes -->
    <?php if (!empty($participantes)): ?>
    <div class="card mb-20">
      <div class="card-header">
        <div class="card-title">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
          Participantes
        </div>
        <span style="font-size:12px; color:var(--text-muted);"><?= count($participantes) ?> registado<?= count($participantes) > 1 ? 's' : '' ?></span>
      </div>
      <div class="table-container" style="border:none;">
        <table class="table">
          <thead><tr><th>Tipo</th><th>Nome</th><th>Documento</th><th>Telefone</th></tr></thead>
          <tbody>
            <?php foreach ($participantes as $p): ?>
            <tr>
              <td>
                <?php
                $tipoLabelMap = ['vitima'=>'Vítima','suspeito'=>'Suspeito','testemunha'=>'Testemunha','detido'=>'Detido'];
                $tipoLabel = isset($tipoLabelMap[$p['tipo']]) ? $tipoLabelMap[$p['tipo']] : ucfirst($p['tipo']);
                ?>
                <span class="badge badge-<?= $p['tipo'] === 'vitima' ? 'alta' : ($p['tipo'] === 'suspeito' ? 'critica' : 'media') ?>"><?= $tipoLabel ?></span>
              </td>
              <td style="color:var(--text-primary);"><?= e($p['nome'] ?? '—') ?></td>
              <td><?= e($p['documento'] ?? '—') ?></td>
              <td><?= e($p['telefone'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Anexos -->
    <?php if (!empty($anexos)): ?>
    <div class="card mb-20">
      <div class="card-header">
        <div class="card-title">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
          Documentos e Evidências
        </div>
        <span style="font-size:12px; color:var(--text-muted);"><?= count($anexos) ?> ficheiro<?= count($anexos) > 1 ? 's' : '' ?></span>
      </div>
      <?php foreach ($anexos as $anx):
        $podeEliminar = OcorrenciaService::podeEditar($ocorrencia) && ($_SESSION['perfil'] === 'admin'
            || (in_array($_SESSION['perfil'], ['supervisor', 'investigador']) && (int)$ocorrencia['esquadra_id'] === (int)$_SESSION['esquadra_id'])
            || ($_SESSION['perfil'] === 'agente' && (int)$anx['uploader_id'] === (int)$_SESSION['user_id']));
      ?>
      <div class="d-flex align-center gap-12" style="padding:10px 0; border-bottom:1px solid var(--border-light);">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--blue-400); flex-shrink:0;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <div style="flex:1;">
          <div style="font-size:13px; color:var(--text-primary);"><?= e($anx['nome_original']) ?></div>
          <div style="font-size:11px; color:var(--text-muted);"><?= number_format($anx['tamanho']/1024, 0) ?> KB — <?= date('d/m/Y H:i', strtotime($anx['created_at'])) ?></div>
        </div>
        <a href="<?= BASE_URL ?>/ocorrencias/anexo/<?= (int)$anx['id'] ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">Abrir</a>
        <?php if ($podeEliminar): ?>
        <form method="POST" action="<?= BASE_URL ?>/ocorrencias/anexo-excluir/<?= $anx['id'] ?>" class="no-print"
              onsubmit="return confirm('Eliminar o ficheiro ' + <?= e(json_encode('"' . $anx['nome_original'] . '"')) ?> + '?');">
          <?= campoCsrf() ?>
          <input type="hidden" name="versao" value="<?= (int)$ocorrencia['versao'] ?>">
          <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Eliminar ficheiro">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
          </button>
        </form>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>

  <!-- Coluna Lateral -->
  <div>

    <div class="card mb-16"><div class="card-header"><div class="card-title">Natureza dos dados</div></div>
      <p><strong><?= e(OcorrenciaService::NATUREZAS[$ocorrencia['natureza_dados']]) ?></strong></p>
      <p style="font-size:12px;"><?= e($ocorrencia['fonte_informacao'] ?: 'Fonte não preenchida neste campo. Consulte o relato e as observações.') ?></p>
      <p class="text-muted" style="font-size:12px;">A classificação é apresentada nos documentos e pode ser filtrada nos relatórios.</p>
    </div>
    <?php require ROOT.'/views/ocorrencias/investigacao.php'; ?>

    <!-- Linha Temporal -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Linha Temporal
        </div>
      </div>
      <div class="timeline" style="margin-top:8px;">
        <?php if (empty($historico)): ?>
        <div style="color:var(--text-muted); font-size:13px;">Sem histórico disponível.</div>
        <?php else: ?>
        <?php foreach ($historico as $h): ?>
        <div class="timeline-item">
          <div class="timeline-dot"></div>
          <div class="timeline-time"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></div>
          <div class="timeline-title"><?= e($h['acao']) ?></div>
          <?php if (!empty($h['descricao'])): ?>
          <div class="timeline-body"><?= nl2br(e($h['descricao'])) ?></div>
          <?php endif; ?>
          <?php if (!empty($h['estado_anterior']) && !empty($h['estado_novo'])): ?>
          <div style="margin-top:4px; font-size:11px;">
            <span class="badge badge-<?= $h['estado_anterior'] ?>" style="font-size:10px;"><?= $estadoLabels[$h['estado_anterior']] ?? $h['estado_anterior'] ?></span>
            <span style="color:var(--text-muted); margin:0 4px;">→</span>
            <span class="badge badge-<?= $h['estado_novo'] ?>" style="font-size:10px;"><?= $estadoLabels[$h['estado_novo']] ?? $h['estado_novo'] ?></span>
          </div>
          <?php endif; ?>
          <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">por <?= e($h['utilizador_nome']) ?></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

<?php require ROOT . '/views/layouts/fim.php'; ?>
