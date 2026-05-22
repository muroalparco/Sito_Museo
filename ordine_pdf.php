<?php
/**
 * PDF riepilogo ordine - versione stabile per allegato email.
 * Non usa HTML/CSS: evita riquadri vuoti, testi sovrapposti o schiacciati nei client email.
 */

function pdfNorm(string $text): string {
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\r", "\n", "\t"], ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;
    $text = str_replace(['€', '–', '—', '“', '”', '’', 'à', 'è', 'é', 'ì', 'ò', 'ù'], ['EUR', '-', '-', '"', '"', "'", 'a', 'e', 'e', 'i', 'o', 'u'], $text);

    $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
    if ($converted !== false) {
        $text = $converted;
    }

    return trim($text);
}

function pdfEscape(string $text): string {
    $text = pdfNorm($text);
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function pdfColor(string $hex): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
        return '0 0 0';
    }
    return sprintf(
        '%.4F %.4F %.4F',
        hexdec(substr($hex, 0, 2)) / 255,
        hexdec(substr($hex, 2, 2)) / 255,
        hexdec(substr($hex, 4, 2)) / 255
    );
}

function pdfWrapText(string $text, int $maxChars): array {
    $text = pdfNorm($text);
    if ($text === '') {
        return ['-'];
    }

    $righe = explode("\n", wordwrap($text, $maxChars, "\n", true));
    return array_values(array_filter(array_map('trim', $righe), fn($r) => $r !== ''));
}

class PdfOrdineBuilder {
    private array $pages = [];
    private string $current = '';
    private float $y = 0;
    private int $pageNo = 0;
    private const W = 595.0;
    private const H = 842.0;

    public function __construct() {
        $this->addPage();
    }

    private function raw(string $cmd): void {
        $this->current .= $cmd;
    }

    public function addPage(): void {
        if ($this->current !== '') {
            $this->footer();
            $this->pages[] = $this->current;
        }

        $this->pageNo++;
        $this->current = '';
        $this->y = 690;
        $this->header();
    }

    public function rect(float $x, float $y, float $w, float $h, string $fill = '', string $stroke = '', float $lineWidth = 1): void {
        if ($fill !== '') {
            $this->raw(pdfColor($fill) . " rg\n");
        }
        if ($stroke !== '') {
            $this->raw(pdfColor($stroke) . " RG\n" . sprintf("%.2F w\n", $lineWidth));
        }
        $this->raw(sprintf("%.2F %.2F %.2F %.2F re ", $x, $y, $w, $h));
        if ($fill !== '' && $stroke !== '') {
            $this->raw("B\n");
        } elseif ($fill !== '') {
            $this->raw("f\n");
        } else {
            $this->raw("S\n");
        }
    }

    public function line(float $x1, float $y1, float $x2, float $y2, string $color = '#8EC5E8', float $w = 1): void {
        $this->raw(pdfColor($color) . " RG\n" . sprintf("%.2F w\n%.2F %.2F m\n%.2F %.2F l\nS\n", $w, $x1, $y1, $x2, $y2));
    }

    public function text(float $x, float $y, string $text, int $size = 10, string $font = 'F1', string $color = '#2C2C2C'): void {
        $this->raw(pdfColor($color) . " rg\nBT\n/" . $font . ' ' . $size . " Tf\n" . sprintf("%.2F %.2F Td\n", $x, $y) . '(' . pdfEscape($text) . ") Tj\nET\n");
    }

    private function header(): void {
        $this->rect(0, 742, self::W, 100, '#2C2C2C');
        $this->rect(0, 738, self::W, 4, '#8EC5E8');
        $this->text(46, 796, 'MUSEO STORICO SEVERI', 21, 'F2', '#F7FBFF');
        $this->text(46, 770, 'Riepilogo ordine e biglietti', 12, 'F1', '#8EC5E8');
        $this->text(470, 770, 'Pagina ' . $this->pageNo, 9, 'F1', '#F7FBFF');
    }

