<?php
// Scarica PDF senza aprire la finestra di stampa.
// La pagina riusa la stessa vista grafica di biglietti.php e avvia automaticamente
// il download del PDF tramite il browser.
$autoScaricaPdf = true;
require __DIR__ . '/biglietti.php';
