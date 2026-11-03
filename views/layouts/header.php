<?php
/** @var string $titulo */
/** @var string $paginaAtiva */
/** @var string $vistaAtual */
/** @var string $scriptExtra */
?>
<!DOCTYPE html>
<html lang="pt" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(gerarTokenCsrf()) ?>">
<title><?= e($titulo ?? 'Dashboard') ?> — <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css?v=<?= filemtime(ROOT . '/public/assets/css/style.css') ?>">
<link rel="icon" type="image/png" href="<?= BASE_URL ?>/public/assets/img/logo.png">
<?php if (in_array(($paginaAtiva ?? ''), ['dashboard', 'relatorios'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php endif; ?>
<?php if (($paginaAtiva ?? '') === 'dashboard' || ($paginaAtiva ?? '') === 'mapa' || (($paginaAtiva ?? '') === 'ocorrencias' && in_array($vistaAtual ?? '', ['criar','editar']))): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<?php endif; ?>
<?php if (($paginaAtiva ?? '') === 'mapa'): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<?php endif; ?>
<style>
.btn-mapa-toggle{display:inline-flex;align-items:center;gap:6px;font-size:12px;padding:5px 12px;height:32px;border-radius:6px;border:1px solid var(--border);background:var(--bg-card);color:var(--text-secondary);cursor:pointer;transition:all .15s;}
.btn-mapa-toggle.active{background:var(--blue-600,#2563EB);color:#fff;border-color:var(--blue-600,#2563EB);}
</style>
<style>
.notif-bell-wrap{position:relative;}
.notif-badge{position:absolute;top:-4px;right:-4px;background:#EF4444;color:#fff;border-radius:10px;min-width:18px;height:18px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;pointer-events:none;border:2px solid var(--bg-card);}
.notif-menu{width:340px;max-height:420px;overflow-y:auto;right:0;left:auto;}
.notif-item{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-bottom:1px solid var(--border-light);cursor:pointer;text-decoration:none;color:inherit;transition:background 0.15s;}
.notif-item:hover{background:var(--bg-hover);}
.notif-item.unread{background:rgba(37,99,235,0.05);}
.notif-item.unread::before{content:'';width:6px;height:6px;background:var(--blue-500);border-radius:50%;flex-shrink:0;margin-top:5px;}
.notif-item:not(.unread)::before{content:'';width:6px;height:6px;flex-shrink:0;}
.notif-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.notif-icon.sucesso{background:rgba(34,197,94,0.12);color:#22C55E;}
.notif-icon.alerta{background:rgba(239,68,68,0.12);color:#EF4444;}
.notif-icon.aviso{background:rgba(245,158,11,0.12);color:#F59E0B;}
.notif-icon.info{background:rgba(59,130,246,0.12);color:#3B82F6;}
.notif-titulo{font-size:12px;font-weight:700;color:var(--text-primary);margin-bottom:2px;}
.notif-msg{font-size:11px;color:var(--text-secondary);line-height:1.4;}
.notif-tempo{font-size:10px;color:var(--text-muted);margin-top:3px;}
#mapaOcorrencias{height:400px;border-radius:8px;}
.leaflet-container{font-family:inherit;}
.leaflet-popup-content-wrapper{background:var(--bg-card);color:var(--text-primary);border:1px solid var(--border);box-shadow:0 4px 20px rgba(0,0,0,0.3);}
.leaflet-popup-tip{background:var(--bg-card);}
</style>
<script>
  /* Apply saved theme before render to prevent flash */
  (function(){
    var t = localStorage.getItem('sigop-theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
  })();
</script>
</head>
<body>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Overlay mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-wrapper">

