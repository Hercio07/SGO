<?php
class RascunhoController {
    public function executar(string $acao): void {
        header('Content-Type: application/json; charset=UTF-8');
        if (!hash_equals($_SESSION['sessao_identificador'],$_SERVER['HTTP_X_SESSAO_ID'] ?? '')) {
            http_response_code(409); echo json_encode(['erro'=>'sessao_alterada']); return;
        }
        $contexto = ($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET)['contexto'] ?? '';
        if ($contexto === 'nova') {
            if ($_SESSION['perfil'] !== 'agente') { http_response_code(403); echo json_encode(['erro'=>'Sem acesso a este formulário.']); return; }
        } elseif (preg_match('/^editar-([1-9][0-9]*)$/',$contexto,$m)) {
            $o = (new Ocorrencia())->buscarPorId((int)$m[1]);
            if (!$o || !OcorrenciaService::podeEditar($o)) { http_response_code(403); echo json_encode(['erro'=>'Ocorrência indisponível.']); return; }
        } else { http_response_code(400); echo json_encode(['erro'=>'Formulário inválido.']); return; }
        $model = new Rascunho(); $uid = (int)$_SESSION['user_id'];
        try {
            if ($acao === 'obter' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $rascunho = $model->obter($uid,$contexto);
                echo json_encode(['rascunho'=>!empty($rascunho['dados']) ? $rascunho : null,'versao'=>(int)($rascunho['versao'] ?? 0)]); return;
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); return; }
            if ($acao === 'descartar') {
                $versao = filter_var($_POST['versao_rascunho'] ?? '',FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
                if ($versao === false) throw new DomainException('Versão inválida.');
                $novaVersao = $model->eliminar($uid,$contexto,$versao); echo json_encode(['ok'=>true,'versao'=>$novaVersao]); return;
            }
            if ($acao !== 'guardar') { http_response_code(404); return; }
            $texto = $_POST['dados'] ?? '';
            if (strlen($texto) > 250000) throw new DomainException('O rascunho excede o tamanho permitido.');
            $dados = json_decode($texto,true,512,JSON_THROW_ON_ERROR);
            if (!is_array($dados) || count($dados) > 1000) throw new DomainException('Rascunho inválido.');
            $campos = ['tipo_id','tipo_peca','data_ocorrencia','hora_ocorrencia','bairro','rua','referencia_local','descricao_factos','objetos_recuperados','observacoes','prioridade','latitude','longitude','natureza_dados','fonte_informacao','versao'];
            foreach ($dados as $campo) {
                if (!is_array($campo) || !is_string($campo['nome'] ?? null) || !is_string($campo['valor'] ?? null)) throw new DomainException('Campo de rascunho inválido.');
                if (!in_array($campo['nome'],$campos,true) && !preg_match('/^participantes\[[0-9]{1,3}\]\[(tipo|nome|idade|sexo|estado_civil|data_nascimento|nome_pai|nome_mae|naturalidade|nacionalidade|profissao|quarteirao|numero_casa|documento|telefone|email|endereco)\]$|^participantes_remover\[\]$/',$campo['nome'])) throw new DomainException('Campo não permitido no rascunho.');
            }
            $versao = filter_var($_POST['versao_rascunho'] ?? '',FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
            if ($versao === false) throw new DomainException('Versão do rascunho inválida.');
            echo json_encode(['rascunho'=>$model->guardar($uid,$contexto,$dados,$versao)]);
        } catch (DomainException $e) { http_response_code(409); echo json_encode(['erro'=>$e->getMessage()]); }
        catch (JsonException $e) { http_response_code(400); echo json_encode(['erro'=>'Dados inválidos.']); }
        catch (Throwable $e) { error_log('[SIGOP] Rascunho: '.$e->getMessage()); http_response_code(500); echo json_encode(['erro'=>'Não foi possível guardar o rascunho.']); }
    }
}
