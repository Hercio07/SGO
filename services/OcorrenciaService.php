<?php
// Escopo de consulta, aprovação e responsabilidades de cada perfil.
class OcorrenciaService {
    public const NATUREZAS = ['por_validar'=>'Por validar','confirmados'=>'Dados confirmados','simulados'=>'Contém dados simulados'];
    public const VALIDACOES = ['pendente'=>'Pendente de aprovação','aprovada'=>'Aprovada','rejeitada'=>'Rejeitada'];
    public const CONCLUSOES = ['sem_proposta'=>'Sem proposta','pendente'=>'Aguarda decisão do supervisor','aprovada'=>'Encerramento validado','devolvida'=>'Devolvida para diligências'];

    public static function condicaoValidacao(array $filtros, array &$parametros, string $prefixo = 'o.'): string {
        $perfil = $_SESSION['perfil'] ?? '';
        $condicoes = [];
        if ($perfil === 'agente') { $condicoes[] = $prefixo.'agente_id=?'; $parametros[] = (int)$_SESSION['user_id']; }
        if (in_array($perfil,['supervisor','investigador'],true)) { $condicoes[] = $prefixo.'esquadra_id=?'; $parametros[] = (int)$_SESSION['esquadra_id']; }
        if ($perfil === 'investigador') { $condicoes[] = $prefixo.'investigador_id=?'; $parametros[] = (int)$_SESSION['user_id']; }
        $estado = in_array($perfil,['admin','investigador'],true) ? 'aprovada' : ($filtros['validacao_estado'] ?? '');
        if (isset(self::VALIDACOES[$estado])) { $condicoes[] = $prefixo.'validacao_estado=?'; $parametros[] = $estado; }
        return $condicoes ? implode(' AND ',$condicoes) : '1=1';
    }

    public static function podeConsultar(array $o): bool {
        $aprovada = ($o['validacao_estado'] ?? 'pendente') === 'aprovada';
        return ($_SESSION['perfil'] === 'admin' && $aprovada)
            || ($_SESSION['perfil'] === 'agente' && (int)$o['agente_id'] === (int)$_SESSION['user_id'])
            || ($_SESSION['perfil'] === 'supervisor' && (int)$o['esquadra_id'] === (int)$_SESSION['esquadra_id'])
            || ($_SESSION['perfil'] === 'investigador' && $aprovada && (int)($o['investigador_id'] ?? 0) === (int)$_SESSION['user_id'] && (int)$o['esquadra_id'] === (int)$_SESSION['esquadra_id']);
    }

    public static function podeEditar(array $o): bool {
        return self::podeConsultar($o) && !in_array($o['estado'],['resolvida','arquivada'],true)
            && ($o['conclusao_estado'] ?? 'sem_proposta') !== 'pendente'
            && (($_SESSION['perfil'] === 'agente' && in_array($o['validacao_estado'] ?? 'pendente',['pendente','rejeitada'],true)) || $_SESSION['perfil'] === 'supervisor');
    }

    public static function podeAtribuir(array $o): bool {
        return $_SESSION['perfil'] === 'supervisor' && self::podeConsultar($o) && $o['validacao_estado'] === 'aprovada'
            && !in_array($o['estado'],['resolvida','arquivada'],true) && ($o['conclusao_estado'] ?? 'sem_proposta') !== 'pendente';
    }

    public static function podeInvestigar(array $o): bool {
        return $_SESSION['perfil'] === 'investigador' && self::podeConsultar($o)
            && !in_array($o['estado'],['resolvida','arquivada'],true) && ($o['conclusao_estado'] ?? 'sem_proposta') !== 'pendente';
    }

    public static function validarDecisao(array $o, string $decisao, string $motivo): void {
        if ($_SESSION['perfil'] !== 'supervisor' || !self::podeConsultar($o)) throw new DomainException('Só o supervisor da esquadra pode validar esta ocorrência.');
        if (($o['validacao_estado'] ?? 'pendente') !== 'pendente') throw new DomainException('Esta ocorrência já foi validada. Reabra o registo para consultar a decisão.');
        if (!in_array($decisao,['aprovada','rejeitada'],true)) throw new DomainException('Decisão inválida.');
        if (in_array($o['estado'],['resolvida','arquivada'],true)) throw new DomainException('Uma ocorrência encerrada não pode ser validada.');
        if ($decisao === 'rejeitada' && trim($motivo) === '') throw new DomainException('Indique o motivo da rejeição.');
    }

