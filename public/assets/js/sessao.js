/* Inactividade: só as interacções do utilizador renovam o prazo no servidor. */
(function () {
  'use strict';
  const cfg = window.SESSAO_CONFIG;
  if (!cfg) return;
  const aviso = document.getElementById('sessao-aviso');
  const tempo = document.getElementById('sessao-tempo');
  const continuar = document.getElementById('sessao-continuar');
  const erro = document.getElementById('sessao-erro');
  const chave = 'sigop-sessao-' + cfg.identificador;
  let prazo = Date.now() + cfg.restante * 1000;
  let ultimaAcao = Date.now() - (cfg.limite - cfg.restante) * 1000;
  let ultimaEnviada = ultimaAcao;
  let ultimaConfirmada = cfg.atividade;
  let ultimoEnvio = 0;
  let envio = null;
  let consulta = null;
  let agendamento = null;
  let encerrada = false;
  let focoAnterior = null;

  function publicar(dados) {
    try { localStorage.setItem(chave, JSON.stringify(dados)); } catch (_) {}
  }

  function encerrar(inatividade, comunicar = true) {
    if (encerrada) return;
    encerrada = true;
    clearTimeout(agendamento);
    if (comunicar) publicar({ encerrada:true, inatividade });
    window.dispatchEvent(new Event('sessao:encerrar'));
    document.body.style.visibility = 'hidden';
    window.location.replace(inatividade ? cfg.loginInatividade : cfg.login);
  }

  function esconderAviso() {
    if (aviso.hidden) return;
    aviso.hidden = true;
    document.body.classList.remove('sessao-em-aviso');
    erro.textContent = '';
    if (focoAnterior && focoAnterior.isConnected) focoAnterior.focus();
  }

  function aplicarEstado(dados, comunicar = false) {
    if (encerrada || dados.atividade < ultimaConfirmada) return;
    ultimaConfirmada = dados.atividade;
    prazo = Date.now() + dados.restante * 1000;
    if (dados.restante > cfg.aviso) esconderAviso();
    if (comunicar) publicar({ prazo, atividade:ultimaConfirmada });
  }

  async function pedir(atividade, instante) {
    const abortar = new AbortController();
    const temporizador = setTimeout(() => abortar.abort(), 10000);
    try {
      const opcoes = { credentials:'same-origin', cache:'no-store', signal:abortar.signal,
        headers:{ Accept:'application/json', 'X-Sessao-Id':cfg.identificador } };
      if (atividade) {
        opcoes.method = 'POST';
        opcoes.headers['X-CSRF-Token'] = cfg.token;
        opcoes.body = new URLSearchParams({ atraso:String(Math.floor(Math.max(0,Date.now()-instante)/1000)) });
      }
      const resposta = await fetch(cfg.base + '/sessao/' + (atividade ? 'atividade' : 'estado'), opcoes);
      const dados = await resposta.json().catch(() => null);
      if (resposta.status === 428 || (dados?.exigir_senha && !cfg.alterarSenha)) {
        encerrada = true;
        window.dispatchEvent(new Event('sessao:encerrar'));
        window.location.replace(cfg.base + '/alterar-senha');
        return null;
      }
      if ([401,403,409].includes(resposta.status)) {
        encerrar(dados?.erro === 'sessao_expirada');
        return null;
      }
      if (!resposta.ok || !dados?.ativo) throw new Error('Não foi possível confirmar a sessão.');
      aplicarEstado(dados, atividade);
      return dados;
    } finally { clearTimeout(temporizador); }
  }

  function agendarEnvio() {
    clearTimeout(agendamento);
    if (!encerrada && ultimaAcao > ultimaEnviada) {
      agendamento = setTimeout(() => enviarAtividade(), Math.max(0,30000-(Date.now()-ultimoEnvio)));
    }
  }

  function enviarAtividade() {
    if (encerrada) return Promise.resolve(null);
    if (envio) return envio;
    const instante = ultimaAcao;
    ultimoEnvio = Date.now();
    envio = pedir(true, instante).then(dados => {
      if (dados) ultimaEnviada = Math.max(ultimaEnviada, instante);
      return dados;
    }).catch(() => {
      if (!aviso.hidden) erro.textContent = 'Não foi possível confirmar a sessão. Verifique a ligação e tente novamente.';
      if (Date.now() >= prazo) encerrar(true);
      return null;
    }).finally(() => { envio = null; agendarEnvio(); });
    return envio;
  }

  function sincronizar() {
    if (encerrada) return Promise.resolve(null);
    if (consulta) return consulta;
    consulta = pedir(false).catch(() => {
      if (Date.now() >= prazo) encerrar(true);
      return null;
    }).finally(() => { consulta = null; });
    return consulta;
  }

  function registarAcao(evento) {
    if (encerrada || !evento.isTrusted || !aviso.hidden) return;
    // Ao regressar de suspensão, confirma o servidor antes de tentar renovar uma sessão vencida.
    if (Date.now() >= prazo) { sincronizar(); return; }
    const agora = Date.now();
    if (agora - ultimaAcao < 1000) return;
    ultimaAcao = agora;
    prazo = agora + cfg.limite * 1000;
    if (agora - ultimoEnvio >= 30000) enviarAtividade();
    else agendarEnvio();
  }

  function formatarTempo(segundos) {
    return String(Math.floor(segundos/60)).padStart(2,'0') + ':' + String(segundos%60).padStart(2,'0');
  }

  function verificarPrazo() {
    if (encerrada) return;
    const restante = Math.max(0,Math.ceil((prazo-Date.now())/1000));
    tempo.textContent = formatarTempo(restante);
    if (restante <= cfg.aviso && aviso.hidden) {
      focoAnterior = document.activeElement;
      aviso.hidden = false;
      document.body.classList.add('sessao-em-aviso');
      continuar.focus();
    }
    if (!restante) sincronizar();
  }

  continuar.addEventListener('click', async () => {
    if (encerrada) return;
    continuar.disabled = true;
    erro.textContent = '';
    ultimaAcao = Date.now();
    // A renovação só é aceite se o servidor confirmar que a sessão ainda está válida.
    await enviarAtividade();
    continuar.disabled = false;
  });

  ['pointermove','pointerdown','keydown','input','scroll','wheel','touchstart'].forEach(tipo => {
    document.addEventListener(tipo,registarAcao,{ capture:true, passive:true });
  });
  document.addEventListener('keydown', evento => {
    if (aviso.hidden) return;
    if (evento.key === 'Escape') { evento.preventDefault(); evento.stopImmediatePropagation(); }
    if (evento.key !== 'Tab') return;
    evento.preventDefault();
    continuar.focus();
  },true);
  document.addEventListener('submit', evento => {
    if (evento.target.id === 'logoutForm') {
      window.dispatchEvent(new Event('sessao:encerrar'));
      publicar({ encerrada:true, inatividade:false });
      encerrada = true;
      clearTimeout(agendamento);
    }
  },true);
  window.addEventListener('storage', evento => {
    if (evento.key !== chave || !evento.newValue) return;
    try {
      const dados = JSON.parse(evento.newValue);
      if (dados.encerrada) encerrar(!!dados.inatividade,false);
      else if (dados.atividade >= ultimaConfirmada) {
        ultimaConfirmada = dados.atividade;
        prazo = Math.max(prazo,dados.prazo);
        if (prazo-Date.now() > cfg.aviso*1000) esconderAviso();
      }
    } catch (_) {}
  });
  document.addEventListener('visibilitychange', () => { if (!document.hidden) sincronizar(); });
  window.addEventListener('focus',sincronizar);
  window.addEventListener('pageshow',sincronizar);
  setInterval(verificarPrazo,1000);
  setInterval(sincronizar,30000);
  verificarPrazo();
  sincronizar();
})();
