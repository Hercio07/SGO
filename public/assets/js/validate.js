(function () {
  'use strict';

  /* ── Padrões disponíveis ─────────────────────────────────────── */
  var RULES = {
    nome: {
      min: 3, max: 100,
      msg: 'Use entre 3 e 100 caracteres'
    },
    phone: {
      re:  /^\+258\s?[0-9]{2}\s?[0-9]{3}\s?[0-9]{4}$/,
      msg: 'Formato obrigatório: +258 84 000 0000'
    },
    bi: {
      re:  /^([0-9]{12}[A-Za-z]|[A-Za-z0-9]{6,15})$/,
      msg: 'Documento inválido — BI: 12 dígitos + 1 letra (ex: 123456789012L), ou Passaporte: 6 a 15 caracteres alfanuméricos'
    },
    mecan: {
      re:  /^(19|20)\d{2}(0[1-9]|1[0-2])\d{2}$/,
      msg: '8 dígitos numéricos no formato AAAAMMSS (ex: 20180347)'
    },
    codigo: {
      re:  /^[A-Z0-9\-]{2,10}$/,
      msg: '2–10 caracteres: maiúsculas, números ou traço'
    },
    bairro: {
      min: 2, max: 80,
      msg: 'Use entre 2 e 80 caracteres'
    }
  };

  /* ── Mostrar / limpar erro num campo ────────────────────────── */
  function setError(input, msg) {
    clearError(input);
    input.classList.add('vld-invalid');
    var span = document.createElement('span');
    span.className = 'vld-msg';
    span.textContent = msg;
    input.parentNode.appendChild(span);
  }

  function setOk(input) {
    clearError(input);
    input.classList.add('vld-valid');
  }

  function clearError(input) {
    input.classList.remove('vld-invalid', 'vld-valid');
    var existing = input.parentNode.querySelector('.vld-msg');
    if (existing) existing.remove();
  }

  /* ── Validar um campo individual ────────────────────────────── */
  function validateField(input) {
    var type = input.dataset.validate;
    var val  = type === 'password' || type === 'password-confirm' ? input.value : input.value.trim();

    // Campo vazio: só erro se obrigatório
    if (!val) {
      if (input.required) { setError(input, 'Campo obrigatório'); return false; }
      clearError(input);
      return true;
    }

    // Email: deixar ao browser (type="email")
    if (type === 'email') {
      if (!input.validity.valid) { setError(input, 'Email inválido'); return false; }
      setOk(input); return true;
    }

    // Senha: só verificar comprimento mínimo
    if (type === 'password') {
      var min = parseInt(input.getAttribute('minlength') || '6', 10);
      if (Array.from(val).length < min) { setError(input, 'Mínimo ' + min + ' caracteres'); return false; }
      setOk(input); return true;
    }

    // Confirmação de senha
    if (type === 'password-confirm') {
      var targetId = input.dataset.matchField;
      var target   = targetId ? document.getElementById(targetId) : null;
      if (target && input.value !== target.value) {
        setError(input, 'As senhas não coincidem'); return false;
      }
      setOk(input); return true;
    }

    // Regras com regex
    var rule = RULES[type];
    if (!rule) { clearError(input); return true; }

    var tamanho = Array.from(val).length;
    var valido = rule.re ? rule.re.test(val) : tamanho >= rule.min && tamanho <= rule.max && !/[\u0000-\u001f\u007f]/.test(val);
    if (!valido) { setError(input, rule.msg); return false; }
    setOk(input); return true;
  }

  /* ── Inicializar um formulário ──────────────────────────────── */
  function initForm(form) {
    var fields = form.querySelectorAll('[data-validate]');

    fields.forEach(function (input) {
      // Valida ao sair do campo
      input.addEventListener('blur', function () { validateField(input); });
      // Relimpa erro enquanto digita (após primeira validação)
      input.addEventListener('input', function () {
        if (input.classList.contains('vld-invalid') || input.classList.contains('vld-valid')) {
          validateField(input);
        }
      });
    });

    form.addEventListener('submit', function (e) {
      var ok = true;
      fields.forEach(function (input) { if (!validateField(input)) ok = false; });
      if (!ok) {
        e.preventDefault();
        var first = form.querySelector('.vld-invalid');
        if (first) first.focus();
      }
    });
  }

  /* ── Auto-inicializar formulários marcados ──────────────────── */
  function init() {
    document.querySelectorAll('[data-vld-form]').forEach(initForm);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // API pública para formulários dinâmicos (modais)
  window.VLD = { initForm: initForm, validateField: validateField };

})();
