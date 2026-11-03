<?php
// Ocorrências: recebe pedidos, verifica permissões e coordena registos e documentos.
class OcorrenciaController {
    private PDO $pdo;
    private Esquadra $esquadras;
    private Notificacao $notificacoes;
    private Ocorrencia $ocorrencias;
    private TipoOcorrencia $tipos;
    private Utilizador $utilizadores;
    private array $anexosNovos = [];

    // Prepara os models e a ligação utilizados por esta classe.
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->esquadras = new Esquadra();
        $this->notificacoes = new Notificacao();
        $this->ocorrencias = new Ocorrencia();
        $this->tipos = new TipoOcorrencia();
        $this->utilizadores = new Utilizador();
    }

    // Recolhe os filtros e os registos necessários para apresentar a listagem.
    public function listar(): void {
        $filtros = [
            'pesquisa'    => $_GET['q'] ?? '',
            'tipo_id'     => $_GET['tipo'] ?? '',
            'natureza_dados' => isset(OcorrenciaService::NATUREZAS[$_GET['natureza_dados'] ?? '']) ? $_GET['natureza_dados'] : '',
            'estado'      => $_GET['estado'] ?? '',
            'validacao_estado' => isset(OcorrenciaService::VALIDACOES[$_GET['validacao_estado'] ?? '']) ? $_GET['validacao_estado'] : '',
            'conclusao_estado' => isset(OcorrenciaService::CONCLUSOES[$_GET['conclusao_estado'] ?? '']) ? $_GET['conclusao_estado'] : '',
            'em_curso' => ($_GET['em_curso'] ?? '') === '1',
            'prioridade'  => $_GET['prioridade'] ?? '',
            'esquadra_id' => $_GET['esquadra'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim'    => $_GET['data_fim'] ?? '',
        ];

        // Filtros por perfil
        if ($_SESSION['perfil'] === 'agente') {
            $filtros['agente_id'] = $_SESSION['user_id'];
        } elseif ($_SESSION['perfil'] === 'admin') {
            if (!empty($_GET['agente'])) {
                $filtros['agente_id'] = (int)$_GET['agente'];
            }
        } elseif (in_array($_SESSION['perfil'], ['supervisor', 'investigador'])) {
            $filtros['esquadra_id'] = $_SESSION['esquadra_id'];
        }

        $pagina   = max(1, (int)($_GET['pagina'] ?? 1));
        $total    = $this->ocorrencias->contar($filtros);
        $totalPgs = max(1, (int)ceil($total / ITEMS_PER_PAGE));
        $pagina   = min($pagina, $totalPgs);
        $offset   = ($pagina - 1) * ITEMS_PER_PAGE;

        $ocorrencias = $this->ocorrencias->listarTodos($filtros, $offset, ITEMS_PER_PAGE);
        $tipos       = $this->tipos->listarTodos(true);
        $esquadras   = $this->esquadras->listarParaSelect();
        $agentes     = $_SESSION['perfil'] === 'admin' ? $this->utilizadores->listarAgentesAtivos() : [];

        $titulo  = 'Gestão de Ocorrências';
        $paginaAtiva = 'ocorrencias';

        renderView('ocorrencias/index', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'filtros' => $filtros,
            'pagina' => $pagina,
            'total' => $total,
            'totalPgs' => $totalPgs,
            'ocorrencias' => $ocorrencias,
            'tipos' => $tipos,
            'esquadras' => $esquadras,
            'agentes' => $agentes,
        ]);
    }

    // Cria o registo com os dados recebidos.
    public function criar(): void {
        if ($_SESSION['perfil'] !== 'agente') {
            redirect('dashboard');
        }

        $tipos     = $this->tipos->listarTodos(true);
        $esquadras = $this->esquadras->listarParaSelect();
        $erros     = [];
        $dados     = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $_POST;
            $dados['esquadra_id'] = $_SESSION['esquadra_id'];
            $erros = $this->validar($dados);

            if (empty($erros)) {
                $dados['agente_id']   = $_SESSION['user_id'];

                $guardado = false;
                $this->pdo->beginTransaction();
                try {
                    $id = $this->ocorrencias->criar($dados);
                    $this->ocorrencias->guardarNatureza($id,$dados);

                    if (!empty($dados['participantes'])) {
                        foreach ($dados['participantes'] as $p) {
                            if (!empty($p['nome']) || !empty($p['tipo'])) {
                                $this->ocorrencias->adicionarParticipante($id, $p);
                            }
                        }
                    }

                    $this->ocorrencias->registarHistorico($id, $_SESSION['user_id'], 'Ocorrência Registada', 'Ocorrência registada.', null, 'aberta');
                    $ocRegistada = $this->ocorrencias->buscarPorId($id);
                    $this->notificacoes->criarParaSupervisores(
                        (int)$ocRegistada['esquadra_id'], 'Ocorrência Pendente de Aprovação',
                        'A ocorrência '.$ocRegistada['numero'].' aguarda a sua análise e aprovação.',
                        'aviso', BASE_URL.'/ocorrencias/ver/'.$id
                    );

                    $anexosRejeitados = [];
                    if (!empty($_FILES['anexos']['name'][0])) {
                        $anexosRejeitados = $this->processarAnexos($id, $_FILES['anexos']);
                    }

                    (new Rascunho())->eliminar((int)$_SESSION['user_id'],'nova');
                    $this->pdo->commit();
                    $guardado = true;

                    $msg = 'Ocorrência submetida para aprovação pelo supervisor da esquadra.';

                    $toastTipo = 'sucesso';
                    if (!empty($anexosRejeitados)) {
                        $msg .= ' Atenção: ' . count($anexosRejeitados) . ' ficheiro(s) não foram aceites — ' . implode('; ', $anexosRejeitados) . '.';
                        $toastTipo = 'aviso';
                    }

                    $_SESSION['toast'] = ['tipo' => $toastTipo, 'msg' => $msg];
                    CacheService::limpar();
                    redirect('ocorrencias');
                } catch (Throwable $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                        $this->limparAnexosNovos();
                    }
                    error_log('[SIGOP] Erro ao registar ocorrência: ' . $e->getMessage());
                    if ($guardado) {
                        CacheService::limpar();
                        $_SESSION['toast'] = ['tipo'=>'aviso','msg'=>'A ocorrência foi registada. Algumas notificações não puderam ser enviadas.'];
                        redirect('ocorrencias/ver/'.$id);
                    }
                    $erros[] = 'Erro ao registar ocorrência. Tente novamente.';
                }
            }
        }

        $titulo   = 'Nova Ocorrência';
        $paginaAtiva  = 'ocorrencias';
        $vistaAtual = 'criar';
        renderView('ocorrencias/criar', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'vistaAtual' => $vistaAtual,
            'tipos' => $tipos,
            'esquadras' => $esquadras,
            'erros' => $erros,
            'dados' => $dados,
        ]);
    }

    // Verifica o acesso e apresenta os detalhes do registo.
    public function exibir(int $id): void {
        $ocorrencia = $this->ocorrencias->buscarPorId($id);
        if (!$ocorrencia) { mostrar404(); return; }
        if (!OcorrenciaService::podeConsultar($ocorrencia)) redirect('ocorrencias');

        // Agente só vê as suas
        if ($_SESSION['perfil'] === 'agente' && $ocorrencia['agente_id'] !== (int)$_SESSION['user_id']) {
            redirect('ocorrencias');
        }
        // Supervisor e investigador só vêem da sua esquadra
        if (in_array($_SESSION['perfil'], ['supervisor', 'investigador']) && $ocorrencia['esquadra_id'] !== (int)$_SESSION['esquadra_id']) {
            redirect('ocorrencias');
        }

        $participantes = $this->ocorrencias->listarParticipantes($id);
        $historico     = $this->ocorrencias->listarHistorico($id);
        $anexos        = $this->ocorrencias->listarAnexos($id);

        $investigadoresEsquadra = $this->ocorrencias->listarInvestigadoresEsquadra((int)$ocorrencia['esquadra_id']);

        $titulo   = 'Detalhes — ' . $ocorrencia['numero'];
        $paginaAtiva  = 'ocorrencias';
        $vistaAtual = 'ver';
        renderView('ocorrencias/exibir', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'vistaAtual' => $vistaAtual,
            'ocorrencia' => $ocorrencia,
            'participantes' => $participantes,
            'historico' => $historico,
            'anexos' => $anexos,
            'investigadoresEsquadra' => $investigadoresEsquadra,
        ]);
    }

    public function atribuirInvestigador(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('ocorrencias');
        }

        $ocorrencia = $this->ocorrencias->buscarPorId($id);
        if (!$ocorrencia) { mostrar404(); return; }

        if (!OcorrenciaService::podeAtribuir($ocorrencia)) {
            $_SESSION['toast'] = ['tipo'=>'aviso','msg'=>'A atribuição é feita pelo supervisor em casos aprovados e activos, sem conclusão pendente.'];
            redirect('ocorrencias');
        }

        $investigadorId = (int)($_POST['investigador_id'] ?? 0);
        $valido = $investigadorId && $this->utilizadores->investigadorAtivoDaEsquadra(
            $investigadorId, (int)$ocorrencia['esquadra_id']
        );

        if (!$valido) {
            $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Seleccione um investigador válido e activo desta esquadra.'];
            header('Location: ' . BASE_URL . '/ocorrencias/ver/' . $id); exit;
        }

        try {
            $this->pdo->beginTransaction();
            $antes = OcorrenciaService::bloquear($id,$_POST['versao'] ?? '');
            if (!OcorrenciaService::podeAtribuir($antes)) throw new DomainException('Este caso já não está disponível para atribuição.');
            if (!$this->utilizadores->investigadorAtivoDaEsquadra($investigadorId,(int)$antes['esquadra_id'],true)) {
                throw new DomainException('O investigador deixou de estar activo nesta esquadra. Reabra o registo e seleccione outro investigador.');
            }
            if ((int)$antes['investigador_id'] === $investigadorId) throw new DomainException('A ocorrência já está atribuída a este investigador.');
            $eraReatribuicao = !empty($antes['investigador_id']);
            $this->ocorrencias->atribuirInvestigador($id,$investigadorId);
            $investigador = $this->utilizadores->contactoPorId($investigadorId);
            OcorrenciaService::auditar($id,$antes,$this->ocorrencias->buscarPorId($id));
            $this->notificacoes->criar($investigadorId,$eraReatribuicao ? 'Caso Reatribuído' : 'Novo Caso Atribuído',
                'A ocorrência '.$ocorrencia['numero'].' foi-lhe atribuída para investigação.','aviso',BASE_URL.'/ocorrencias/ver/'.$id);
            OcorrenciaService::incrementarVersao($id);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[SIGOP] Atribuição: '.$e->getMessage());
            $_SESSION['toast'] = ['tipo'=>'erro','msg'=>$e instanceof DomainException ? $e->getMessage() : 'Não foi possível atribuir o investigador.'];
            redirect('ocorrencias/ver/'.$id);
        }
        if (!empty($investigador['email'])) {
            EmailService::enviarCasoAtribuido(
                $investigador['email'], $investigador['nome'],
                $ocorrencia['numero'], $ocorrencia['tipo_nome'], $ocorrencia['esquadra_nome'],
                BASE_URL . '/ocorrencias/ver/' . $id, $eraReatribuicao
            );
        }

        $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Investigador ' . ($eraReatribuicao ? 'reatribuído' : 'atribuído') . ' com sucesso.'];
        CacheService::limpar();
        header('Location: ' . BASE_URL . '/ocorrencias/ver/' . $id);
        exit;
    }

    // Prepara a versão de impressão com os dados permitidos ao utilizador.
    public function imprimir(int $id): void {
        $ocorrencia = $this->ocorrencias->buscarPorId($id);
        if (!$ocorrencia) { mostrar404(); return; }
        if (!OcorrenciaService::podeConsultar($ocorrencia)) redirect('ocorrencias');

        if ($_SESSION['perfil'] === 'agente' && $ocorrencia['agente_id'] !== (int)$_SESSION['user_id']) {
            redirect('ocorrencias');
        }
        if (in_array($_SESSION['perfil'], ['supervisor', 'investigador']) && $ocorrencia['esquadra_id'] !== (int)$_SESSION['esquadra_id']) {
            redirect('ocorrencias');
        }

        $participantes = $this->ocorrencias->listarParticipantes($id);
        $historico     = $this->ocorrencias->listarHistorico($id);
        $anexos        = $this->ocorrencias->listarAnexos($id);

        renderView('ocorrencias/imprimir', [
            'ocorrencia' => $ocorrencia,
            'participantes' => $participantes,
            'historico' => $historico,
            'anexos' => $anexos,
        ]);
        exit;
    }

    // Verifica o acesso, garante o número do auto e solicita o PDF ao serviço.
    public function gerarAuto(int $id): void {
        $ocorrencia = $this->ocorrencias->buscarPorId($id);
        if (!$ocorrencia) { mostrar404(); return; }
        if (!OcorrenciaService::podeConsultar($ocorrencia)) redirect('ocorrencias');

        if ($_SESSION['perfil'] === 'agente' && $ocorrencia['agente_id'] !== (int)$_SESSION['user_id']) {
            redirect('ocorrencias');
        }
        if (in_array($_SESSION['perfil'], ['supervisor', 'investigador']) && $ocorrencia['esquadra_id'] !== (int)$_SESSION['esquadra_id']) {
            redirect('ocorrencias');
        }

        $ocorrencia    = $this->ocorrencias->garantirNumeroAuto($ocorrencia);
        $participantes = $this->ocorrencias->listarParticipantes($id);

        (new OcorrenciaPdfService())->descarregarAuto($ocorrencia, $participantes);
    }

    // Carrega o registo, valida o formulário e encaminha a atualização.
    public function editar(int $id): void {
        $ocorrencia = $this->ocorrencias->buscarPorId($id);
        if (!$ocorrencia) { mostrar404(); return; }
        if (!OcorrenciaService::podeEditar($ocorrencia)) {
            $_SESSION['toast'] = ['tipo'=>'aviso','msg'=>'Esta ocorrência não está disponível para edição. O agente só pode corrigir ocorrências pendentes ou rejeitadas.'];
            redirect('ocorrencias');
        }

        if ($ocorrencia['estado'] === 'arquivada') {
            header('Location: ' . BASE_URL . '/ocorrencias/ver/' . $id);
            exit;
        }

        // Agente só edita as suas.
        if ($_SESSION['perfil'] === 'agente' && (int)$ocorrencia['agente_id'] !== (int)$_SESSION['user_id']) {
            redirect('ocorrencias');
        }

        // Supervisor e investigador só editam da sua esquadra
        if (in_array($_SESSION['perfil'], ['supervisor', 'investigador']) && (int)$ocorrencia['esquadra_id'] !== (int)$_SESSION['esquadra_id']) {
            redirect('ocorrencias');
        }

        $tipos         = $this->tipos->listarTodos(true);
        // Mantém disponível o tipo histórico quando foi desactivado depois do registo.
        $tipoOriginal = $this->tipos->buscarPorId((int)$ocorrencia['tipo_id']);
        if ($tipoOriginal && !$tipoOriginal['ativo']) {
            $tipos[] = $tipoOriginal;
        }
        $esquadras     = $this->esquadras->listarParaSelect();
        $participantes = $this->ocorrencias->listarParticipantes($id);
        $erros         = [];
        $dados         = $ocorrencia;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $_POST;
            $erros = $this->validar($dados, (int)$ocorrencia['tipo_id']);

            if (empty($erros)) {
                $this->pdo->beginTransaction();
                try {
                    $antes = OcorrenciaService::bloquear($id,$dados['versao'] ?? '');
                    if (!OcorrenciaService::podeEditar($antes)) throw new DomainException('A ocorrência já foi aprovada ou arquivada e não pode ser editada por este utilizador.');
                    $participantesAntes = $this->ocorrencias->listarParticipantes($id);
                    $estadoAnterior = $antes['estado'];
                    $this->ocorrencias->atualizar($id, $dados);
                    $this->ocorrencias->guardarNatureza($id,$dados);

                    // Os dados da ocorrência e os participantes são guardados em conjunto.
                    if (!empty($dados['participantes_remover'])) {
                        foreach ($dados['participantes_remover'] as $pid) {
                            $this->ocorrencias->eliminarParticipante((int)$pid, $id);
                        }
                    }

                    if (!empty($dados['participantes'])) {
                        foreach ($dados['participantes'] as $p) {
                            if (!empty($p['nome']) || !empty($p['tipo'])) {
                                $this->ocorrencias->adicionarParticipante($id, $p);
                            }
                        }
                    }

                    $anexosRejeitados = [];
                    if (!empty($_FILES['anexos']['name'][0])) {
                        $anexosRejeitados = $this->processarAnexos($id, $_FILES['anexos']);
                    }

                    $resubmetida = $_SESSION['perfil'] === 'agente' && $antes['validacao_estado'] === 'rejeitada';
                    if ($resubmetida) {
                        $this->ocorrencias->resubmeter($id);
                        $this->ocorrencias->registarHistorico($id,$_SESSION['user_id'],'Ocorrência Resubmetida','Corrigida pelo agente e submetida novamente para aprovação.');
                        $this->notificacoes->criarParaSupervisores((int)$antes['esquadra_id'],'Ocorrência Resubmetida',
                            'A ocorrência '.$antes['numero'].' foi corrigida e aguarda nova aprovação.','aviso',BASE_URL.'/ocorrencias/ver/'.$id);
                    }
                    OcorrenciaService::auditar($id,$antes,$this->ocorrencias->buscarPorId($id));
                    OcorrenciaService::auditarParticipantes($id,$participantesAntes,$this->ocorrencias->listarParticipantes($id));
                    OcorrenciaService::incrementarVersao($id);
                    (new Rascunho())->eliminar((int)$_SESSION['user_id'],'editar-'.$id);
                    $this->pdo->commit();
                } catch (Throwable $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    error_log('[SIGOP] Erro ao actualizar ocorrência: ' . $e->getMessage());
                    $this->limparAnexosNovos();
                    $erros[] = $e instanceof DomainException ? $e->getMessage() : 'Não foi possível guardar as alterações. Verifique os dados e tente novamente.';
                }

                if (empty($erros)) {
                    $msg = $resubmetida ? 'Ocorrência corrigida e resubmetida para aprovação.' : 'Ocorrência actualizada com sucesso!';
                    $toastTipo = 'sucesso';
                    if (!empty($anexosRejeitados)) {
                        $msg .= ' Atenção: ' . count($anexosRejeitados) . ' ficheiro(s) não foram aceites — ' . implode('; ', $anexosRejeitados) . '.';
                        $toastTipo = 'aviso';
                    }

                    $_SESSION['toast'] = ['tipo' => $toastTipo, 'msg' => $msg];
                    CacheService::limpar();
                    header('Location: ' . BASE_URL . '/ocorrencias/ver/' . $id);
                    exit;
                }
            }
        }

        $titulo   = 'Editar — ' . $ocorrencia['numero'];
        $paginaAtiva  = 'ocorrencias';
        $vistaAtual = 'editar';
        renderView('ocorrencias/editar', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'vistaAtual' => $vistaAtual,
            'ocorrencia' => $ocorrencia,
            'tipos' => $tipos,
            'esquadras' => $esquadras,
            'participantes' => $participantes,
            'erros' => $erros,
            'dados' => $dados,
        ]);
    }

    public function aprovar(int $id): void {
        $this->decidirValidacao($id,'aprovada');
    }

    public function rejeitar(int $id): void {
        $this->decidirValidacao($id,'rejeitada');
    }

    private function decidirValidacao(int $id, string $decisao): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); return; }
        if ($_SESSION['perfil'] !== 'supervisor') { http_response_code(403); return; }
        try {
            $this->pdo->beginTransaction();
            $antes = OcorrenciaService::bloquear($id,$_POST['versao'] ?? '');
            $motivo = trim($_POST['motivo_rejeicao'] ?? '');
            OcorrenciaService::validarDecisao($antes,$decisao,$motivo);
            $aprovada = $decisao === 'aprovada';
            $acao = $aprovada ? 'Ocorrência Aprovada' : 'Ocorrência Rejeitada';
            $mensagem = $aprovada ? 'Aprovada pelo supervisor e disponibilizada ao Comando da Cidade.' : $motivo;
            $this->ocorrencias->validar($id,(int)$_SESSION['user_id'],$decisao,$motivo);
            if ($aprovada) {
                $this->ocorrencias->limparAtribuicao($id);
                OcorrenciaService::auditar($id,$antes,$this->ocorrencias->buscarPorId($id));
            }
            $this->ocorrencias->registarHistorico($id,$_SESSION['user_id'],$acao,$mensagem);
            $this->notificacoes->criar((int)$antes['agente_id'],$acao,
                'Ocorrência '.$antes['numero'].': '.$mensagem,$aprovada ? 'sucesso' : 'alerta',BASE_URL.'/ocorrencias/ver/'.$id);
            OcorrenciaService::incrementarVersao($id);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[SIGOP] Validação: '.$e->getMessage());
            $_SESSION['toast'] = ['tipo'=>'erro','msg'=>$e instanceof DomainException ? $e->getMessage() : 'Não foi possível guardar a decisão. Nenhuma alteração foi guardada.'];
            redirect('ocorrencias');
        }
        CacheService::limpar();
        if ($aprovada) $this->notificarEstado($id,'aprovada');
        $_SESSION['toast'] = ['tipo'=>$aprovada ? 'sucesso' : 'aviso','msg'=>$aprovada
            ? 'Ocorrência aprovada e enviada ao Comando.' : 'Ocorrência rejeitada. O agente foi notificado para corrigir e resubmeter.'];
        redirect('ocorrencias/ver/'.$id);
    }

    // Regista diligências sem permitir ao investigador encerrar o caso directamente.
    public function registarDiligencia(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); return; }
        if ($_SESSION['perfil'] !== 'investigador') { http_response_code(403); return; }
        try {
            $this->pdo->beginTransaction();
            $antes = OcorrenciaService::bloquear($id,$_POST['versao'] ?? '');
            if (!OcorrenciaService::podeInvestigar($antes)) throw new DomainException('Só o investigador responsável pode registar diligências num caso activo.');
            $texto = trim($_POST['observacao'] ?? '');
            if (mb_strlen($texto)<10 || mb_strlen($texto)>10000) throw new DomainException('Descreva a diligência com 10 a 10000 caracteres.');
            $estado = $_POST['estado'] ?? '';
            if (!in_array($estado,['','em_investigacao'],true)) throw new DomainException('O encerramento depende da proposta do investigador e da validação do supervisor.');
            $mudou = $estado === 'em_investigacao' && $antes['estado'] !== $estado;
            if ($mudou) $this->ocorrencias->atualizarEstado($id,$estado);
            $this->ocorrencias->registarHistorico($id,$_SESSION['user_id'],'Diligência Registada',$texto,$antes['estado'],$mudou?$estado:$antes['estado']);
            $rejeitados = !empty($_FILES['anexos']['name'][0]) ? $this->processarAnexos($id,$_FILES['anexos']) : [];
            $this->notificacoes->criarParaSupervisores((int)$antes['esquadra_id'],'Diligência Registada',
                'Foi registada uma diligência na ocorrência '.$antes['numero'].'.','info',BASE_URL.'/ocorrencias/ver/'.$id);
            OcorrenciaService::incrementarVersao($id);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->limparAnexosNovos();
            $this->falhaInvestigacao($e,'Não foi possível guardar a diligência.');
        }
        CacheService::limpar();
        if ($mudou) $this->notificarEstado($id,'em_investigacao');
        $_SESSION['toast'] = ['tipo'=>$rejeitados?'aviso':'sucesso','msg'=>'Diligência registada no histórico.'.($rejeitados?' Ficheiros não aceites: '.implode('; ',$rejeitados).'.':'')];
        redirect('ocorrencias/ver/'.$id);
    }

    public function proporConclusao(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); return; }
        if ($_SESSION['perfil'] !== 'investigador') { http_response_code(403); return; }
        try {
            $this->pdo->beginTransaction();
            $antes = OcorrenciaService::bloquear($id,$_POST['versao'] ?? '');
            $texto = trim($_POST['conclusao'] ?? ''); $destino = $_POST['destino'] ?? '';
            OcorrenciaService::validarProposta($antes,$texto,$destino);
            $this->ocorrencias->proporConclusao($id,(int)$_SESSION['user_id'],$texto,$destino);
            if ($antes['estado'] === 'aberta') $this->ocorrencias->atualizarEstado($id,'em_investigacao');
            $this->ocorrencias->registarHistorico($id,$_SESSION['user_id'],'Conclusão Proposta',
                'Resultado proposto: '.DocumentoService::ESTADOS[$destino].".\n".$texto,$antes['estado'],'em_investigacao');
            $this->notificacoes->criarParaSupervisores((int)$antes['esquadra_id'],'Conclusão por Validar',
                'O investigador propôs a conclusão da ocorrência '.$antes['numero'].'.','aviso',BASE_URL.'/ocorrencias/ver/'.$id.'#conclusao');
            OcorrenciaService::incrementarVersao($id); $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->falhaInvestigacao($e,'Não foi possível submeter a proposta de conclusão.');
        }
        CacheService::limpar();
        if ($antes['estado'] === 'aberta') $this->notificarEstado($id,'em_investigacao');
        $_SESSION['toast'] = ['tipo'=>'sucesso','msg'=>'Proposta de conclusão enviada ao supervisor. O caso aguarda a sua decisão.'];
        redirect('ocorrencias/ver/'.$id.'#conclusao');
    }

    public function decidirConclusao(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); return; }
        if ($_SESSION['perfil'] !== 'supervisor') { http_response_code(403); return; }
        try {
            $this->pdo->beginTransaction();
            $antes = OcorrenciaService::bloquear($id,$_POST['versao'] ?? '');
            $decisao = $_POST['decisao'] ?? ''; $parecer = trim($_POST['parecer'] ?? '');
            OcorrenciaService::validarConclusao($antes,$decisao,$parecer);
            $aprovada = $decisao === 'aprovar';
            $final = $aprovada ? $antes['conclusao_destino'] : $antes['estado'];
            if ($aprovada && !in_array($final,['resolvida','arquivada'],true)) throw new DomainException('O resultado proposto não é válido.');
            $this->ocorrencias->decidirConclusao($id,(int)$_SESSION['user_id'],$aprovada?'aprovada':'devolvida',$parecer);
            if ($aprovada) $this->ocorrencias->atualizarEstado($id,$final);
            $acao = $aprovada ? 'Encerramento Validado' : 'Conclusão Devolvida';
            $this->ocorrencias->registarHistorico($id,$_SESSION['user_id'],$acao,$parecer,$antes['estado'],$final);
            $this->notificacoes->criar((int)$antes['conclusao_proposta_por'],$acao,
                'Ocorrência '.$antes['numero'].': '.$parecer,$aprovada?'sucesso':'aviso',BASE_URL.'/ocorrencias/ver/'.$id.'#conclusao');
            if ($aprovada) $this->notificacoes->criar((int)$antes['agente_id'],$acao,
                'O supervisor validou o encerramento da ocorrência '.$antes['numero'].'.','info',BASE_URL.'/ocorrencias/ver/'.$id);
            OcorrenciaService::incrementarVersao($id); $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->falhaInvestigacao($e,'Não foi possível guardar a decisão do supervisor.');
        }
        CacheService::limpar();
        if ($aprovada) $this->notificarEstado($id,$final);
        $_SESSION['toast'] = ['tipo'=>$aprovada?'sucesso':'aviso','msg'=>$aprovada?'Encerramento validado pelo supervisor.':'Proposta devolvida ao investigador para novas diligências.'];
        redirect('ocorrencias/ver/'.$id.'#conclusao');
    }

    public function atualizarEstado(int $id): void {
        $this->registarDiligencia($id);
    }

    public function arquivar(int $id): void {
        http_response_code(403);
        echo 'O encerramento requer proposta do investigador e validação do supervisor.';
    }

    private function falhaInvestigacao(Throwable $e, string $mensagem): never {
        error_log('[SGO] Investigação: '.$e->getMessage());
        $_SESSION['toast'] = ['tipo'=>'erro','msg'=>$e instanceof DomainException?$e->getMessage():$mensagem];
        redirect('ocorrencias');
    }

    private function notificarEstado(int $id, string $estado): void {
        try { $this->ocorrencias->notificarVitimas($id,$estado); }
        catch (Throwable $e) { error_log('[SIGOP] Estado guardado; falhou a notificação: '.$e->getMessage()); }
    }

    private function limparAnexosNovos(): void {
        foreach ($this->anexosNovos as $caminho) {
            if (is_file($caminho) && !unlink($caminho)) error_log('[SIGOP] Não foi possível limpar um anexo de uma operação anulada.');
        }
        $this->anexosNovos = [];
    }

    // Verifica os campos do formulário e devolve as mensagens de erro.
    private function validar(array $dados, ?int $tipoOriginal = null): array {
        $erros = [];
        if (!isset(OcorrenciaService::NATUREZAS[$dados['natureza_dados'] ?? ''])) $erros[] = 'Seleccione a natureza dos dados.';
        if (mb_strlen($dados['fonte_informacao'] ?? '') > 500) $erros[] = 'A fonte da informação pode ter até 500 caracteres.';
        if (($dados['natureza_dados'] ?? '') === 'confirmados' && mb_strlen(trim($dados['fonte_informacao'] ?? '')) < 4) $erros[] = 'Identifique a fonte ou a forma de confirmação dos dados.';
        $tipoId = filter_var($dados['tipo_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $tipo = $tipoId ? $this->tipos->buscarPorId($tipoId) : null;
        if (!$tipo || (!$tipo['ativo'] && $tipoId !== $tipoOriginal)) {
            $erros[] = 'Seleccione um tipo de ocorrência activo e válido.';
        }
        if (empty($dados['tipo_peca']) || !in_array($dados['tipo_peca'], ['auto_noticia', 'auto_denuncia', 'participacao', 'informacao']))
            $erros[] = 'Seleccione o tipo de peça de expediente.';
        $dataOcorrencia = $dados['data_ocorrencia'] ?? '';
        $dataValida = DateTime::createFromFormat('!Y-m-d', $dataOcorrencia);
        if (!$dataValida || $dataValida->format('Y-m-d') !== $dataOcorrencia)
            $erros[] = 'Indique uma data de ocorrência válida.';
        $horaOcorrencia = $dados['hora_ocorrencia'] ?? '';
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $horaOcorrencia))
            $erros[] = 'Indique uma hora de ocorrência válida.';
        if (!in_array($dados['prioridade'] ?? 'media', ['baixa', 'media', 'alta', 'critica'], true))
            $erros[] = 'Seleccione uma prioridade válida.';
        foreach (['latitude' => 90, 'longitude' => 180] as $campo => $limite) {
            if (($dados[$campo] ?? '') !== '' && (!is_numeric($dados[$campo]) || abs((float)$dados[$campo]) > $limite)) {
                $erros[] = 'Coordenadas GPS inválidas.';
                break;
            }
        }

        if (empty($dados['bairro'])) {
            $erros[] = 'Indique o bairro da ocorrência.';
        } elseif (!textoNoIntervalo($dados['bairro'],2,80)) {
            $erros[] = 'O bairro deve ter entre 2 e 80 caracteres.';
        }

        if (empty($dados['descricao_factos'])) {
            $erros[] = 'Preencha a descrição dos factos.';
        } elseif (mb_strlen(trim($dados['descricao_factos'])) < 50) {
            $erros[] = 'A descrição dos factos deve ter pelo menos 50 caracteres.';
        }

        if ($dataValida && $dataValida->format('Y-m-d') === $dataOcorrencia && $dataOcorrencia > date('Y-m-d'))
            $erros[] = 'A data da ocorrência não pode ser no futuro.';

        if (!empty($dados['participantes'])) {
            $erros = array_merge($erros, $this->validarParticipantes($dados['participantes']));
        }

        return $erros;
    }

    // Replica no servidor as mesmas regras do public/assets/js/validate.js — sem isto,
    // um POST directo (JS desligado ou script) contornava toda a validação de
    // nome/documento/telefone dos participantes.
    private function validarParticipantes(array $participantes): array {
        $erros  = [];
        $regras = [
            'documento' => ['re' => '/^([0-9]{12}[A-Za-z]|[A-Za-z0-9]{6,15})$/', 'msg' => 'inválido — BI: 12 dígitos + 1 letra, ou Passaporte: 6 a 15 caracteres alfanuméricos'],
            'telefone'  => ['re' => '/^\+258\s?[0-9]{2}\s?[0-9]{3}\s?[0-9]{4}$/', 'msg' => 'inválido — formato obrigatório: +258 84 000 0000'],
        ];

        foreach ($participantes as $i => $p) {
            if (!empty($p['nome']) && !textoNoIntervalo($p['nome'],3,100)) {
                $erros[] = 'Participante '.((int)$i+1).' — o nome deve ter entre 3 e 100 caracteres.';
            }
            if (!in_array($p['tipo'] ?? '', ['vitima', 'suspeito', 'testemunha', 'detido'], true)) {
                $erros[] = 'Participante ' . ((int)$i + 1) . ' — tipo inválido.';
            }
            if (!empty($p['email']) && !filter_var($p['email'], FILTER_VALIDATE_EMAIL)) {
                $erros[] = 'Participante ' . ((int)$i + 1) . ' — email inválido.';
            }
            if (($p['idade'] ?? '') !== '' && filter_var($p['idade'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 0, 'max_range' => 120],
            ]) === false) {
                $erros[] = 'Participante ' . ((int)$i + 1) . ' — idade inválida (0 a 120 anos).';
            }
            if (($p['sexo'] ?? '') !== '' && !in_array($p['sexo'], ['M', 'F', 'outro'], true)) {
                $erros[] = 'Participante ' . ((int)$i + 1) . ' — sexo inválido.';
            }
            if (($p['estado_civil'] ?? '') !== '' && !in_array($p['estado_civil'], ['solteiro', 'casado', 'divorciado', 'viuvo', 'uniao_de_facto'], true)) {
                $erros[] = 'Participante ' . ((int)$i + 1) . ' — estado civil inválido.';
            }
            if (!empty($p['data_nascimento'])) {
                $nascimento = DateTime::createFromFormat('!Y-m-d', $p['data_nascimento']);
                if (!$nascimento || $nascimento->format('Y-m-d') !== $p['data_nascimento'] || $p['data_nascimento'] > date('Y-m-d')) {
                    $erros[] = 'Participante ' . ((int)$i + 1) . ' — data de nascimento inválida.';
                }
            }
            foreach ($regras as $campo => $regra) {
                if (!empty($p[$campo]) && !preg_match($regra['re'], trim($p[$campo]))) {
                    $erros[] = 'Participante ' . ((int)$i + 1) . ' — ' . ucfirst($campo) . ' ' . $regra['msg'];
                }
            }
        }

        return $erros;
    }

    // Devolve os nomes dos ficheiros rejeitados (com motivo), para o chamador
    // poder avisar o utilizador em vez de os descartar silenciosamente.
    private function processarAnexos(int $ocorrenciaId, array $files): array {
        // Mapa autoritativo: MIME real (detectado pelo servidor) → extensão segura
        $mimeToExt = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/gif'       => 'gif',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];

        $rejeitados = [];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) return $rejeitados;
        $count = count($files['name']);

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $rejeitados[] = $files['name'][$i] . ' (erro no envio)';
                continue;
            }
            if ($files['size'][$i] > UPLOAD_MAX_SIZE) {
                $rejeitados[] = $files['name'][$i] . ' (excede o tamanho máximo de ' . round(UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB)';
                continue;
            }

            // Detectar MIME real a partir do conteúdo do ficheiro (não do cliente)
            $realMime = finfo_file($finfo, $files['tmp_name'][$i]);

            // finfo detecta ficheiros DOCX como application/zip (são ZIPs internamente);
            // aceitar apenas se a extensão original também for .docx
            if ($realMime === 'application/zip') {
                $origExt = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if ($origExt === 'docx') {
                    $realMime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                }
            }

            if (!isset($mimeToExt[$realMime])) {
                $rejeitados[] = $files['name'][$i] . ' (tipo de ficheiro não suportado)';
                continue;
            }

            // Extensão derivada do mapa seguro, nunca do nome fornecido pelo cliente
            $ext      = $mimeToExt[$realMime];
            $novoNome = uniqid('oc_') . '.' . $ext;

            if (move_uploaded_file($files['tmp_name'][$i], UPLOAD_DIR . $novoNome)) {
                $this->anexosNovos[] = UPLOAD_DIR.$novoNome;
                $this->ocorrencias->guardarAnexo(
                    $ocorrenciaId, $files['name'][$i], $novoNome, $realMime,
                    (int)$files['size'][$i], (int)$_SESSION['user_id']
                );
            } else {
                $rejeitados[] = $files['name'][$i] . ' (falha ao gravar o ficheiro)';
            }
        }

        finfo_close($finfo);
        return $rejeitados;
    }

    public function excluirAnexo(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('ocorrencias');
        }

        $anexo = $this->ocorrencias->anexoParaExcluir($id);

        if (!$anexo) {
            $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Anexo não encontrado.'];
            redirect('ocorrencias');
        }

        $ocorrenciaId = $anexo['ocorrencia_id'];
        $perfil       = $_SESSION['perfil'];

        if ($anexo['estado'] === 'arquivada') {
            $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Não é possível eliminar anexos de uma ocorrência arquivada.'];
            header('Location: ' . BASE_URL . '/ocorrencias/ver/' . $ocorrenciaId); exit;
        }

        if ($perfil === 'agente') {
            if ((int)$anexo['agente_id'] !== (int)$_SESSION['user_id'] || (int)$anexo['uploader_id'] !== (int)$_SESSION['user_id']) {
                $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Sem permissão para eliminar este ficheiro.'];
                header('Location: ' . BASE_URL . '/ocorrencias/ver/' . $ocorrenciaId); exit;
            }
        } elseif (in_array($perfil, ['supervisor', 'investigador'])) {
            if ((int)$anexo['esquadra_id'] !== (int)$_SESSION['esquadra_id']) {
                $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Sem permissão para eliminar este ficheiro.'];
                header('Location: ' . BASE_URL . '/ocorrencias/ver/' . $ocorrenciaId); exit;
            }
        }

        try {
            $this->pdo->beginTransaction();
            $atual = OcorrenciaService::bloquear((int)$ocorrenciaId,$_POST['versao'] ?? '');
            if (!OcorrenciaService::podeEditar($atual)) throw new DomainException('A ocorrência já não está disponível para alteração dos anexos.');
            $this->ocorrencias->excluirAnexo($id);
            $this->ocorrencias->registarHistorico($ocorrenciaId,$_SESSION['user_id'],'Anexo Eliminado',
                'Ficheiro: '.$anexo['nome_original'].'; tamanho: '.$anexo['tamanho'].' bytes; nome guardado: '.$anexo['nome_arquivo'].'.');
            OcorrenciaService::incrementarVersao((int)$ocorrenciaId);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[SIGOP] Remoção de anexo: '.$e->getMessage());
            $_SESSION['toast'] = ['tipo'=>'erro','msg'=>$e instanceof DomainException ? $e->getMessage() : 'Não foi possível eliminar o anexo.'];
            redirect('ocorrencias/ver/'.$ocorrenciaId);
        }
        $filePath = UPLOAD_DIR.basename($anexo['nome_arquivo']);
        if (is_file($filePath) && !unlink($filePath)) error_log('[SIGOP] Anexo removido da base de dados mas ainda presente no disco: '.$filePath);

        $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Ficheiro "' . $anexo['nome_original'] . '" eliminado.'];
        header('Location: ' . BASE_URL . '/ocorrencias/ver/' . $ocorrenciaId); exit;
    }

    public function abrirAnexo(int $id): void {
        $anexo = $this->ocorrencias->anexoParaAbrir($id);
        if (!$anexo) { mostrar404(); return; }
        if (!OcorrenciaService::podeConsultar($anexo)) { http_response_code(403); return; }

        if (($_SESSION['perfil'] === 'agente' && (int)$anexo['agente_id'] !== (int)$_SESSION['user_id'])
            || (in_array($_SESSION['perfil'], ['supervisor', 'investigador'], true)
                && (int)$anexo['esquadra_id'] !== (int)$_SESSION['esquadra_id'])) {
            http_response_code(403);
            exit;
        }

        $caminho = UPLOAD_DIR . basename($anexo['nome_arquivo']);
        if (!is_file($caminho)) { mostrar404(); return; }

        $tiposPermitidos = [
            'image/jpeg', 'image/png', 'image/gif', 'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $tipo = in_array($anexo['tipo_mime'], $tiposPermitidos, true)
            ? $anexo['tipo_mime'] : 'application/octet-stream';
        $nome = str_replace(["\r", "\n", '"', '\\'], '', basename($anexo['nome_original']));
        header('Content-Type: ' . $tipo);
        header('Content-Length: ' . filesize($caminho));
        header('Content-Disposition: inline; filename="' . $nome . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($caminho);
        exit;
    }
}
