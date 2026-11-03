<?php
// Padrão de apresentação partilhado pelos documentos PDF, impressos e Excel.
class DocumentoService {
    public const DESTAQUE = '9A5B27';
    public const TEXTO = '25313B';
    public const SUAVE = '596570';
    public const LINHA = 'CDD3D8';
    public const FUNDO = 'F3F5F6';

    public const ESTADOS = ['aberta'=>'Aberta','em_investigacao'=>'Em Investigação','resolvida'=>'Resolvida','arquivada'=>'Arquivada'];
    public const PRIORIDADES = ['baixa'=>'Baixa','media'=>'Média','alta'=>'Alta','critica'=>'Crítica'];
    public const PERFIS = ['admin'=>'Administrador','supervisor'=>'Supervisor','investigador'=>'Investigador','agente'=>'Agente'];

    public static function logo(): ?string {
        $caminho = ROOT . '/public/assets/img/logo.png';
        return is_file($caminho) ? 'data:image/png;base64,' . base64_encode(file_get_contents($caminho)) : null;
    }

    public static function emblema(): ?string {
        $caminho = ROOT . '/public/assets/img/emblema-mocambique.png';
        return is_file($caminho) ? 'data:image/png;base64,' . base64_encode(file_get_contents($caminho)) : null;
    }

    public static function texto($valor): string {
        $texto = trim((string)$valor);
        return $texto === '' ? '—' : $texto;
    }

    public static function emitente(): string {
        return trim(($_SESSION['nome'] ?? '') . (!empty($_SESSION['patente']) ? ' - ' . $_SESSION['patente'] : ''));
    }

    // Identifica os filtros efectivamente aplicados, sem alterar as consultas.
    public static function filtros(array $filtros): array {
        $tipo = !empty($filtros['tipo_id']) ? (new TipoOcorrencia())->buscarPorId((int)$filtros['tipo_id']) : null;
        $unidade = !empty($filtros['esquadra_id']) ? (new Esquadra())->buscarPorId((int)$filtros['esquadra_id']) : null;
        $linhas = [
            'Natureza dos dados' => OcorrenciaService::NATUREZAS[$filtros['natureza_dados'] ?? ''] ?? 'Todas (inclui dados por validar e simulados)',
            'Unidade' => $unidade['nome'] ?? (!empty($filtros['esquadra_id']) ? 'Unidade #'.$filtros['esquadra_id'] : 'Todas as esquadras'),
            'Tipo' => $tipo['nome'] ?? (!empty($filtros['tipo_id']) ? 'Tipo #'.$filtros['tipo_id'] : 'Todos os tipos'),
            'Estado' => self::ESTADOS[$filtros['estado'] ?? ''] ?? (($filtros['estado'] ?? '') ?: 'Todos os estados'),
            'Validação' => in_array($_SESSION['perfil'],['admin','investigador'],true) ? 'Aprovadas pelo supervisor' : (OcorrenciaService::VALIDACOES[$filtros['validacao_estado'] ?? ''] ?? 'Todas as validações'),
            'Conclusão' => OcorrenciaService::CONCLUSOES[$filtros['conclusao_estado'] ?? ''] ?? 'Todas as conclusões',
            'Prioridade' => self::PRIORIDADES[$filtros['prioridade'] ?? ''] ?? (($filtros['prioridade'] ?? '') ?: 'Todas as prioridades'),
        ];
        if (($_SESSION['perfil'] ?? '') === 'investigador') $linhas['Responsável'] = 'Apenas casos atribuídos a '.($_SESSION['nome'] ?? 'este investigador');
        if (!empty($filtros['pesquisa'])) $linhas['Pesquisa'] = $filtros['pesquisa'];
        if (!empty($filtros['agente_id'])) {
            $agente = (new Utilizador())->buscarPorId((int)$filtros['agente_id']);
            $linhas['Agente'] = $agente['nome'] ?? (string)$filtros['agente_id'];
        }
        return $linhas;
    }

    public static function criarPdf(string $titulo, string $referencia, string $orientacao = 'P', string $nota = ''): \Mpdf\Mpdf {
        $pdf = new \Mpdf\Mpdf([
            'mode'=>'utf-8','format'=>'A4','orientation'=>$orientacao,'default_font'=>'dejavusans','default_font_size'=>10,
            'margin_top'=>18,'margin_bottom'=>17,'margin_left'=>14,'margin_right'=>14,'margin_header'=>6,'margin_footer'=>7,
            'tempDir'=>sys_get_temp_dir(), 'shrink_tables_to_fit'=>1,
        ]);
        $pdf->SetTitle(APP_NAME . ' - ' . $titulo);
        $pdf->SetAuthor(APP_NAME);
        $h = static fn($v) => e((string)$v);
        $pdf->SetHTMLHeader('<table width="100%" style="font-family:dejavusans;font-size:8pt;color:#'.self::SUAVE.';border-bottom:0.4pt solid #'.self::LINHA.'"><tr><td>'.APP_NAME.' - '.$h($titulo).'</td><td style="text-align:right">'.$h($referencia).'</td></tr></table>');
        $pdf->SetHTMLFooter('<table width="100%" style="font-family:dejavusans;font-size:8pt;color:#'.self::SUAVE.';border-top:0.4pt solid #'.self::LINHA.'"><tr><td>'.$h($nota ?: COMMAND_NAME).'</td><td style="text-align:right">Página {PAGENO} de {nbpg}</td></tr></table>');
        return $pdf;
    }
}
