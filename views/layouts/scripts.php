<script>const BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/public/assets/js/app.js?v=<?= filemtime(ROOT . '/public/assets/js/app.js') ?>"></script>
<script src="<?= BASE_URL ?>/public/assets/js/validate.js?v=<?= filemtime(ROOT . '/public/assets/js/validate.js') ?>"></script>
<?php if (($paginaAtiva ?? '') === 'dashboard' || ($paginaAtiva ?? '') === 'mapa' || (($paginaAtiva ?? '') === 'ocorrencias' && in_array($vistaAtual ?? '', ['criar','editar']))): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<?php if (($paginaAtiva ?? '') === 'mapa'): ?>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<?php endif; ?>

<?php if (isset($scriptExtra)): ?>
<script src="<?= $scriptExtra ?>"></script>
<?php endif; ?>

<?php require ROOT . '/views/partials/sessao.php'; ?>
<?php require ROOT . '/views/partials/rascunho.php'; ?>
</body>
</html>
