<?php
// Mapa: apresenta a vista; os pontos são consultados através da API.
class MapaController {
    // Prepara os dados e apresenta a página principal do módulo.
    public function index(): void {
        $titulo  = 'Mapa de Ocorrências';
        $paginaAtiva = 'mapa';
        renderView('mapa/index', [
            'titulo' => $titulo,
            'filtros' => ['natureza_dados'=>isset(OcorrenciaService::NATUREZAS[$_GET['natureza_dados'] ?? '']) ? $_GET['natureza_dados'] : ''],
            'paginaAtiva' => $paginaAtiva,
        ]);
    }
}