    private function footer(): void {
        $this->line(46, 56, 549, 56, '#EAF4FF', 1);
        $this->text(46, 38, 'Documento generato automaticamente - Museo Storico Severi', 8, 'F1', '#6B6B6B');
        $this->text(420, 38, date('d/m/Y H:i'), 8, 'F1', '#6B6B6B');
    }

    private function ensure(float $heightNeeded): void {
        if ($this->y - $heightNeeded < 82) {
            $this->addPage();
        }
    }

    public function title(string $text): void {
        $this->ensure(34);
        $this->text(46, $this->y, $text, 16, 'F2', '#2C2C2C');
        $this->line(46, $this->y - 10, 549, $this->y - 10, '#8EC5E8', 1.2);
        $this->y -= 34;
    }

    public function keyValue(string $label, string $value, int $maxChars = 54): void {
        $lines = pdfWrapText($value, $maxChars);
        $height = max(22, count($lines) * 14 + 8);
        $this->ensure($height + 4);

        $this->text(50, $this->y, strtoupper($label), 8, 'F2', '#6B6B6B');
        $yy = $this->y;
        foreach ($lines as $line) {
            $this->text(190, $yy, $line, 10, 'F1', '#2C2C2C');
            $yy -= 14;
        }
        $separatorY = $this->y - $height + 12;
        $this->line(46, $separatorY, 549, $separatorY, '#EFE8D8', 0.6);
        $this->y -= $height;
    }

    public function warning(string $title, string $message): void {
        $lines = pdfWrapText($message, 78);
        $height = 42 + (count($lines) - 1) * 12;
        $this->ensure($height + 10);
        $this->rect(46, $this->y - $height + 8, 503, $height, '#FFF8E1', '#8EC5E8', 1);
        $this->text(60, $this->y - 10, $title, 11, 'F2', '#5FA8D3');
        $yy = $this->y - 27;
        foreach ($lines as $line) {
            $this->text(60, $yy, $line, 9, 'F1', '#4A4A4A');
            $yy -= 12;
        }
        $this->y -= $height + 12;
    }

    public function ticketRow(int $numero, string $codice, string $stato): void {
        $this->ensure(34);
        $rowTop = $this->y + 9;
        $this->rect(46, $this->y - 13, 503, 28, '#FFFFFF', '#EAF4FF', 0.8);
        $this->text(58, $this->y, (string)$numero, 9, 'F2', '#6B6B6B');
        $this->text(95, $this->y, $codice, 10, 'F3', '#2C2C2C');
        if (strtolower($stato) === 'non pagato') {
            $statoColor = '#5FA8D3';
        } elseif (strtolower($stato) === 'rimborsato') {
            $statoColor = '#B91C1C';
        } else {
            $statoColor = '#166534';
        }
        $this->text(455, $this->y, strtoupper($stato), 8, 'F2', $statoColor);
        $this->y -= 34;
    }

    public function output(): string {
        if ($this->current !== '') {
            $this->footer();
            $this->pages[] = $this->current;
            $this->current = '';
        }

        $objects = [];
        $pageCount = count($this->pages);
        $font1Obj = 3;
        $font2Obj = 4;
        $font3Obj = 5;
        $firstPageObj = 6;
        $kids = [];

        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[3] = "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[4] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
        $objects[5] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold >>\nendobj\n";

        $objNo = $firstPageObj;
        foreach ($this->pages as $stream) {
            $pageObj = $objNo++;
            $contentObj = $objNo++;
            $kids[] = $pageObj . ' 0 R';

            $objects[$pageObj] = $pageObj . " 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 $font1Obj 0 R /F2 $font2Obj 0 R /F3 $font3Obj 0 R >> >> /Contents $contentObj 0 R >>\nendobj\n";
            $objects[$contentObj] = $contentObj . " 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n";
        }

        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count $pageCount >>\nendobj\n";
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $no => $obj) {
            $offsets[$no] = strlen($pdf);
            $pdf .= $obj;
        }

