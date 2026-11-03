  </div><!-- .main-content -->

</div><!-- .app-wrapper -->

<!-- Formulário único para terminar a sessão com protecção CSRF. -->
<form id="logoutForm" method="POST" action="<?= BASE_URL ?>/logout" hidden>
  <?= campoCsrf() ?>
</form>

<!-- Confirm Modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <div class="modal-title" id="confirmTitle">Confirmar Acção</div>
      <button class="modal-close" onclick="closeModal('confirmModal')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <p id="confirmMessage" style="color:var(--text-secondary); font-size:14px; line-height:1.6;"></p>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('confirmModal')">Cancelar</button>
      <button class="btn btn-danger" id="confirmBtn">Confirmar</button>
    </div>
  </div>
</div>

