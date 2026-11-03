<?php
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Chart\AxisText;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\GridLines;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// A listagem alimenta as fórmulas, que por sua vez alimentam os gráficos nativos.
class RelatorioExcelService {
    private const ESCURO = '203741';
    private const CLARO = 'EEF3F5';
    private const LARGURAS = ['A'=>21,'B'=>21,'C'=>12,'D'=>8,'E'=>20,'F'=>18,'G'=>22,'H'=>12,'I'=>15,'J'=>20];

    public function gerar(array $dados): string {
        $livro = new Spreadsheet();
        $livro->getProperties()->setCreator(APP_NAME)->setTitle('Relatório de Ocorrências')->setSubject(ENTITY_NAME);
        $livro->getDefaultStyle()->getFont()->setName('Arial')->setSize(10)->getColor()->setRGB(DocumentoService::TEXTO);
        $dados['rotulosFiltros'] = DocumentoService::filtros($dados['filtros']);
        $resumo = $livro->getActiveSheet()->setTitle('Relatório');
        $detalhe = $livro->createSheet()->setTitle('Ocorrências');
        $estatisticas = $livro->createSheet()->setTitle('Estatísticas');
        foreach ([$resumo,$detalhe,$estatisticas] as $folha) self::configurar($folha,$dados['referencia']);
        foreach (range('A','Q') as $c) $resumo->getColumnDimension($c)->setWidth(in_array($c,['F','L']) ? 2 : 6.5);
        foreach (self::LARGURAS as $c=>$largura) $detalhe->getColumnDimension($c)->setWidth($largura);
        foreach (range('A','M') as $c) $estatisticas->getColumnDimension($c)->setWidth($c==='G' ? 3 : (in_array($c,['E','F','L','M']) ? 12 : 11));
        self::cabecalho($resumo,$dados,'Relatório de Ocorrências','Q','H','J');
        self::cabecalho($detalhe,$dados,'Listagem de Ocorrências','J','E','F');
        self::cabecalho($estatisticas,$dados,'Estatísticas das Ocorrências','M','F','H');
        self::detalhe($detalhe,$dados['ocorrencias']);
        $fontes = self::estatisticas($estatisticas,$dados);
        self::resumo($resumo,$fontes);
        $resumo->getTabColor()->setRGB(self::ESCURO);
        $detalhe->getTabColor()->setRGB(DocumentoService::DESTAQUE);
        $estatisticas->getTabColor()->setRGB('A6B4BB');
        $livro->setActiveSheetIndex(0);
        foreach ($livro->getAllSheets() as $folha) $folha->setSelectedCell('A1');

        $caminho = tempnam(sys_get_temp_dir(),'sigop_excel_');
        if ($caminho===false) throw new RuntimeException('Não foi possível criar o Excel temporário.');
        try {
            $writer = new Xlsx($livro);
            $writer->setIncludeCharts(true);
            $writer->save($caminho);
        } catch (Throwable $erro) {
            unlink($caminho);
            throw $erro;
        }
        return $caminho;
    }

