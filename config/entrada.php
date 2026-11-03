<?php
// Valores HTTP são texto: valida os caracteres e converte apenas campos numéricos conhecidos.
function inteiroEntrada(mixed $valor, int $maximo = 4294967295): int {
    if (!is_int($valor) && !is_string($valor)) throw new InvalidArgumentException('Valor inteiro inválido.');
    $inteiro = filter_var($valor,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>$maximo]]);
    if ($inteiro === false) throw new InvalidArgumentException('Valor inteiro inválido.');
    return $inteiro;
}

function textoEntradaValido(mixed $valor): bool {
    return is_string($valor) && mb_check_encoding($valor,'UTF-8') && !str_contains($valor,"\0");
}

// Nomes e localidades conservam a grafia introduzida, incluindo acentos e apóstrofos.
function textoNoIntervalo(string $valor, int $minimo, int $maximo): bool {
    if (!textoEntradaValido($valor) || preg_match('/[\x00-\x1F\x7F]/u',$valor)) return false;
    $tamanho = mb_strlen(trim($valor),'UTF-8');
    return $tamanho >= $minimo && $tamanho <= $maximo;
}

function tiparEntrada(array $dados, bool $pesquisa = false): array {
    foreach ($dados as $campo=>$valor) {
        if (!$pesquisa && in_array($campo,['participantes','participantes_remover'],true) && !is_array($valor)) {
            throw new InvalidArgumentException('Dados dos participantes inválidos.');
        }
        if (!$pesquisa && $campo === 'participantes' && is_array($valor)) {
            foreach ($valor as $participante) {
                if (!is_array($participante)) throw new InvalidArgumentException('Dados dos participantes inválidos.');
                foreach ($participante as $dado) if (!textoEntradaValido($dado)) throw new InvalidArgumentException('Campo do participante inválido.');
            }
        } elseif (!$pesquisa && $campo === 'participantes_remover' && is_array($valor)) {
            foreach ($valor as $id) {
                if (!is_string($id) || !ctype_digit($id) || inteiroEntrada($id) <= 0) throw new InvalidArgumentException('Identificador do participante inválido.');
            }
            $dados[$campo] = array_map('inteiroEntrada',$valor);
        } elseif (!textoEntradaValido($valor)) {
            throw new InvalidArgumentException($pesquisa ? 'Parâmetro de pesquisa inválido.' : 'Campo do formulário inválido.');
        }
    }
    $camposInteiros = $pesquisa
        ? ['esquadra','agente','pagina','pagina_tipos','utilizador','ocorrencia_id']
        : ['id','utilizador_id','user_id','agente_id','esquadra_id','tipo_id','investigador_id','anexo_id','versao','versao_rascunho'];
    foreach ($camposInteiros as $campo) {
        if (array_key_exists($campo,$dados)) $dados[$campo] = $dados[$campo] === '' ? null : inteiroEntrada($dados[$campo]);
    }
    if (array_key_exists('ativo',$dados)) $dados['ativo'] = $dados['ativo'] === '' ? null : inteiroEntrada($dados['ativo'],1);
    return $dados;
}

function validarEntradaHttp(): void {
    try {
        $_GET = tiparEntrada($_GET,true);
        $_POST = tiparEntrada($_POST);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        exit($e->getMessage());
    }
}
