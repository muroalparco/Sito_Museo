<?php

function qrGfMultiply(int $x, int $y): int {
    $z = 0;
    for ($i = 7; $i >= 0; $i--) {
        $z = (($z << 1) ^ (($z >> 7) * 0x11D)) & 0xFF;
        if ((($y >> $i) & 1) !== 0) {
            $z ^= $x;
        }
    }
    return $z;
}

function qrReedSolomonDivisor(int $degree): array {
    $result = array_fill(0, $degree, 0);
    $result[$degree - 1] = 1;
    $root = 1;

    for ($i = 0; $i < $degree; $i++) {
        for ($j = 0; $j < $degree; $j++) {
            $result[$j] = qrGfMultiply($result[$j], $root);
            if ($j + 1 < $degree) {
                $result[$j] ^= $result[$j + 1];
            }
        }
        $root = qrGfMultiply($root, 2);
    }

    return $result;
}

function qrReedSolomonRemainder(array $data, array $divisor): array {
    $result = array_fill(0, count($divisor), 0);

    foreach ($data as $byte) {
        $factor = $byte ^ $result[0];
        array_shift($result);
        $result[] = 0;

        foreach ($divisor as $i => $coef) {
            $result[$i] ^= qrGfMultiply($coef, $factor);
        }
    }

    return $result;
}

function qrAppendBits(array &$bits, int $value, int $length): void {
    for ($i = $length - 1; $i >= 0; $i--) {
        $bits[] = (($value >> $i) & 1) !== 0;
    }
}

function qrFormatBits(int $mask): int {
    $data = $mask; // Livello errore M = 00, quindi restano solo i tre bit maschera.
    $rem = $data;
    for ($i = 0; $i < 10; $i++) {
        $rem = ($rem << 1) ^ ((($rem >> 9) & 1) ? 0x537 : 0);
    }
    return (($data << 10) | $rem) ^ 0x5412;
}

function qrSetModule(array &$matrix, array &$reserved, int $x, int $y, bool $black, bool $function = true): void {
    $size = count($matrix);
    if ($x < 0 || $y < 0 || $x >= $size || $y >= $size) {
        return;
    }
    $matrix[$y][$x] = $black;
    if ($function) {
        $reserved[$y][$x] = true;
    }
}

function qrDrawFinder(array &$matrix, array &$reserved, int $left, int $top): void {
    for ($dy = -1; $dy <= 7; $dy++) {
        for ($dx = -1; $dx <= 7; $dx++) {
            $x = $left + $dx;
            $y = $top + $dy;
            $inFinder = $dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6;
            $black = $inFinder && (
                $dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 ||
                ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4)
            );
            qrSetModule($matrix, $reserved, $x, $y, $black, true);
        }
    }
}

