<?php require ROOT . '/views/layouts/inicio.php'; ?>
<?php
/** @var array $registos */
/** @var int $total */
/** @var int $totalPaginas */
/** @var int $page */
/** @var int $hoje */
/** @var int|null $semana */
/** @var int|null $uniqueOc */
/** @var int|null $logins */
/** @var int|null $falhas */
/** @var array $esquadras */
/** @var array $utilizadores */
/** @var array $filtros */
/** @var string $tab */
/** @var array|null $esquadraFixada */
// ── Helpers ──────────────────────────────────────────────────────────────
function auditCor(string $acao): string {
    if (stripos($acao, 'Registada')    !== false) return '#10B981';
    if (stripos($acao, 'Aprovada')     !== false) return '#3B82F6';
    if (stripos($acao, 'Rejeitada')    !== false) return '#EF4444';
    if (stripos($acao, 'Estado')       !== false) return '#F59E0B';
    if (stripos($acao, 'Corrigida')    !== false) return '#8B5CF6';
    if (stripos($acao, 'Reesubmetida') !== false) return '#8B5CF6';
    if (stripos($acao, 'Arquivada')    !== false) return '#64748B';
    return '#64748B';
}
function auditBg(string $acao): string {
    if (stripos($acao, 'Registada')    !== false) return 'rgba(16,185,129,.12)';
    if (stripos($acao, 'Aprovada')     !== false) return 'rgba(59,130,246,.12)';
    if (stripos($acao, 'Rejeitada')    !== false) return 'rgba(239,68,68,.12)';
    if (stripos($acao, 'Estado')       !== false) return 'rgba(245,158,11,.12)';
    if (stripos($acao, 'Corrigida')    !== false) return 'rgba(139,92,246,.12)';
    if (stripos($acao, 'Reesubmetida') !== false) return 'rgba(139,92,246,.12)';
    if (stripos($acao, 'Arquivada')    !== false) return 'rgba(100,116,139,.12)';
    return 'rgba(100,116,139,.12)';
}
function acaoCor(string $acao): string {
    if (stripos($acao, 'Backup') !== false || stripos($acao, 'Rotação') !== false) return '#8B5CF6';
    if (stripos($acao, 'Login Falhado')  !== false || stripos($acao, 'Bloqueado') !== false) return '#EF4444';
    if (stripos($acao, 'Login')          !== false) return '#10B981';
    if (stripos($acao, 'Logout')         !== false) return '#64748B';
    if (stripos($acao, 'Criado')         !== false) return '#3B82F6';
    if (stripos($acao, 'Editado')        !== false) return '#F59E0B';
    if (stripos($acao, 'Desactivado')    !== false) return '#EF4444';
    if (stripos($acao, 'Activado')       !== false) return '#10B981';
    if (stripos($acao, 'Redefinida')     !== false) return '#8B5CF6';
    return '#64748B';
}
function acaoBg(string $acao): string {
    $c = acaoCor($acao);
    if ($c === '#EF4444') return 'rgba(239,68,68,.12)';
    if ($c === '#10B981') return 'rgba(16,185,129,.12)';
    if ($c === '#3B82F6') return 'rgba(59,130,246,.12)';
    if ($c === '#F59E0B') return 'rgba(245,158,11,.12)';
    if ($c === '#8B5CF6') return 'rgba(139,92,246,.12)';
    return 'rgba(100,116,139,.12)';
}
function perfilBadge(?string $perfil): string {
    $perfil = $perfil ?? '';
    $map = [
        ''             => ['#64748B', 'rgba(100,116,139,.12)', 'Sistema'],
        'admin'        => ['#F59E0B', 'rgba(245,158,11,.12)', 'Admin'],
        'supervisor'   => ['#3B82F6', 'rgba(59,130,246,.12)', 'Supervisor'],
        'agente'       => ['#10B981', 'rgba(16,185,129,.12)', 'Agente'],
        'investigador' => ['#DB2777', 'rgba(219,39,119,.12)', 'Investigador'],
    ];
    $d = $map[$perfil] ?? ['#64748B', 'rgba(100,116,139,.12)', ucfirst($perfil)];
    return '<span style="background:'.$d[1].';color:'.$d[0].';padding:1px 7px;border-radius:10px;font-size:10px;font-weight:600;border:1px solid '.$d[0].'40;">'.e($d[2]).'</span>';
}
function estadoLabel(string $estado): string {
    return ucfirst(str_replace('_', ' ', $estado));
}
function diaSemanaAudit(DateTime $dt): string {
    $map = ['Sunday'=>'Domingo','Monday'=>'Segunda','Tuesday'=>'Terça','Wednesday'=>'Quarta','Thursday'=>'Quinta','Friday'=>'Sexta','Saturday'=>'Sábado'];
    return $map[$dt->format('l')] ?? $dt->format('l');
}
?>