    public static function validarProposta(array $o, string $texto, string $destino): void {
        if (!self::podeInvestigar($o)) throw new DomainException('Só o investigador responsável pode propor a conclusão de um caso activo.');
        if (!in_array($destino,['resolvida','arquivada'],true)) throw new DomainException('Seleccione um resultado final válido.');
        if ((mb_strlen(trim($texto)) < 10 || mb_strlen($texto)>10000)) throw new DomainException('Descreva a conclusão proposta com pelo menos 10 caracteres.');
    }

    public static function validarConclusao(array $o, string $decisao, string $parecer): void {
        if ($_SESSION['perfil'] !== 'supervisor' || !self::podeConsultar($o)) throw new DomainException('Só o supervisor da esquadra pode validar o encerramento.');
        if ($o['validacao_estado'] !== 'aprovada' || ($o['conclusao_estado'] ?? '') !== 'pendente' || in_array($o['estado'],['resolvida','arquivada'],true)) throw new DomainException('Este caso não tem uma proposta de conclusão pendente.');
        if (!in_array($decisao,['aprovar','devolver'],true)) throw new DomainException('Seleccione uma decisão válida.');
        if ((mb_strlen(trim($parecer)) < 10 || mb_strlen($parecer)>10000)) throw new DomainException('Registe um parecer com pelo menos 10 caracteres.');
    }

    public static function bloquear(int $id, $versao): array {
        global $pdo;
        $consulta = $pdo->prepare('SELECT * FROM ocorrencias WHERE id=? FOR UPDATE');
        $consulta->execute([$id]);
        $o = $consulta->fetch();
        if (!$o || !self::podeConsultar($o)) throw new DomainException('A ocorrência não está disponível para este utilizador.');
        if (!ctype_digit((string)$versao) || (int)$versao !== (int)$o['versao']) {
            throw new DomainException('Outro utilizador alterou esta ocorrência. Os seus dados não foram gravados. Reabra o registo e compare as alterações antes de tentar novamente.');
        }
        return $o;
    }

    public static function incrementarVersao(int $id): void {
        global $pdo;
        $consulta = $pdo->prepare('UPDATE ocorrencias SET versao=versao+1 WHERE id=?');
        $consulta->execute([$id]);
    }

    public static function auditarParticipantes(int $id, array $antes, array $depois): void {
        $indexar = static function(array $pessoas): array {
            $resultado = [];
            foreach ($pessoas as $pessoa) {
                $chave = $pessoa['id'];
                unset($pessoa['id'],$pessoa['ocorrencia_id'],$pessoa['created_at']);
                $resultado[$chave] = json_encode($pessoa,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }
            return $resultado;
        };
        $a = $indexar($antes); $d = $indexar($depois); $linhas = [];
        foreach ($a as $chave=>$valor) if (!isset($d[$chave])) $linhas[] = 'Removido: '.$valor;
        foreach ($d as $chave=>$valor) if (!isset($a[$chave])) $linhas[] = 'Adicionado: '.$valor;
        if ($linhas) (new Ocorrencia())->registarHistorico($id,$_SESSION['user_id'],'Participantes Actualizados',implode("\n",$linhas));
    }

    public static function auditar(int $id, array $antes, array $depois, string $grupo = 'Ocorrência'): void {
        $rotulos = ['tipo_id'=>'Tipo','tipo_peca'=>'Peça','data_ocorrencia'=>'Data','hora_ocorrencia'=>'Hora','bairro'=>'Bairro',
            'rua'=>'Rua','referencia_local'=>'Referência do local','descricao_factos'=>'Descrição dos factos','objetos_recuperados'=>'Objectos recuperados',
            'observacoes'=>'Observações','prioridade'=>'Prioridade','estado'=>'Estado','investigador_id'=>'Investigador',
            'latitude'=>'Latitude','longitude'=>'Longitude','natureza_dados'=>'Natureza dos dados','fonte_informacao'=>'Fonte da informação'];
        $linhas = [];
        foreach ($rotulos as $campo=>$rotulo) {
            $a = trim((string)($antes[$campo] ?? '')); $d = trim((string)($depois[$campo] ?? ''));
            if ($a !== $d) $linhas[] = $rotulo.': '.($a === ''?'(vazio)':$a).' → '.($d === ''?'(vazio)':$d);
        }
        if ($linhas) (new Ocorrencia())->registarHistorico($id,$_SESSION['user_id'],$grupo.' Actualizada',implode("\n",$linhas));
    }
}
