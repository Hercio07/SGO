<?php
// Filtros e permissões chegam preparados pelo controller.
class RelatorioPdfService {
    public function gerar(array $dados): \Mpdf\Mpdf {
        $dados['logoBase64'] = DocumentoService::logo();
        $dados['rotulosFiltros'] = DocumentoService::filtros($dados['filtros']);
        ob_start();
        renderView('relatorios/pdf', $dados);
        $html = ob_get_clean();
        $pdf = DocumentoService::criarPdf('Relatório de Ocorrências', $dados['referencia'], 'L', 'RESERVADO - Uso exclusivo da PRM');
        $pdf->WriteHTML($html);
        return $pdf;
    }

    public function descarregar(array $dados): void {
        $this->gerar($dados)->Output('SIGOP_Relatorio_' . date('Y-m-d') . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