function qrTicketMatrix(string $codice): array {
    $codice = strtoupper(trim($codice));
    if (!preg_match('/^TKT-[A-Z0-9]{8,20}$/', $codice)) {
        throw new InvalidArgumentException('Codice biglietto non valido.');
    }

    $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';
    $bits = [];
    qrAppendBits($bits, 0x2, 4);
    qrAppendBits($bits, strlen($codice), 9);

    for ($i = 0; $i + 1 < strlen($codice); $i += 2) {
        $v = strpos($alphabet, $codice[$i]) * 45 + strpos($alphabet, $codice[$i + 1]);
        qrAppendBits($bits, $v, 11);
    }
    if (strlen($codice) % 2 === 1) {
        qrAppendBits($bits, strpos($alphabet, $codice[strlen($codice) - 1]), 6);
    }

    $dataCapacityBits = 16 * 8; // QR versione 1, correzione errore M.
    qrAppendBits($bits, 0, min(4, $dataCapacityBits - count($bits)));
    while (count($bits) % 8 !== 0) {
        $bits[] = false;
    }

    $data = [];
    for ($i = 0; $i < count($bits); $i += 8) {
        $byte = 0;
        for ($j = 0; $j < 8; $j++) {
            $byte = ($byte << 1) | ($bits[$i + $j] ? 1 : 0);
        }
        $data[] = $byte;
    }

    for ($pad = 0; count($data) < 16; $pad++) {
        $data[] = ($pad % 2 === 0) ? 0xEC : 0x11;
    }

    $codewords = array_merge($data, qrReedSolomonRemainder($data, qrReedSolomonDivisor(10)));
    $codeBits = [];
    foreach ($codewords as $byte) {
        qrAppendBits($codeBits, $byte, 8);
    }

    $size = 21;
    $matrix = array_fill(0, $size, array_fill(0, $size, false));
    $reserved = array_fill(0, $size, array_fill(0, $size, false));

    qrDrawFinder($matrix, $reserved, 0, 0);
    qrDrawFinder($matrix, $reserved, $size - 7, 0);
    qrDrawFinder($matrix, $reserved, 0, $size - 7);

    for ($i = 8; $i < $size - 8; $i++) {
        qrSetModule($matrix, $reserved, $i, 6, $i % 2 === 0, true);
        qrSetModule($matrix, $reserved, 6, $i, $i % 2 === 0, true);
    }

    qrSetModule($matrix, $reserved, 8, $size - 8, true, true);
    for ($i = 0; $i < 9; $i++) {
        if ($i !== 6) {
            qrSetModule($matrix, $reserved, 8, $i, false, true);
            qrSetModule($matrix, $reserved, $i, 8, false, true);
        }
    }
    for ($i = 0; $i < 8; $i++) {
        qrSetModule($matrix, $reserved, $size - 1 - $i, 8, false, true);
        qrSetModule($matrix, $reserved, 8, $size - 1 - $i, false, true);
    }

    $bitIndex = 0;
    $upward = true;
    for ($right = $size - 1; $right >= 1; $right -= 2) {
        if ($right === 6) {
            $right--;
        }
        for ($vert = 0; $vert < $size; $vert++) {
            $y = $upward ? ($size - 1 - $vert) : $vert;
            for ($j = 0; $j < 2; $j++) {
                $x = $right - $j;
                if (!$reserved[$y][$x]) {
                    $black = $codeBits[$bitIndex++] ?? false;
                    if ((($x + $y) % 2) === 0) {
                        $black = !$black;
                    }
                    $matrix[$y][$x] = $black;
                }
            }
        }
        $upward = !$upward;
    }

    $format = qrFormatBits(0);
    for ($i = 0; $i <= 5; $i++) {
        qrSetModule($matrix, $reserved, 8, $i, (($format >> $i) & 1) !== 0, true);
    }
    qrSetModule($matrix, $reserved, 8, 7, (($format >> 6) & 1) !== 0, true);
    qrSetModule($matrix, $reserved, 8, 8, (($format >> 7) & 1) !== 0, true);
    qrSetModule($matrix, $reserved, 7, 8, (($format >> 8) & 1) !== 0, true);
    for ($i = 9; $i < 15; $i++) {
        qrSetModule($matrix, $reserved, 14 - $i, 8, (($format >> $i) & 1) !== 0, true);
    }
    for ($i = 0; $i < 8; $i++) {
        qrSetModule($matrix, $reserved, $size - 1 - $i, 8, (($format >> $i) & 1) !== 0, true);
    }
    for ($i = 8; $i < 15; $i++) {
        qrSetModule($matrix, $reserved, 8, $size - 15 + $i, (($format >> $i) & 1) !== 0, true);
    }
    qrSetModule($matrix, $reserved, 8, $size - 8, true, true);

    return $matrix;
}

function qrTicketSvg(string $codice, int $moduleSize = 8, int $quietZone = 4): string {
    $matrix = qrTicketMatrix($codice);
    $size = count($matrix);
    $viewSize = $size + $quietZone * 2;
    $pixelSize = $viewSize * max(2, $moduleSize);
    $paths = [];

    foreach ($matrix as $y => $row) {
        foreach ($row as $x => $black) {
            if ($black) {
                $paths[] = 'M' . ($x + $quietZone) . ' ' . ($y + $quietZone) . 'h1v1h-1z';
            }
        }
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $pixelSize . '" height="' . $pixelSize . '" viewBox="0 0 ' . $viewSize . ' ' . $viewSize . '" shape-rendering="crispEdges" role="img" aria-label="QR code biglietto ' . htmlspecialchars($codice, ENT_QUOTES, 'UTF-8') . '">'
        . '<rect width="100%" height="100%" fill="#fff"/>'
        . '<path fill="#000" d="' . implode('', $paths) . '"/>'
        . '</svg>';
}
