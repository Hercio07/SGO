<?php require __DIR__ . '/header.php'; ?>
<?php require __DIR__ . '/sidebar.php'; ?>
  <!-- ══════════════ MAIN ══════════════ -->
  <div class="main-content">

<?php require __DIR__ . '/navbar.php'; ?>
    <!-- PAGE CONTENT -->
    <main class="page-content">

      <?php
      // Toast (session)
      if (!empty($_SESSION['toast'])) {
          $t = $_SESSION['toast'];
          unset($_SESSION['toast']);
          echo '<script>document.addEventListener("DOMContentLoaded",function(){showToast(' . json_encode($t['msg']) . ',' . json_encode($t['tipo'] ?? 'sucesso') . ');});</script>';
      }

      ?>