    private static function configurar(Worksheet $folha,string $referencia): void {
        $folha->setShowGridlines(false);
        $folha->getSheetView()->setZoomScale(90);
        $folha->getDefaultRowDimension()->setRowHeight(18);
        $folha->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)->setFitToHeight(0);
        $folha->getPageMargins()->setTop(0.3)->setBottom(0.3)->setLeft(0.35)->setRight(0.35)->setHeader(0.1)->setFooter(0.1);
        $referencia = str_replace('&','&&',$referencia);
        $folha->getHeaderFooter()->setOddHeader('&L&"Arial"&8'.APP_NAME.'&R&"Arial"&8Ref. '.$referencia);
        $folha->getHeaderFooter()->setOddFooter('&L&"Arial"&8'.COMMAND_NAME.'&R&"Arial"&8Página &P de &N');
    }

    private static function cabecalho(Worksheet $folha,array $dados,string $titulo,string $fim,string $fimEsquerda,string $inicioDireita): void {
        self::fundo($folha,'A1:'.$fim.'1',DocumentoService::DESTAQUE);
        $folha->mergeCells('B2:'.$fim.'2')->setCellValue('B2',ENTITY_NAME);
        $folha->mergeCells('B3:'.$fim.'3')->setCellValue('B3',COMMAND_NAME);
        $folha->getStyle('B2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB(self::ESCURO);
        $folha->getStyle('B3')->getFont()->getColor()->setRGB(DocumentoService::SUAVE);
        $folha->mergeCells('A4:'.$fim.'4')->setCellValue('A4',$titulo);
        $folha->mergeCells('A5:'.$fim.'5')->setCellValue('A5','Período de '.dataOcorrenciaFormatada($dados['filtros']['data_inicio']).' a '.dataOcorrenciaFormatada($dados['filtros']['data_fim']));
        self::fundo($folha,'A4:'.$fim.'5',self::ESCURO);
        $folha->getStyle('A4')->getFont()->setBold(true)->setSize(18)->getColor()->setRGB('FFFFFF');
        $folha->getStyle('A5')->getFont()->getColor()->setRGB('DFE8EB');
        $folha->getStyle('A4:A5')->getAlignment()->setIndent(1);
        $f=$dados['rotulosFiltros'];
        $folha->mergeCells('A7:'.$fimEsquerda.'7')->setCellValue('A7','Unidade: '.$f['Unidade']);
        $folha->mergeCells($inicioDireita.'7:'.$fim.'7')->setCellValue($inicioDireita.'7','Tipo: '.$f['Tipo']);
        $contexto='Estado: '.$f['Estado'].'   ·   Prioridade: '.$f['Prioridade'];
        foreach ($f as $k=>$v) if (!in_array($k,['Unidade','Tipo','Estado','Prioridade'])) $contexto.='   ·   '.$k.': '.$v;
        $folha->mergeCells('A8:'.$fim.'8')->setCellValue('A8',$contexto);
        $folha->mergeCells('A9:'.$fimEsquerda.'9')->setCellValue('A9','Ref. '.$dados['referencia']);
        $folha->mergeCells($inicioDireita.'9:'.$fim.'9')->setCellValue($inicioDireita.'9','Emitido em '.date('d/m/Y H:i'));
        $folha->getStyle($inicioDireita.'9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $folha->getStyle('A7:'.$fim.'9')->getFont()->setSize(9)->getColor()->setRGB(DocumentoService::SUAVE);
        $folha->getStyle('A9:'.$fim.'9')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(DocumentoService::LINHA);
        $folha->getStyle('A1:'.$fim.'9')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        foreach ([1=>5,2=>24,3=>20,4=>30,5=>22,6=>6,7=>21,8=>19,9=>21] as $r=>$h) $folha->getRowDimension($r)->setRowHeight($h);
        $largura=0;
        foreach (range('A',$fim) as $c) $largura+=$folha->getColumnDimension($c)->getWidth();
        $folha->getRowDimension(8)->setRowHeight(max(19,self::linhasTexto($contexto,(int)$largura)*12+5));
        $larguraEsquerda=0;$larguraDireita=0;
        foreach (range('A',$fimEsquerda) as $c) $larguraEsquerda+=$folha->getColumnDimension($c)->getWidth();
        foreach (range($inicioDireita,$fim) as $c) $larguraDireita+=$folha->getColumnDimension($c)->getWidth();
        $folha->getRowDimension(7)->setRowHeight(max(21,self::linhasTexto('Unidade: '.$f['Unidade'],(int)$larguraEsquerda)*12+5,self::linhasTexto('Tipo: '.$f['Tipo'],(int)$larguraDireita)*12+5));
        if (!empty($dados['logoPath']) && is_file($dados['logoPath'])) {
            $logo=new Drawing();
            $logo->setName('Logótipo da PRM')->setPath($dados['logoPath'])->setHeight(42)->setCoordinates('A2')->setOffsetX(6)->setOffsetY(5)->setWorksheet($folha);
        }
    }

    private static function resumo(Worksheet $folha,array $fontes): void {
        $metricas=[
            ['Total de ocorrências','=COUNTA(Table1[Número])'],
            ['Em aberto','=COUNTIF(Table1[Estado],"Aberta")'],
            ['Em investigação','=COUNTIF(Table1[Estado],"Em Investigação")'],
            ['Resolvidas','=COUNTIF(Table1[Estado],"Resolvida")'],
            ['Arquivadas','=COUNTIF(Table1[Estado],"Arquivada")'],
            ['Críticas activas','=COUNTIFS(Table1[Prioridade],"Crítica",Table1[Estado],"<>Resolvida",Table1[Estado],"<>Arquivada")'],
        ];
        foreach ($metricas as $i=>[$rotulo,$formula]) {
            $inicio=['A','G','M'][$i%3];$fim=['E','K','Q'][$i%3];$r=$i<3?11:14;
            self::fundo($folha,$inicio.$r.':'.$fim.($r+1),$i===0?self::ESCURO:self::CLARO);
            $folha->mergeCells($inicio.$r.':'.$fim.$r)->setCellValue($inicio.$r,$rotulo);
            $folha->mergeCells($inicio.($r+1).':'.$fim.($r+1))->setCellValue($inicio.($r+1),$formula);
            $folha->getStyle($inicio.$r)->getFont()->setSize(10)->getColor()->setRGB($i===0?'DFE8EB':DocumentoService::SUAVE);
            $folha->getStyle($inicio.($r+1))->getFont()->setBold(true)->setSize(22)->getColor()->setRGB($i===0?'FFFFFF':self::ESCURO);
            $folha->getStyle($inicio.$r.':'.$fim.($r+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
            $folha->getStyle($inicio.($r+1))->getNumberFormat()->setFormatCode('#,##0');
            $folha->getStyle($inicio.$r.':'.$fim.$r)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($i===0?DocumentoService::DESTAQUE:'C4D1D7');
        }
        foreach ([10=>8,11=>19,12=>27,13=>8,14=>19,15=>27,16=>9,17=>20] as $r=>$h) $folha->getRowDimension($r)->setRowHeight($h);
        self::seccao($folha,'A17:Q17','Evolução e tipos de ocorrência');
        for($r=18;$r<=35;$r++) $folha->getRowDimension($r)->setRowHeight(11);
        self::colocarGrafico($folha,$fontes['evolucao'],'A18','H35',true);
        self::colocarGrafico($folha,$fontes['tipo'],'J18','Q35');
        $folha->mergeCells('A36:Q36')->setCellValue('A36','Os gráficos por tipo e bairro apresentam até 10 categorias. A evolução apresenta até 24 meses, conforme os filtros.');
        $folha->getStyle('A36')->getFont()->setSize(8)->getColor()->setRGB(DocumentoService::SUAVE);
        $folha->getRowDimension(36)->setRowHeight(16);
        $folha->getRowDimension(37)->setRowHeight(6);
        if ($fontes['esquadra']['quantidade'] || $fontes['bairro']['quantidade']) {
            $folha->setBreak('A38',Worksheet::BREAK_ROW);
            self::seccao($folha,'A38:Q38','Distribuição territorial');
            $folha->getRowDimension(38)->setRowHeight(27);
            for($r=39;$r<=64;$r++) $folha->getRowDimension($r)->setRowHeight(12);
            self::colocarGrafico($folha,$fontes['esquadra'],'A39','H64');
            self::colocarGrafico($folha,$fontes['bairro'],'J39','Q64');
            $folha->getPageSetup()->setPrintArea('A1:Q65');
        } else {
            $folha->getPageSetup()->setPrintArea('A1:Q36');
        }
        $folha->getPageSetup()->setRowsToRepeatAtTop([1,9]);
    }

    private static function detalhe(Worksheet $folha,array $ocorrencias): void {
        $folha->fromArray(['Número','Tipo','Data','Hora','Bairro','Esquadra','Agente','Prioridade','Estado','Natureza dos dados'],null,'A11');
        self::cabecalhoTabela($folha,'A11:J11');$folha->getRowDimension(11)->setRowHeight(28);
        $r=12;
        foreach ($ocorrencias as $o) {
            $valores=['A'=>$o['numero'],'B'=>DocumentoService::texto($o['tipo_nome']??null),'E'=>DocumentoService::texto($o['bairro']??null),
                'F'=>DocumentoService::texto($o['esquadra_nome']??null),'G'=>DocumentoService::texto($o['agente_nome']??null),
                'H'=>DocumentoService::PRIORIDADES[$o['prioridade']]??$o['prioridade'],'I'=>DocumentoService::ESTADOS[$o['estado']]??$o['estado'],'J'=>OcorrenciaService::NATUREZAS[$o['natureza_dados'] ?? 'por_validar']];
            $linhas=1;
            foreach ($valores as $c=>$v) {
                $folha->setCellValueExplicit($c.$r,$v,DataType::TYPE_STRING);
                $linhas=max($linhas,self::linhasTexto($v,(int)self::LARGURAS[$c]-2));
            }
            if (!empty($o['data_ocorrencia'])) $folha->setCellValue('C'.$r,ExcelDate::PHPToExcel(new DateTimeImmutable($o['data_ocorrencia'])));
            if (!empty($o['hora_ocorrencia'])) {
                $h=array_map('intval',explode(':',$o['hora_ocorrencia']));
                $folha->setCellValue('D'.$r,($h[0]*3600+$h[1]*60+($h[2]??0))/86400);
            }
            $folha->getStyle('C'.$r)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $folha->getStyle('D'.$r)->getNumberFormat()->setFormatCode('hh:mm');
            $folha->getStyle('C'.$r.':D'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $folha->getStyle('A'.$r.':J'.$r)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $folha->getRowDimension($r)->setRowHeight(max(28,$linhas*13+9));
            self::linhaTabela($folha,'A'.$r.':J'.$r,$r-12);
            $r++;
        }
        // Duas linhas mantêm os intervalos válidos no cálculo de exportações com zero ou um registo.
        $ultima=max(13,$r-1);
        // O nome coincide com o identificador emitido pela biblioteca, para outros leitores de Excel.
        $tabela=new Table('A11:J'.$ultima,'Table1');
        $tabela->setStyle((new TableStyle(TableStyle::TABLE_STYLE_NONE))->setShowRowStripes(false));
        $folha->addTable($tabela);
        if (!$ocorrencias) {
            $folha->mergeCells('A14:J14')->setCellValue('A14','Nenhuma ocorrência corresponde aos filtros seleccionados.');
            $folha->getRowDimension(14)->setRowHeight(27);$r=15;
        }
        $folha->mergeCells('A'.($r+1).':J'.($r+1))->setCellValue('A'.($r+1),'="Total da listagem: "&COUNTA(Table1[Número])&" ocorrência(s)."');
        $folha->getStyle('A'.($r+1))->getFont()->setBold(true)->getColor()->setRGB(self::ESCURO);
        foreach (['H'=>['Crítica','9B3531','FBEFEE'],'I'=>['Resolvida','2F6558','EDF5F2']] as $c=>[$texto,$cor,$fundo]) {
            $regra=new Conditional();
            $regra->setConditionType(Conditional::CONDITION_CELLIS)->setOperatorType(Conditional::OPERATOR_EQUAL)->addCondition('"'.$texto.'"');
            $regra->getStyle()->getFont()->setBold(true)->getColor()->setRGB($cor);
            $regra->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fundo);
            $folha->getStyle($c.'12:'.$c.$ultima)->setConditionalStyles([$regra]);
        }
        $folha->freezePane('B12');
        $folha->getPageSetup()->setPrintArea('A1:J'.($r+1))->setRowsToRepeatAtTop([11,11]);
    }

    private static function estatisticas(Worksheet $folha,array $dados): array {
        $fontes=[];
        $fontes['tipo']=self::distribuicao($folha,11,'A','D','E','F','Ocorrências por tipo','Tipo',$dados['porTipo'],'nome','Tipo');
        $fontes['esquadra']=self::distribuicao($folha,11,'H','K','L','M','Ocorrências por esquadra','Esquadra',$dados['porEsquadra'],'nome','Esquadra');
        $linha=max($fontes['tipo']['fim'],$fontes['esquadra']['fim'])+2;
        $folha->getRowDimension($linha-1)->setRowHeight(8);
        $folha->setBreak('A'.$linha,Worksheet::BREAK_ROW);
        $fontes['bairro']=self::distribuicao($folha,$linha,'A','D','E','F','Bairros com mais ocorrências','Bairro',$dados['porBairro'],'bairro','Bairro');
        $fontes['evolucao']=self::distribuicao($folha,$linha,'H','K','L','M','Evolução mensal','Mês',$dados['evolucao'],'label','Data');
        $fim=max($fontes['bairro']['fim'],$fontes['evolucao']['fim'])+3;
        $folha->mergeCells('A'.$fim.':M'.$fim)->setCellValue('A'.$fim,'Percentagens calculadas sobre o total de ocorrências da listagem. As categorias correspondem à exportação seleccionada.');
        $folha->getStyle('A'.$fim)->getFont()->setSize(9)->getColor()->setRGB(DocumentoService::SUAVE);
        $folha->getRowDimension($fim)->setRowHeight(28);
        $folha->getStyle('A'.$fim)->getAlignment()->setWrapText(true);
        $folha->getPageSetup()->setPrintArea('A1:M'.$fim)->setRowsToRepeatAtTop([1,9]);
        return $fontes;
    }

    private static function distribuicao(Worksheet $folha,int $r,string $inicio,string $fimLabel,string $numero,string $percentagem,string $titulo,string $rotulo,array $dados,string $chave,string $colunaTabela): array {
        self::seccao($folha,$inicio.$r.':'.$percentagem.$r,$titulo);
        $folha->getRowDimension($r)->setRowHeight(24);
        $cab=$r+1;$primeira=$r+2;$atual=$primeira;
        $folha->mergeCells($inicio.$cab.':'.$fimLabel.$cab)->setCellValue($inicio.$cab,$rotulo);
        $folha->setCellValue($numero.$cab,'Total')->setCellValue($percentagem.$cab,'% do total');
        self::cabecalhoTabela($folha,$inicio.$cab.':'.$percentagem.$cab);
        $folha->getRowDimension($cab)->setRowHeight(23);
        foreach ($dados as $d) {
            $texto=DocumentoService::texto($d[$chave]??null);
            $folha->mergeCells($inicio.$atual.':'.$fimLabel.$atual)->setCellValueExplicit($inicio.$atual,$texto,DataType::TYPE_STRING);
            if ($colunaTabela==='Data') {
                $data=new DateTimeImmutable($d['periodo'].'-01');
                $de=ExcelDate::PHPToExcel($data);$ate=ExcelDate::PHPToExcel($data->modify('+1 month'));
                $formula='=COUNTIFS(Table1[Data],">='.$de.'",Table1[Data],"<'.$ate.'")';
            } else $formula='=COUNTIF(Table1['.$colunaTabela.'],'.$inicio.$atual.')';
            $folha->setCellValue($numero.$atual,$formula);
            $folha->setCellValue($percentagem.$atual,'=IFERROR('.$numero.$atual.'/COUNTA(Table1[Número]),0)');
            $folha->getStyle($numero.$atual)->getNumberFormat()->setFormatCode('#,##0');
            $folha->getStyle($percentagem.$atual)->getNumberFormat()->setFormatCode('0.0%');
            $folha->getStyle($numero.$atual.':'.$percentagem.$atual)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $folha->getStyle($inicio.$atual.':'.$percentagem.$atual)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $folha->getRowDimension($atual)->setRowHeight(max(20,$folha->getRowDimension($atual)->getRowHeight(),self::linhasTexto($texto,40)*12+6));
            self::linhaTabela($folha,$inicio.$atual.':'.$percentagem.$atual,$atual-$primeira);
            $atual++;
        }
        if (!$dados) {
            $folha->mergeCells($inicio.$atual.':'.$percentagem.$atual)->setCellValue($inicio.$atual,'Sem dados no período');
            $folha->getRowDimension($atual)->setRowHeight(25);$atual++;
        }
        return ['titulo'=>$titulo,'label'=>$inicio.$primeira.':'.$inicio.max($primeira,$atual-1),'valores'=>$numero.$primeira.':'.$numero.max($primeira,$atual-1),
            'cabecalho'=>$numero.$cab,'quantidade'=>count($dados),'fim'=>$atual-1];
    }

    private static function colocarGrafico(Worksheet $folha,array $fonte,string $inicio,string $fim,bool $linha=false): void {
        if (!$fonte['quantidade']) {
            $folha->mergeCells($inicio.':'.$fim)->setCellValue($inicio,'Sem dados para '.$fonte['titulo']);
            $folha->getStyle($inicio)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            return;
        }
        $grafico=self::criarGrafico($fonte,$linha);
        $grafico->setTopLeftPosition($inicio,0,5)->setBottomRightPosition($fim,0,0);
        $folha->addChart($grafico);
    }

    private static function criarGrafico(array $fonte,bool $linha): Chart {
        $prefixo="'Estatísticas'!";
        $labels=[new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING,$prefixo.$fonte['cabecalho'],null,1)];
        $categorias=[new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING,$prefixo.$fonte['label'],null,$fonte['quantidade'])];
        $valores=new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER,$prefixo.$fonte['valores'],'0',$fonte['quantidade'],[], $linha?'circle':null,$linha?DocumentoService::DESTAQUE:self::ESCURO);
        if ($linha) $valores->setLineWidth(2);
        $tipo=$linha?DataSeries::TYPE_LINECHART:DataSeries::TYPE_BARCHART;
        $serie=new DataSeries($tipo,$linha?DataSeries::GROUPING_STANDARD:DataSeries::GROUPING_CLUSTERED,[0],$labels,$categorias,[$valores]);
        if (!$linha) $serie->setPlotDirection(DataSeries::DIRECTION_BAR);
        $layout=new Layout();
        $layout->setShowVal(!$linha)->setShowLegendKey(false)->setShowSerName(false)->setShowCatName(false);
        $layout->setLabelFont((new Font())->setName('Arial')->setSize(9));
        $plot=new PlotArea($layout,[$serie]);$plot->setGapWidth(65);
        $titulo=new Title($fonte['titulo']);
        $titulo->setFont((new Font())->setName('Arial')->setSize(11)->setBold(true));
        $grafico=new Chart('grafico_'.md5($fonte['titulo']),$titulo,null,$plot);
        $grafico->setNoBorder(true)->setLang('pt-PT');
        $grafico->getFillColor()->setColorProperties('FFFFFF');
        foreach ([$grafico->getChartAxisX(),$grafico->getChartAxisY()] as $eixo) {
            $texto=new AxisText();$texto->setRotation(0);$texto->getFont()->setName('Arial')->setSize(9);
            $texto->getFillColorObject()->setColorProperties(DocumentoService::SUAVE);
            $eixo->setAxisText($texto);
            $eixo->setLineColorProperties('CAD5DA');
        }
        $grafico->getChartAxisY()->setAxisNumberProperties('0',true,0);
        $grafico->getChartAxisY()->setAxisOption('minimum',0);
        $grelha=new GridLines();$grelha->setLineColorProperties('E6ECEF');
        $grafico->getChartAxisY()->setMajorGridlines($grelha);
        if (!$linha) {
            $grafico->getChartAxisX()->setAxisOrientation('maxMin');
            $grafico->getChartAxisY()->setAxisOption('horizontal_crosses','max');
        }
        return $grafico;
    }

    private static function seccao(Worksheet $folha,string $intervalo,string $texto): void {
        $folha->mergeCells($intervalo)->setCellValue(explode(':',$intervalo)[0],$texto);
        $folha->getStyle($intervalo)->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(self::ESCURO);
        $folha->getStyle($intervalo)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(DocumentoService::LINHA);
    }

    private static function fundo(Worksheet $folha,string $intervalo,string $cor): void {
        $folha->getStyle($intervalo)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($cor);
    }

    private static function cabecalhoTabela(Worksheet $folha,string $intervalo): void {
        $folha->getStyle($intervalo)->applyFromArray([
            'font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF'],'size'=>10],
            'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::ESCURO]],
            'alignment'=>['horizontal'=>Alignment::HORIZONTAL_LEFT,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
            'borders'=>['inside'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'FFFFFF']]],
        ]);
    }

    private static function linhaTabela(Worksheet $folha,string $intervalo,int $indice): void {
        $folha->getStyle($intervalo)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('DCE4E8');
        if ($indice%2) self::fundo($folha,$intervalo,'F3F6F8');
    }

    private static function linhasTexto(string $texto,int $largura): int {
        $linhas=0;
        foreach (preg_split('/\R/u',$texto) as $parte) $linhas+=max(1,(int)ceil(mb_strwidth($parte,'UTF-8')/max(1,$largura)));
        return $linhas;
    }
}


