<style>
* { box-sizing: border-box; }
body { margin:0; font-family:DejaVu Sans, Arial, sans-serif; font-size:10pt; line-height:1.45; color:#<?= DocumentoService::TEXTO ?>; }
p { margin:0 0 3mm; }
.doc-header { width:100%; border-collapse:collapse; margin:0 0 5mm; border-bottom:1pt solid #<?= DocumentoService::DESTAQUE ?>; }
.doc-header td { border:0; padding:0 0 4mm; vertical-align:middle; }
.doc-logo { width:18mm; }
.doc-logo img { width:13mm; height:13mm; }
.doc-institution { font-size:11pt; font-weight:bold; }
.doc-command, .doc-system { font-size:8.5pt; color:#<?= DocumentoService::SUAVE ?>; margin-top:1mm; }
.doc-formal-header { text-align:center; margin-bottom:6mm; page-break-inside:avoid; }
.doc-formal-header img { width:14mm; height:auto; margin-bottom:2mm; }
.doc-formal-header .country { font-size:11pt; font-weight:bold; margin-bottom:2mm; }
.doc-formal-header .formal-line { font-size:10pt; line-height:1.45; }
.doc-title { font-size:15pt; line-height:1.25; margin:0 0 2mm; font-weight:bold; page-break-after:avoid; }
.doc-subtitle { font-size:10pt; color:#<?= DocumentoService::SUAVE ?>; margin:0 0 4mm; page-break-after:avoid; }
.doc-meta { width:100%; border-collapse:collapse; margin-bottom:4mm; }
.doc-meta td { padding:2mm 2.5mm; border-bottom:0.4pt solid #<?= DocumentoService::LINHA ?>; font-size:9pt; vertical-align:top; }
.doc-meta .label { font-size:8pt; color:#<?= DocumentoService::SUAVE ?>; display:block; margin-bottom:1mm; }
.doc-section { font-size:11pt; margin:5mm 0 2.5mm; padding-bottom:1.5mm; border-bottom:0.5pt solid #<?= DocumentoService::LINHA ?>; font-weight:bold; page-break-after:avoid; }
.doc-table { width:100%; border-collapse:collapse; table-layout:fixed; margin-bottom:3mm; }
.doc-table thead { display:table-header-group; }
.doc-table th { background:#<?= DocumentoService::FUNDO ?>; color:#<?= DocumentoService::TEXTO ?>; font-size:9pt; font-weight:bold; text-align:left; padding:2.4mm 2mm; border-top:0.6pt solid #<?= DocumentoService::LINHA ?>; border-bottom:0.6pt solid #<?= DocumentoService::LINHA ?>; }
.doc-table td { padding:1.5mm 1.8mm; border-bottom:0.35pt solid #<?= DocumentoService::LINHA ?>; font-size:9pt; line-height:1.25; vertical-align:top; word-wrap:break-word; overflow-wrap:anywhere; }
.doc-table tr { page-break-inside:avoid; }
.doc-table .shade td { background:#FAFBFC; }
.doc-table .number, .number { text-align:right; }
.doc-table .date, .date { text-align:center; }
.doc-table td.date { white-space:nowrap; }
.doc-table .total td { font-weight:bold; background:#<?= DocumentoService::FUNDO ?>; border-top:0.6pt solid #<?= DocumentoService::LINHA ?>; }
.doc-text { font-size:10pt; line-height:1.55; white-space:pre-wrap; word-wrap:break-word; overflow-wrap:anywhere; margin-bottom:4mm; }
.doc-note { font-size:8.5pt; line-height:1.4; color:#<?= DocumentoService::SUAVE ?>; }
.doc-empty { text-align:center; padding:5mm; color:#<?= DocumentoService::SUAVE ?>; }
.doc-signatures { width:100%; border-collapse:collapse; margin-top:9mm; page-break-inside:avoid; }
.doc-signatures td { width:50%; padding:7mm 7mm 0; border:0; text-align:center; font-size:9pt; vertical-align:top; }
.doc-signatures td.signature-space { width:10%; padding:0; border:0; }
.doc-signatures td.signature-entry { width:35%; padding:2mm 0 0; border:0; border-top:0.5pt solid #<?= DocumentoService::TEXTO ?>; }
.doc-footer { border-top:0.5pt solid #<?= DocumentoService::LINHA ?>; margin-top:6mm; padding-top:2mm; font-size:8pt; color:#<?= DocumentoService::SUAVE ?>; }
.keep-together { page-break-inside:avoid; }
.report-stats { width:100%; border-collapse:collapse; margin:2mm 0 5mm; page-break-inside:avoid; }
.report-stats td { width:16.66%; padding:2.5mm 2mm; border-bottom:0.5pt solid #<?= DocumentoService::LINHA ?>; vertical-align:top; }
.report-stat-value { font-size:16pt; font-weight:bold; }
.report-stat-label { font-size:8.5pt; color:#<?= DocumentoService::SUAVE ?>; margin-top:1mm; }
.report-charts { width:100%; border-collapse:collapse; }
.report-charts td { width:50%; padding:2mm 3mm; vertical-align:top; }
.report-charts img { width:120mm; height:auto; }
.report-chart-title { font-size:10pt; font-weight:bold; margin-bottom:2mm; }
.report-page { page-break-before:always; }
.auto-body { font-family:dejavuserif, Times New Roman, serif; font-size:11pt; line-height:1.45; }
.auto-body .doc-formal-header { font-family:DejaVu Sans, Arial, sans-serif; }
.auto-title { font-size:12pt; font-weight:bold; text-align:center; margin:4mm 0 6mm; page-break-after:avoid; }
.auto-paragraph { text-align:justify; margin-bottom:4mm; overflow-wrap:anywhere; }
.auto-narrative { text-align:justify; white-space:pre-wrap; line-height:1.45; overflow-wrap:anywhere; }
.auto-field { padding:1mm 0; border-bottom:0.35pt solid #<?= DocumentoService::LINHA ?>; }
.auto-field-label { color:#<?= DocumentoService::SUAVE ?>; font-size:9pt; }
.auto-signature { margin-top:9mm; page-break-inside:avoid; }
.auto-signature-line { display:inline-block; border-bottom:0.5pt solid #<?= DocumentoService::TEXTO ?>; width:65mm; margin-left:2mm; }
.auto-info { margin-bottom:2.5mm; line-height:1.6; }
.auto-inline-field { margin-right:4mm; }
.auto-value { border-bottom:0.35pt dotted #<?= DocumentoService::SUAVE ?>; padding:0 1mm; }
.auto-classification { text-align:center; margin:3mm 0; page-break-inside:avoid; }
.auto-classification .texto { font-weight:bold; font-size:11pt; }
.auto-classification .legenda { font-family:DejaVu Sans,Arial,sans-serif; font-size:8.5pt; color:#<?= DocumentoService::SUAVE ?>; }
.auto-case { margin:4mm 0; page-break-inside:avoid; }
.auto-case table { width:100%; border-collapse:collapse; }
.auto-case td { padding:2mm; font-size:10pt; vertical-align:top; border-bottom:0.4pt solid #<?= DocumentoService::LINHA ?>; }
.auto-case .rotulo { width:25%; font-family:DejaVu Sans,Arial,sans-serif; font-size:9pt; color:#<?= DocumentoService::SUAVE ?>; }
.auto-signature-name { font-weight:bold; margin-top:1mm; }
.print-body { background:#ECEFF1; }
.print-page { background:white; max-width:210mm; margin:6mm auto; padding:16mm 14mm; }
.print-toolbar { padding:12px 20px; background:#<?= DocumentoService::TEXTO ?>; color:white; display:flex; gap:12px; align-items:center; font:14px Arial,sans-serif; }
.print-toolbar span { flex:1; }
.print-toolbar button { padding:8px 14px; border:1px solid #CDD3D8; background:white; color:#25313B; cursor:pointer; font-size:13px; }
@media print {
  @page { size:A4 portrait; margin:16mm 14mm 18mm; @bottom-right { content:"Página " counter(page) " de " counter(pages); font:8pt Arial,sans-serif; color:#596570; } }
  .print-body { background:white; }
  .print-toolbar { display:none !important; }
  .print-page { max-width:none; width:auto; margin:0; padding:0; }
}
</style>
