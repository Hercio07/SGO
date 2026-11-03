<?php // Fecha o conteúdo e carrega os elementos comuns do rodapé. ?>
      <?php if (($_SESSION['perfil'] ?? '') === 'admin'): ?>
      <script>
        (function () {
          var chave = 'sigop-backup-diario-<?= date('Y-m-d') ?>';
          try { if (sessionStorage.getItem(chave)) return; } catch (e) {}
          var token = <?= json_encode(gerarTokenCsrf(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
          fetch(<?= json_encode(BASE_URL . '/backups/verificar-diario', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': token },
            body: new URLSearchParams({ _csrf: token })
          }).then(function (resposta) {
            if (!resposta.ok) return;
            return resposta.json().then(function(resultado) {
              if (resultado.estado === 'criado' || resultado.estado === 'existente') {
                try { sessionStorage.setItem(chave, '1'); } catch (e) {}
              }
            });
          }).catch(function () {});
        })();
      </script>
      <?php endif; ?>

    </main>
<?php require __DIR__ . '/footer.php'; ?>
<?php require __DIR__ . '/scripts.php'; ?>
