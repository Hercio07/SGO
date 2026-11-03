/* Rascunhos guardados no servidor: nenhum dado da ocorrência fica no localStorage. */
(function () {
  'use strict';
  function iniciar() {
    const cfg = window.RASCUNHO_CONFIG, sessao = window.SESSAO_CONFIG;
    const form = document.getElementById('formOcorrencia') || document.getElementById('formOcorrenciaEdit');
    if (!cfg || !sessao || !form) return;
    const painel = document.getElementById('rascunho-painel');
    const estado = document.getElementById('rascunho-estado');
    const recuperar = document.getElementById('rascunho-recuperar');
    const guardar = document.getElementById('rascunho-guardar');
    const descartar = document.getElementById('rascunho-descartar');
    form.prepend(painel);
    let versao = 0, pendente = null, timer = null, envio = null, pronta = false, bloqueado = false, aSair = false;

    function campos() {
      return Array.from(new FormData(form)).filter(([nome,valor]) => typeof valor === 'string' && nome !== '_csrf' && nome !== 'esquadra_id')
        .map(([nome,valor]) => ({nome,valor}));
    }
    const original = JSON.stringify(campos());
    let ultimoGuardado = original;
    function mensagem(texto) { estado.textContent = texto; }

    async function pedir(acao,dados) {
      const opcoes = {credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Sessao-Id':sessao.identificador}};
      let url = sessao.base + '/rascunhos/' + acao;
      if (dados) {
        opcoes.method = 'POST'; opcoes.headers['X-CSRF-Token'] = sessao.token;
        opcoes.body = new URLSearchParams({contexto:cfg.contexto,...dados});
      } else url += '?contexto=' + encodeURIComponent(cfg.contexto);
      const abortar = new AbortController();
      const temporizador = setTimeout(() => abortar.abort(),12000);
      opcoes.signal = abortar.signal;
      let resposta, resultado;
      try {
        resposta = await fetch(url,opcoes);
        resultado = await resposta.json();
      } catch(e) {
        if (e.name === 'AbortError') throw new Error('A confirmação demorou demasiado. Verifique a ligação e tente novamente.');
        if (e.name === 'SyntaxError') throw new Error('O servidor não confirmou o rascunho. Tente novamente.');
        throw e;
      } finally { clearTimeout(temporizador); }
      if ([401,403,428].includes(resposta.status) || resultado.erro === 'sessao_alterada') {
        aSair = true;
        window.location.replace(resposta.status === 428 ? sessao.base+'/alterar-senha' : sessao.login);
        throw new Error('É necessário iniciar sessão novamente.');
      }
      if (!resposta.ok) {
        if (resposta.status === 409) bloqueado = true;
        throw new Error(resultado.erro || 'Não foi possível confirmar o rascunho.');
      }
      return resultado;
    }

    async function gravar(manual = false) {
      if (!pronta || pendente || bloqueado || aSair || envio) return;
      const texto = JSON.stringify(campos());
      if (!manual && texto === ultimoGuardado) return;
      guardar.disabled = true;
      mensagem('A guardar rascunho privado…');
      envio = pedir('guardar',{dados:texto,versao_rascunho:String(versao)}).then(resultado => {
        versao = Number(resultado.rascunho.versao);
        ultimoGuardado = texto; descartar.hidden = false;
        mensagem('Rascunho privado guardado às ' + new Date().toLocaleTimeString('pt-PT') + '. A ocorrência ainda não foi submetida.');
      }).catch(e => mensagem(e.message + ' O formulário continua disponível nesta página.'))
        .finally(() => { envio = null; guardar.disabled = false; });
      return envio;
    }

    function agendar() {
      clearTimeout(timer);
      if (!pronta || pendente || bloqueado) return;
      if (JSON.stringify(campos()) !== ultimoGuardado) timer = setTimeout(() => gravar(),2000);
    }

    recuperar.addEventListener('click',() => {
      if (!pendente) return;
      if (JSON.stringify(campos()) !== original && !window.confirm('Recuperar o rascunho vai substituir os valores que está a preencher nesta página. Continuar?')) return;
      document.querySelectorAll('#participantesContainer [id^="part_"]').forEach(el => el.remove());
      const indices = [...new Set(pendente.dados.map(c => c.nome.match(/^participantes\[(\d+)\]/)?.[1]).filter(v => v !== undefined))];
      if (typeof adicionarParticipante === 'function' && typeof partCount !== 'undefined') {
        partCount = 0;
        indices.forEach(() => adicionarParticipante());
      }
      form.querySelectorAll('[name="participantes_remover[]"]').forEach(el => {
        el.disabled = true; el.value = '';
        const linha = el.closest('[id^="part_exist_"]');
        if (linha) { linha.style.opacity = ''; linha.style.textDecoration = ''; const botao = linha.querySelector('button'); if (botao) botao.disabled = false; }
      });
      for (const campo of pendente.dados) {
        if (campo.nome === 'participantes_remover[]') {
          if (/^[1-9]\d*$/.test(campo.valor) && typeof removerParticipanteExistente === 'function') removerParticipanteExistente(Number(campo.valor));
          continue;
        }
        const nome = campo.nome.replace(/^participantes\[(\d+)\]/,(_,indice) => 'participantes['+indices.indexOf(indice)+']');
        for (const el of Array.from(form.elements).filter(el => el.name === nome)) {
          if (el.type === 'radio' || el.type === 'checkbox') el.checked = el.value === campo.valor;
          else el.value = campo.valor;
          el.dispatchEvent(new Event('input',{bubbles:true}));
          el.dispatchEvent(new Event('change',{bubbles:true}));
        }
      }
      pendente = null; recuperar.hidden = true; guardar.disabled = false;
      ultimoGuardado = JSON.stringify(campos());
      mensagem('Rascunho recuperado. Reveja os dados, a versão da ocorrência e os anexos antes de submeter.');
    });
    guardar.addEventListener('click',() => gravar(true));
    descartar.addEventListener('click',async () => {
      if (envio || !window.confirm('Descartar o rascunho guardado? Os valores actualmente preenchidos no formulário serão mantidos.')) return;
      try {
        const resultado = await pedir('descartar',{versao_rascunho:String(versao)});
        versao = Number(resultado.versao); pendente = null; bloqueado = false; recuperar.hidden = true; descartar.hidden = true; guardar.disabled = false;
        ultimoGuardado = JSON.stringify(campos());
        mensagem('Rascunho descartado. Novas alterações serão guardadas automaticamente.');
      } catch(e) { mensagem(e.message); }
    });
    ['input','change'].forEach(tipo => form.addEventListener(tipo,agendar));
    form.addEventListener('click',() => setTimeout(agendar,0));
    form.addEventListener('submit',e => { if (!e.defaultPrevented) { aSair = true; clearTimeout(timer); } });
    window.addEventListener('sessao:encerrar',() => { aSair = true; clearTimeout(timer); });
    window.addEventListener('beforeunload',e => {
      if (aSair || JSON.stringify(campos()) === original) return;
      e.preventDefault(); e.returnValue = '';
    });
    pedir('obter').then(resultado => {
      pronta = true;
      versao = Number(resultado.versao || 0);
      const r = resultado.rascunho;
      if (r) {
        versao = Number(r.versao); descartar.hidden = false;
        if (cfg.repost) { mensagem('Os dados submetidos com erros foram mantidos no formulário.'); gravar(true); }
        else { pendente = r; recuperar.hidden = false; guardar.disabled = true; mensagem('Existe um rascunho privado de '+r.updated_at+'. Recupere-o ou descarte-o antes de guardar outro.'); }
      } else mensagem('Rascunho privado: as alterações são guardadas automaticamente após dois segundos.');
    }).catch(e => mensagem(e.message));
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',iniciar);
  else iniciar();
})();
