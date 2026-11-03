<?php require ROOT . '/views/layouts/inicio.php'; ?>
<?php
/** @var array $backups */
?>
<div style="padding:24px 28px 32px;max-width:1100px;">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px;">
    <div>
      <h2 style="margin:0 0 6px;font-size:22px;color:var(--text-primary);">Backups do Sistema</h2>
      <p style="margin:0;color:var(--text-secondary);font-size:13px;">ZIP com base de dados, anexos, código, configuração e manifesto de integridade. Retenção local de 30 dias.</p>
    </div>
    <form method="POST" action="<?= BASE_URL ?>/backups/criar" onsubmit="return confirm('Gerar um backup completo agora?');">
      <?= campoCsrf() ?>
      <button type="submit" class="btn btn-primary" style="white-space:nowrap;">Gerar backup agora</button>
    </form>
  </div>

  <p class="text-muted" style="font-size:12px;">Os antigos ficheiros SQL continuam disponíveis e contêm apenas a base de dados. Cópia adicional: <?= env('BACKUP_EXTERNAL_DIR') !== '' ? 'configurada; consulte o resultado da última execução.' : 'aguarda indicação de uma pasta num disco externo ou servidor.' ?></p>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:22px;">
    <?php if ($ultimoResultado): ?><div class="card" style="padding:18px 20px;"><div style="font-size:12px;color:var(--text-secondary);">Última execução — <?= e($ultimoResultado['data']) ?></div><p style="margin-bottom:0;"><?= e($ultimoResultado['estado'] === 'falhado' ? 'Falha no backup. Consulte o log de auditoria.' : 'Backup local verificado.') ?><br><?= e($ultimoResultado['copia_externa']) ?></p></div><?php endif; ?>
    <div class="card" style="padding:18px 20px;">
      <div style="font-size:12px;color:var(--text-secondary);margin-bottom:6px;">Backups disponíveis</div>
      <div style="font-size:24px;font-weight:700;color:var(--text-primary);"><?= count($backups) ?></div>
    </div>
    <div class="card" style="padding:18px 20px;">
      <div style="font-size:12px;color:var(--text-secondary);margin-bottom:6px;">Último backup</div>
      <div style="font-size:15px;font-weight:600;color:var(--text-primary);">
        <?= $backups ? e(date('d/m/Y H:i', $backups[0]['data'])) : 'Ainda não foi gerado' ?>
      </div>
    </div>
  </div>

  <div class="card" style="overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border-color);">
      <h3 style="margin:0;font-size:15px;color:var(--text-primary);">Histórico de cópias</h3>
    </div>
    <?php if (!$backups): ?>
      <div style="padding:32px 20px;text-align:center;color:var(--text-secondary);font-size:13px;">Nenhum backup disponível. O backup diário será gerado quando um administrador aceder ao sistema.</div>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
          <thead>
            <tr style="background:var(--bg-secondary);color:var(--text-secondary);text-align:left;">
              <th style="padding:12px 20px;font-weight:600;">Ficheiro</th>
              <th style="padding:12px 16px;font-weight:600;">Data</th>
              <th style="padding:12px 16px;font-weight:600;">Tamanho</th>
              <th style="padding:12px 20px;text-align:right;font-weight:600;">Acção</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($backups as $backup): ?>
              <tr style="border-top:1px solid var(--border-color);">
                <td style="padding:13px 20px;color:var(--text-primary);font-weight:500;"><?= e($backup['nome']) ?></td>
                <td style="padding:13px 16px;color:var(--text-secondary);white-space:nowrap;"><?= e(date('d/m/Y H:i:s', $backup['data'])) ?></td>
                <td style="padding:13px 16px;color:var(--text-secondary);white-space:nowrap;"><?= number_format($backup['tamanho'] / 1024, 1, ',', '.') ?> KB</td>
                <td style="padding:13px 20px;text-align:right;">
                  <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/backups/download/<?= rawurlencode($backup['nome']) ?>" style="display:inline-block;text-decoration:none;">Descarregar</a>
                  <?php if (str_ends_with($backup['nome'],'.zip')): ?><form method="POST" action="<?= BASE_URL ?>/backups/verificar/<?= rawurlencode($backup['nome']) ?>" style="display:inline;"><?= campoCsrf() ?><button type="submit" class="btn btn-secondary btn-sm">Verificar integridade</button></form><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require ROOT . '/views/layouts/fim.php'; ?>