<div style="padding:24px 28px 32px;">

  <!-- ── Cabeçalho ── -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <div>
      <h1 style="font-size:20px;font-weight:700;color:var(--text-primary);margin:0;">Log de Auditoria</h1>
      <p style="font-size:13px;color:var(--text-secondary);margin:4px 0 0;">
        <?php if ($esquadraFixada): ?>
          Histórico de acções da <strong><?= e($esquadraFixada['nome']) ?></strong>
        <?php else: ?>
          Histórico centralizado de todas as acções realizadas no sistema
        <?php endif; ?>
      </p>
    </div>
    <div style="background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.3);border-radius:8px;padding:8px 14px;display:flex;align-items:center;gap:8px;">
      <svg width="14" height="14" fill="none" stroke="#3B82F6" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <span style="font-size:12px;font-weight:600;color:#3B82F6;"><?= number_format($total) ?> registo<?= $total !== 1 ? 's' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?></span>
    </div>
  </div>

  <!-- ── Tabs ── -->
  <div style="display:flex;gap:4px;margin-bottom:20px;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:4px;width:fit-content;">
    <?php
    $tabItems = [
        'ocorrencias' => ['Acções em Ocorrências', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'acessos'     => ['Acessos & Gestão',      'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
    ];
    foreach ($tabItems as $key => [$label, $icon]):
        $active = ($tab === $key);
    ?>
    <a href="<?= BASE_URL ?>/auditoria?tab=<?= $key ?>"
       style="display:flex;align-items:center;gap:7px;padding:7px 16px;border-radius:7px;font-size:13px;font-weight:<?= $active ? '600' : '500' ?>;text-decoration:none;
              background:<?= $active ? '#3B82F6' : 'transparent' ?>;color:<?= $active ? '#fff' : 'var(--text-secondary)' ?>;transition:all .15s;">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="<?= $icon ?>"/></svg>
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

<?php if ($tab === 'ocorrencias'): ?>

  <!-- ── Stats: Ocorrências ── -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px;">
    <?php
    $statsCards = [
      ['Hoje',                   (int)($hoje     ?? 0), '#3B82F6', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
      ['Esta Semana',            (int)($semana   ?? 0), '#10B981', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
      ['Ocorrências com Hist.',  (int)($uniqueOc ?? 0), '#8B5CF6', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ];
    foreach ($statsCards as [$label, $val, $cor, $path]):
    ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px 18px;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:34px;height:34px;border-radius:8px;background:<?= $cor ?>1a;display:flex;align-items:center;justify-content:center;">
          <svg width="16" height="16" fill="none" stroke="<?= $cor ?>" stroke-width="2" viewBox="0 0 24 24"><path d="<?= $path ?>"/></svg>
        </div>
        <div>
          <div style="font-size:20px;font-weight:700;color:var(--text-primary);"><?= number_format($val) ?></div>
          <div style="font-size:11px;color:var(--text-secondary);"><?= $label ?></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Filtros: Ocorrências ── -->
  <form method="GET" style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:18px 20px;margin-bottom:20px;">
    <input type="hidden" name="tab" value="ocorrencias">
    <div style="display:grid;grid-template-columns:<?= $esquadraFixada ? '1fr 1fr 1fr auto' : '1fr 1fr 1fr 1fr auto' ?>;gap:12px;align-items:end;">
      <div>
        <label style="font-size:11px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Data Início</label>
        <input type="date" name="data_inicio" value="<?= e($filtros['data_inicio']) ?>"
          style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:7px;padding:8px 11px;color:var(--text-primary);font-size:13px;">
      </div>
      <div>
        <label style="font-size:11px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Data Fim</label>
        <input type="date" name="data_fim" value="<?= e($filtros['data_fim']) ?>"
          style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:7px;padding:8px 11px;color:var(--text-primary);font-size:13px;">
      </div>
      <?php if (!$esquadraFixada): ?>
      <div>
        <label style="font-size:11px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Esquadra</label>
        <select name="esquadra_id" style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:7px;padding:8px 11px;color:var(--text-primary);font-size:13px;">
          <option value="">Todas</option>
          <?php foreach ($esquadras as $e): ?>
          <option value="<?= $e['id'] ?>" <?= $filtros['esquadra_id'] == $e['id'] ? 'selected' : '' ?>><?= e($e['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div>
        <label style="font-size:11px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Utilizador</label>
        <select name="utilizador_id" style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:7px;padding:8px 11px;color:var(--text-primary);font-size:13px;">
          <option value="">Todos</option>
          <?php foreach ($utilizadores as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $filtros['utilizador_id'] == $u['id'] ? 'selected' : '' ?>><?= e($u['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:8px;">
        <button type="submit" style="background:#3B82F6;color:#fff;border:none;border-radius:7px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">Filtrar</button>
        <a href="<?= BASE_URL ?>/auditoria?tab=ocorrencias" style="background:var(--bg-input);color:var(--text-secondary);border:1px solid var(--border);border-radius:7px;padding:9px 14px;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;display:inline-block;line-height:1.3;">Limpar</a>
      </div>
    </div>
    <div style="margin-top:12px;position:relative;">
      <svg style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;" width="14" height="14" fill="none" stroke="var(--text-secondary)" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="pesquisa" placeholder="Pesquisar por acção, descrição ou número de ocorrência…"
        value="<?= e($filtros['pesquisa']) ?>"
        style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:7px;padding:9px 11px 9px 34px;color:var(--text-primary);font-size:13px;box-sizing:border-box;">
    </div>
  </form>

  <!-- ── Tabela: Ocorrências ── -->
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;">
    <?php if (empty($registos)): ?>
    <div style="padding:60px 24px;text-align:center;">
      <svg width="44" height="44" fill="none" stroke="var(--text-secondary)" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 14px;opacity:.4;"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      <p style="color:var(--text-secondary);font-size:14px;margin:0;">Nenhum registo encontrado para os filtros seleccionados.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:1px solid var(--border);">
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;white-space:nowrap;">Data / Hora</th>
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;">Utilizador</th>
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;">Acção</th>
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;">Ocorrência</th>
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;">Esquadra</th>
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;">Transição</th>
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;">Detalhe</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $lastDate = '';
          foreach ($registos as $r):
            $dt      = new DateTime($r['created_at']);
            $dataStr = $dt->format('d/m/Y');
            $hora    = $dt->format('H:i');
            $isNew   = ($dataStr !== $lastDate);
            $lastDate = $dataStr;
          ?>
          <?php if ($isNew): ?>
          <tr>
            <td colspan="7" style="padding:8px 16px 4px;background:var(--bg-sidebar);border-bottom:1px solid var(--border);">
              <span style="font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.7px;"><?= diaSemanaAudit($dt) . ', ' . $dataStr ?></span>
            </td>
          </tr>
          <?php endif; ?>
          <tr style="border-bottom:1px solid var(--border);transition:background .15s;" onmouseover="this.style.background='var(--bg-sidebar)'" onmouseout="this.style.background=''">
            <td style="padding:12px 16px;white-space:nowrap;">
              <span style="font-family:monospace;font-size:13px;color:var(--text-primary);font-weight:600;"><?= $hora ?></span>
            </td>
            <td style="padding:12px 16px;">
              <div style="font-size:13px;font-weight:600;color:var(--text-primary);margin-bottom:2px;"><?= e($r['utilizador_nome']) ?></div>
              <div style="display:flex;align-items:center;gap:5px;">
                <?= perfilBadge($r['utilizador_perfil']) ?>
                <?php if (!empty($r['utilizador_patente'])): ?>
                <span style="font-size:10px;color:var(--text-secondary);"><?= e($r['utilizador_patente']) ?></span>
                <?php endif; ?>
              </div>
            </td>
            <td style="padding:12px 16px;white-space:nowrap;">
              <span style="background:<?= auditBg($r['acao']) ?>;color:<?= auditCor($r['acao']) ?>;border:1px solid <?= auditCor($r['acao']) ?>40;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;"><?= e($r['acao']) ?></span>
            </td>
            <td style="padding:12px 16px;white-space:nowrap;">
              <a href="<?= BASE_URL ?>/ocorrencias/ver/<?= $r['ocorrencia_id'] ?>"
                 style="font-family:monospace;font-size:12px;font-weight:700;color:#3B82F6;text-decoration:none;background:rgba(59,130,246,.08);padding:3px 8px;border-radius:5px;border:1px solid rgba(59,130,246,.2);">
                <?= e($r['ocorrencia_numero']) ?>
              </a>
            </td>
            <td style="padding:12px 16px;">
              <span style="font-size:12px;color:var(--text-secondary);"><?= e($r['esquadra_nome']) ?></span>
            </td>
            <td style="padding:12px 16px;white-space:nowrap;">
              <?php if ($r['estado_anterior'] || $r['estado_novo']): ?>
                <?php if ($r['estado_anterior']): ?>
                <span style="font-size:11px;color:var(--text-secondary);background:var(--bg-input);padding:2px 8px;border-radius:5px;"><?= e(estadoLabel($r['estado_anterior'])) ?></span>
                <?php endif; ?>
                <?php if ($r['estado_anterior'] && $r['estado_novo']): ?>
                <span style="color:var(--text-secondary);margin:0 4px;font-size:11px;">&#8594;</span>
                <?php endif; ?>
                <?php if ($r['estado_novo']): ?>
                <span style="font-size:11px;color:#10B981;background:rgba(16,185,129,.1);padding:2px 8px;border-radius:5px;font-weight:600;"><?= e(estadoLabel($r['estado_novo'])) ?></span>
                <?php endif; ?>
              <?php else: ?>
                <span style="color:var(--text-secondary);font-size:11px;">—</span>
              <?php endif; ?>
            </td>
            <td style="padding:12px 16px;max-width:260px;">
              <?php if (!empty($r['descricao'])): ?>
              <details><summary style="font-size:12px;cursor:pointer;">Ver detalhes</summary><div style="font-size:12px;color:var(--text-secondary);white-space:pre-wrap;overflow-wrap:anywhere;padding-top:8px;"><?= e($r['descricao']) ?></div></details>
              <?php else: ?>
              <span style="color:var(--text-secondary);font-size:11px;">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if ($totalPaginas > 1): ?>
    <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <span style="font-size:12px;color:var(--text-secondary);">Página <?= $page ?> de <?= $totalPaginas ?> &nbsp;·&nbsp; <?= number_format($total) ?> registos</span>
      <div style="display:flex;gap:6px;">
        <?php
        $q = array_merge($filtros, ['tab' => 'ocorrencias']);
        for ($p = max(1,$page-2); $p <= min($totalPaginas,$page+4); $p++):
          $q['pagina'] = $p; $isActive = ($p === $page);
        ?>
        <a href="?<?= http_build_query($q) ?>"
           style="padding:6px 11px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;
                  background:<?= $isActive ? '#3B82F6' : 'var(--bg-input)' ?>;
                  color:<?= $isActive ? '#fff' : 'var(--text-secondary)' ?>;
                  border:1px solid <?= $isActive ? '#3B82F6' : 'var(--border)' ?>;"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /tabela ocorrências -->

<?php else: // tab === 'acessos' ?>

  <!-- ── Stats: Acessos ── -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px;">
    <?php
    $statsAcesso = [
      ['Eventos Hoje',    (int)($hoje   ?? 0), '#3B82F6', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
      ['Logins Hoje',     (int)($logins ?? 0), '#10B981', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
      ['Falhas (7 dias)', (int)($falhas ?? 0), '#EF4444', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
    ];
    foreach ($statsAcesso as [$label, $val, $cor, $path]):
    ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px 18px;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:34px;height:34px;border-radius:8px;background:<?= $cor ?>1a;display:flex;align-items:center;justify-content:center;">
          <svg width="16" height="16" fill="none" stroke="<?= $cor ?>" stroke-width="2" viewBox="0 0 24 24"><path d="<?= $path ?>"/></svg>
        </div>
        <div>
          <div style="font-size:20px;font-weight:700;color:var(--text-primary);"><?= number_format($val) ?></div>
          <div style="font-size:11px;color:var(--text-secondary);"><?= $label ?></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Filtros: Acessos ── -->
  <form method="GET" style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:18px 20px;margin-bottom:20px;">
    <input type="hidden" name="tab" value="acessos">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:12px;align-items:end;">
      <div>
        <label style="font-size:11px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Data Início</label>
        <input type="date" name="data_inicio" value="<?= e($filtros['data_inicio']) ?>"
          style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:7px;padding:8px 11px;color:var(--text-primary);font-size:13px;">
      </div>
      <div>
        <label style="font-size:11px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Data Fim</label>
        <input type="date" name="data_fim" value="<?= e($filtros['data_fim']) ?>"
          style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:7px;padding:8px 11px;color:var(--text-primary);font-size:13px;">
      </div>
      <div>
        <label style="font-size:11px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Utilizador</label>
        <select name="utilizador_id" style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:7px;padding:8px 11px;color:var(--text-primary);font-size:13px;">
          <option value="">Todos</option>
          <?php foreach ($utilizadores as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $filtros['utilizador_id'] == $u['id'] ? 'selected' : '' ?>><?= e($u['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:11px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Categoria</label>
        <select name="tipo" style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:7px;padding:8px 11px;color:var(--text-primary);font-size:13px;">
          <option value="">Todas</option>
          <option value="acesso" <?= ($filtros['tipo'] ?? '') === 'acesso' ? 'selected' : '' ?>>Acessos (Login/Logout)</option>
          <option value="gestao" <?= ($filtros['tipo'] ?? '') === 'gestao' ? 'selected' : '' ?>>Gestão de Utilizadores</option>
        </select>
      </div>
      <div style="display:flex;gap:8px;">
        <button type="submit" style="background:#3B82F6;color:#fff;border:none;border-radius:7px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">Filtrar</button>
        <a href="<?= BASE_URL ?>/auditoria?tab=acessos" style="background:var(--bg-input);color:var(--text-secondary);border:1px solid var(--border);border-radius:7px;padding:9px 14px;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;display:inline-block;line-height:1.3;">Limpar</a>
      </div>
    </div>
    <div style="margin-top:12px;position:relative;">
      <svg style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;" width="14" height="14" fill="none" stroke="var(--text-secondary)" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="pesquisa" placeholder="Pesquisar por acção, utilizador, IP…"
        value="<?= e($filtros['pesquisa']) ?>"
        style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:7px;padding:9px 11px 9px 34px;color:var(--text-primary);font-size:13px;box-sizing:border-box;">
    </div>
  </form>

  <!-- ── Tabela: Acessos ── -->
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;">
    <?php if (empty($registos)): ?>
    <div style="padding:60px 24px;text-align:center;">
      <svg width="44" height="44" fill="none" stroke="var(--text-secondary)" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 14px;opacity:.4;"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <p style="color:var(--text-secondary);font-size:14px;margin:0;">Nenhum registo encontrado. Os eventos serão registados a partir de agora.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:1px solid var(--border);">
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;white-space:nowrap;">Data / Hora</th>
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;">Utilizador</th>
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;">Evento</th>
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;">Categoria</th>
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;">IP</th>
            <th style="padding:11px 16px;text-align:left;font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px;">Detalhe</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $lastDate = '';
          foreach ($registos as $r):
            $dt      = new DateTime($r['created_at']);
            $dataStr = $dt->format('d/m/Y');
            $hora    = $dt->format('H:i:s');
            $isNew   = ($dataStr !== $lastDate);
            $lastDate = $dataStr;
          ?>
          <?php if ($isNew): ?>
          <tr>
            <td colspan="6" style="padding:8px 16px 4px;background:var(--bg-sidebar);border-bottom:1px solid var(--border);">
              <span style="font-size:10px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.7px;"><?= diaSemanaAudit($dt) . ', ' . $dataStr ?></span>
            </td>
          </tr>
          <?php endif; ?>
          <tr style="border-bottom:1px solid var(--border);transition:background .15s;" onmouseover="this.style.background='var(--bg-sidebar)'" onmouseout="this.style.background=''">
            <td style="padding:12px 16px;white-space:nowrap;">
              <span style="font-family:monospace;font-size:13px;color:var(--text-primary);font-weight:600;"><?= $hora ?></span>
            </td>
            <td style="padding:12px 16px;">
              <?php if ($r['utilizador_nome']): ?>
              <div style="font-size:13px;font-weight:600;color:var(--text-primary);margin-bottom:2px;"><?= e($r['utilizador_nome']) ?></div>
              <?php if (!empty($r['utilizador_perfil'])): ?><?= perfilBadge($r['utilizador_perfil']) ?><?php endif; ?>
              <?php else: ?>
              <div style="font-size:13px;font-weight:600;color:#EF4444;"><?= e($r['identificador_tentativa'] ?? '—') ?></div>
              <span style="font-size:10px;color:#EF4444;background:rgba(239,68,68,.1);padding:1px 7px;border-radius:10px;border:1px solid rgba(239,68,68,.3);">Não identificado</span>
              <?php endif; ?>
            </td>
            <td style="padding:12px 16px;white-space:nowrap;">
              <span style="background:<?= acaoBg($r['acao']) ?>;color:<?= acaoCor($r['acao']) ?>;border:1px solid <?= acaoCor($r['acao']) ?>40;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;"><?= e($r['acao']) ?></span>
            </td>
            <td style="padding:12px 16px;">
              <?php if ($r['tipo'] === 'gestao'): ?>
              <span style="font-size:11px;color:#8B5CF6;background:rgba(139,92,246,.1);padding:2px 8px;border-radius:5px;border:1px solid rgba(139,92,246,.3);">Gestão</span>
              <?php else: ?>
              <span style="font-size:11px;color:#64748B;background:var(--bg-input);padding:2px 8px;border-radius:5px;border:1px solid var(--border);">Acesso</span>
              <?php endif; ?>
            </td>
            <td style="padding:12px 16px;white-space:nowrap;">
              <span style="font-family:monospace;font-size:12px;color:var(--text-secondary);"><?= e($r['ip'] ?? '—') ?></span>
            </td>
            <td style="padding:12px 16px;max-width:260px;">
              <?php if (!empty($r['descricao'])): ?>
              <details><summary style="font-size:12px;cursor:pointer;">Ver detalhes</summary><div style="font-size:12px;color:var(--text-secondary);white-space:pre-wrap;overflow-wrap:anywhere;padding-top:8px;"><?= e($r['descricao']) ?></div></details>
              <?php else: ?>
              <span style="color:var(--text-secondary);font-size:11px;">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if ($totalPaginas > 1): ?>
    <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <span style="font-size:12px;color:var(--text-secondary);">Página <?= $page ?> de <?= $totalPaginas ?> &nbsp;·&nbsp; <?= number_format($total) ?> registos</span>
      <div style="display:flex;gap:6px;">
        <?php
        $q = array_merge($filtros, ['tab' => 'acessos']);
        for ($p = max(1,$page-2); $p <= min($totalPaginas,$page+4); $p++):
          $q['pagina'] = $p; $isActive = ($p === $page);
        ?>
        <a href="?<?= http_build_query($q) ?>"
           style="padding:6px 11px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;
                  background:<?= $isActive ? '#3B82F6' : 'var(--bg-input)' ?>;
                  color:<?= $isActive ? '#fff' : 'var(--text-secondary)' ?>;
                  border:1px solid <?= $isActive ? '#3B82F6' : 'var(--border)' ?>;"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /tabela acessos -->

<?php endif; ?>
</div>

<?php require ROOT . '/views/layouts/fim.php'; ?>
