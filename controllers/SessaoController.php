<?php
// Endpoints da sessão: consultar o prazo e confirmar uma interacção real na página.
class SessaoController {
    public function __construct() {
        header('Content-Type: application/json; charset=UTF-8');
    }

    private function validarPedido(string $metodo): bool {
        if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
            http_response_code(405);
            header('Allow: '.$metodo);
            echo json_encode(['erro'=>'Método não permitido.']);
            return false;
        }
        $identificador = $_SERVER['HTTP_X_SESSAO_ID'] ?? '';
        if (!$identificador || !hash_equals($_SESSION['sessao_identificador'],$identificador)) {
            http_response_code(409);
            echo json_encode(['ativo'=>false,'erro'=>'sessao_alterada','login'=>BASE_URL.'/login']);
            return false;
        }
        return true;
    }

    public function estado(): void {
        if (!$this->validarPedido('GET')) return;
        echo json_encode(SessaoService::estado());
    }

    public function atividade(): void {
        if (!$this->validarPedido('POST')) return;
        $atraso = filter_var($_POST['atraso'] ?? '0',FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>SESSION_TIMEOUT]]);
        if ($atraso === false) {
            http_response_code(400);
            echo json_encode(['erro'=>'Tempo de actividade inválido.']);
            return;
        }
        SessaoService::registarAtividade($atraso);
        echo json_encode(SessaoService::estado());
    }
}