        $maxObj = max(array_keys($objects));
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxObj + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxObj; $i++) {
            if (isset($offsets[$i])) {
                $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
            } else {
                $pdf .= "0000000000 65535 f \n";
            }
        }
        $pdf .= "trailer\n<< /Size " . ($maxObj + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

        return $pdf;
    }
}

function creaPdfOrdine(array $ordine, array $codiciBiglietti): string {
    $codiceOrdine = (string)($ordine['codice_recupero'] ?? '');
    $nomeCliente = (string)($ordine['nome_cliente'] ?? 'Visitatore');
    $emailCliente = (string)($ordine['email_cliente'] ?? '-');
    $metodo = ucfirst((string)($ordine['metodo_pagamento'] ?? 'Non indicato'));
    $ordineRimborsato = strcasecmp((string)($ordine['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0;
    $stato = $ordineRimborsato ? 'Rimborsato' : (string)($ordine['stato_pagamento'] ?? 'Pagato');
    $statoBiglietto = $ordineRimborsato ? 'Rimborsato' : (strcasecmp($stato, 'Non pagato') === 0 ? 'Non pagato' : 'Valido');
    $totale = 'EUR ' . number_format((float)($ordine['importo_totale'] ?? 0), 2, ',', '.');
    $percorso = (string)($ordine['titolo_percorso'] ?? ($ordine['tipo'] ?? 'Museo Storico Severi'));
    $dataValidita = (string)($ordine['data_validita'] ?? '');
    $servizi = trim((string)($ordine['servizi_descrizione'] ?? ''));
    $quantita = (int)($ordine['quantita'] ?? count($codiciBiglietti));

    $pdf = new PdfOrdineBuilder();

    $pdf->title('Riepilogo ordine');
    $pdf->keyValue('Codice ordine', $codiceOrdine ?: '-');
    $pdf->keyValue('Acquirente', $nomeCliente);
    $pdf->keyValue('Email', $emailCliente);
    $pdf->keyValue('Percorso', $percorso);
    if ($dataValidita !== '') {
        $pdf->keyValue('Data visita', date('d/m/Y', strtotime($dataValidita)));
    }
    if ($servizi !== '') {
        $pdf->keyValue('Servizi opzionali', $servizi);
    }
    $pdf->keyValue('Metodo pagamento', $metodo);
    $pdf->keyValue('Stato pagamento', $stato);
    $pdf->keyValue('Totale', $totale);
    $pdf->keyValue('Numero biglietti', (string)max($quantita, count($codiciBiglietti)));

    if (!empty($ordine['prenotazione_docente'])) {
        $classe = (string)($ordine['classe_scuola'] ?? '-');
        $scuola = (string)($ordine['nome_scuola'] ?? '-');
        $studenti = (string)($ordine['quantita_studenti'] ?? '0');
        $docenti = (string)($ordine['numero_docenti'] ?? '0');
        $pdf->title('Dati gruppo / classe');
        $pdf->keyValue('Scuola', $scuola);
        $pdf->keyValue('Classe', $classe);
        $pdf->keyValue('Studenti', $studenti);
        $pdf->keyValue('Docenti accompagnatori', $docenti);
    }

    if ($ordineRimborsato) {
        $pdf->warning(
            'Ordine rimborsato',
            "Il rimborso di questo ordine e stato accettato. I biglietti restano nello storico, ma non sono piu utilizzabili ne validabili all'ingresso."
        );
    } elseif (strcasecmp($stato, 'Non pagato') === 0) {
        $pdf->warning(
            'Pagamento in cassa da completare',
            "L'ordine e stato registrato, ma i biglietti non sono ancora validi. Per renderli validi occorre saldare il pagamento alla cassa del museo."
        );
    }

    $pdf->title('Codici biglietto');
    if (empty($codiciBiglietti)) {
        $pdf->keyValue('Biglietti', 'Nessun codice biglietto disponibile.');
    } else {
        foreach ($codiciBiglietti as $i => $codice) {
            $pdf->ticketRow($i + 1, (string)$codice, $statoBiglietto);
        }
    }

    return $pdf->output();
}
