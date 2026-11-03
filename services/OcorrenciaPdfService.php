<?php
// Mantém a geração separada do envio, permitindo inspeccionar os mesmos documentos.
class OcorrenciaPdfService {
    public function gerarAuto(array $ocorrencia, array $participantes): \Mpdf\Mpdf {
        ob_start();
        renderView('ocorrencias/auto', [
            'ocorrencia'=>$ocorrencia, 'participantes'=>$participantes,
        ]);
        $html = ob_get_clean();
        $titulos = ['auto_noticia'=>'Auto de Notícia','auto_denuncia'=>'Auto de Denúncia','participacao'=>'Participação','informacao'=>'Informação'];
        $pdf = DocumentoService::criarPdf($titulos[$ocorrencia['tipo_peca']] ?? 'Auto', $ocorrencia['numero'], 'P', 'Documento oficial - Uso exclusivo da PRM');
        $pdf->WriteHTML($html);
        return $pdf;
    }

    public function descarregarAuto(array $ocorrencia, array $participantes): void {
        $this->gerarAuto($ocorrencia, $participantes)->Output('Auto_' . $ocorrencia['numero'] . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
