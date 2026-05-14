<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireAdmin();

$pageTitle = 'Vista amministratore';
$pdo = getDB();
$successMsg = '';
$errorMsg = '';
$stati = ['Bozza', 'Pubblicata', 'Conclusa', 'Annullata'];
$tipiBiglietto = ['base', 'esposizione'];
$ruoliDisponibili = ['visitatore', 'operatore', 'cassiere', 'amministratore', 'tester'];
$ruoloLabel = [
    'visitatore' => 'Visitatore',
    'operatore' => 'Operatore',
    'cassiere' => 'Cassiere',
    'amministratore' => 'Amministratore',
    'tester' => 'Tester'
];
$emojiEsposizioni = [
    '🏛️' => 'Museo storico',
    '🏺' => 'Civiltà antiche',
    '⚔️' => 'Battaglie e imperi',
    '🏰' => 'Medioevo',
    '🎨' => 'Arte',
    '🖼️' => 'Galleria',
    '🗿' => 'Archeologia',
    '📜' => 'Documenti',
    '🪙' => 'Reperti',
    '🌍' => 'Culture'
];
$domandeSicurezza = [
    'primo_animale' => 'Nome del primo animale domestico',
    'citta_nascita' => 'Città che vorresti visitare',
    'scuola_elementare' => 'Nome della scuola elementare',
    'colore_preferito' => 'Colore preferito'
];

function normalizzaOraFascia(string $ora): string {
    $ora = trim($ora);
    if (preg_match('/^\d{2}:\d{2}$/', $ora)) {
        return $ora . ':00';
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $ora)) {
        return $ora;
    }
    throw new RuntimeException('Inserisci un orario valido per la fascia.');
}

function caricaEsposizione(PDO $pdo, int $idEsposizione): array {
    $stmt = $pdo->prepare('SELECT id_esposizione, titolo, data_inizio, data_fine FROM Esposizioni WHERE id_esposizione = ? LIMIT 1');
    $stmt->execute([$idEsposizione]);
    $esposizione = $stmt->fetch();
    if (!$esposizione) {
        throw new RuntimeException('Esposizione non trovata.');
    }
    return $esposizione;
}

function validaDatiFascia(array $esposizione, string $data, string $ora, int $capienza): string {
    if ($data === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        throw new RuntimeException('Inserisci un giorno valido per la fascia oraria.');
    }
    if ($data < $esposizione['data_inizio'] || $data > $esposizione['data_fine']) {
        throw new RuntimeException("Il giorno della fascia deve essere compreso nel periodo dell'esposizione.");
    }
    if ($capienza <= 0) {
        throw new RuntimeException('La capienza massima deve essere maggiore di zero.');
    }
    return normalizzaOraFascia($ora);
}

function contaBigliettiFascia(PDO $pdo, int $idFascia): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Biglietti WHERE id_fascia = ? AND stato <> 'Annullato'");
    $stmt->execute([$idFascia]);
    return (int)$stmt->fetchColumn();
}

function esposizioniSupportaEmoji(PDO $pdo): bool {
    static $supporta = null;
    if ($supporta !== null) {
        return $supporta;
    }
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM Esposizioni LIKE 'emoji'");
        $supporta = (bool)$stmt->fetch();
    } catch (Throwable $e) {
        $supporta = false;
    }
    return $supporta;
}

function normalizzaEmojiEsposizione(string $emoji, array $emojiEsposizioni): string {
    return array_key_exists($emoji, $emojiEsposizioni) ? $emoji : '🏛️';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Token di sicurezza non valido.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'create_esposizione' || $action === 'update_esposizione') {
                $titolo = trim($_POST['titolo'] ?? '');
                $descrizione = trim($_POST['descrizione'] ?? '');
                $dataInizio = $_POST['data_inizio'] ?? '';
                $dataFine = $_POST['data_fine'] ?? '';
                $stato = $_POST['stato'] ?? 'Bozza';
                $emoji = normalizzaEmojiEsposizione((string)($_POST['emoji'] ?? '🏛️'), $emojiEsposizioni);
                $usaEmoji = esposizioniSupportaEmoji($pdo);

                if ($titolo === '' || !$dataInizio || !$dataFine || !in_array($stato, $stati, true)) {
                    throw new RuntimeException('Compila correttamente tutti i campi dell\'esposizione.');
                }
                if ($dataFine < $dataInizio) {
                    throw new RuntimeException('La data di fine non può precedere la data di inizio.');
                }

                if ($action === 'create_esposizione') {
                    if ($usaEmoji) {
                        $stmt = $pdo->prepare('INSERT INTO Esposizioni (titolo, descrizione, emoji, data_inizio, data_fine, stato) VALUES (?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$titolo, $descrizione, $emoji, $dataInizio, $dataFine, $stato]);
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO Esposizioni (titolo, descrizione, data_inizio, data_fine, stato) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([$titolo, $descrizione, $dataInizio, $dataFine, $stato]);
                    }
                    $successMsg = 'Esposizione creata correttamente.';
                } else {
                    $id = (int)($_POST['id_esposizione'] ?? 0);
                    if ($id <= 0) throw new RuntimeException('ID esposizione non valido.');
                    if ($usaEmoji) {
                        $stmt = $pdo->prepare('UPDATE Esposizioni SET titolo = ?, descrizione = ?, emoji = ?, data_inizio = ?, data_fine = ?, stato = ? WHERE id_esposizione = ?');
                        $stmt->execute([$titolo, $descrizione, $emoji, $dataInizio, $dataFine, $stato, $id]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE Esposizioni SET titolo = ?, descrizione = ?, data_inizio = ?, data_fine = ?, stato = ? WHERE id_esposizione = ?');
                        $stmt->execute([$titolo, $descrizione, $dataInizio, $dataFine, $stato, $id]);
                    }
                    $successMsg = 'Esposizione aggiornata correttamente.';
                }
            } elseif ($action === 'create_fascia') {
                $idEsposizione = (int)($_POST['id_esposizione'] ?? 0);
                $data = $_POST['data'] ?? '';
                $ora = $_POST['ora_ingresso'] ?? '';
                $capienza = (int)($_POST['capienza_massima'] ?? 0);

                if ($idEsposizione <= 0) {
                    throw new RuntimeException("Seleziona un'esposizione valida.");
                }

                $esposizione = caricaEsposizione($pdo, $idEsposizione);
                $oraIngresso = validaDatiFascia($esposizione, $data, $ora, $capienza);

                try {
                    $stmt = $pdo->prepare('INSERT INTO Fasce_Orarie (id_esposizione, data, ora_ingresso, capienza_massima) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$idEsposizione, $data, $oraIngresso, $capienza]);
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('Esiste già una fascia per questa esposizione nello stesso giorno e orario.');
                    }
                    throw $e;
                }
                $successMsg = 'Fascia oraria creata correttamente.';
            } elseif ($action === 'update_fascia') {
                $idFascia = (int)($_POST['id_fascia'] ?? 0);
                $idEsposizione = (int)($_POST['id_esposizione'] ?? 0);
                $data = $_POST['data'] ?? '';
                $ora = $_POST['ora_ingresso'] ?? '';
                $capienza = (int)($_POST['capienza_massima'] ?? 0);

                if ($idFascia <= 0 || $idEsposizione <= 0) {
                    throw new RuntimeException('Fascia oraria non valida.');
                }

                $esposizione = caricaEsposizione($pdo, $idEsposizione);
                $oraIngresso = validaDatiFascia($esposizione, $data, $ora, $capienza);
                $bigliettiPrenotati = contaBigliettiFascia($pdo, $idFascia);

                if ($capienza < $bigliettiPrenotati) {
                    throw new RuntimeException('La capienza non può essere inferiore ai biglietti già prenotati per questa fascia.');
                }

                try {
                    $stmt = $pdo->prepare('UPDATE Fasce_Orarie SET data = ?, ora_ingresso = ?, capienza_massima = ? WHERE id_fascia = ? AND id_esposizione = ?');
                    $stmt->execute([$data, $oraIngresso, $capienza, $idFascia, $idEsposizione]);
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('Esiste già una fascia per questa esposizione nello stesso giorno e orario.');
                    }
                    throw $e;
                }
                $successMsg = 'Fascia oraria aggiornata correttamente.';
            } elseif ($action === 'delete_fascia') {
                $idFascia = (int)($_POST['id_fascia'] ?? 0);
                if ($idFascia <= 0) {
                    throw new RuntimeException('Fascia oraria non valida.');
                }

                $bigliettiPrenotati = contaBigliettiFascia($pdo, $idFascia);
                if ($bigliettiPrenotati > 0) {
                    throw new RuntimeException('Non puoi eliminare una fascia con biglietti già prenotati. Puoi modificarne la capienza oppure annullare i biglietti.');
                }

                $stmt = $pdo->prepare('DELETE FROM Fasce_Orarie WHERE id_fascia = ?');
                $stmt->execute([$idFascia]);
                $successMsg = 'Fascia oraria eliminata correttamente.';
            } elseif ($action === 'create_categoria' || $action === 'update_categoria') {
                $nome = trim($_POST['nome'] ?? '');
                $percentualeSconto = (float)str_replace(',', '.', $_POST['percentuale_sconto'] ?? '0');
                $documentoRichiesto = trim($_POST['documento_richiesto'] ?? '');
                $documentoRichiestoDb = $documentoRichiesto !== '' ? $documentoRichiesto : null;

                if ($nome === '' || $percentualeSconto < 0 || $percentualeSconto > 100) {
                    throw new RuntimeException('Compila correttamente la categoria: nome obbligatorio e sconto tra 0 e 100.');
                }

                try {
                    if ($action === 'create_categoria') {
                        $stmt = $pdo->prepare('INSERT INTO Categorie_Riduzione (nome, percentuale_sconto, documento_richiesto) VALUES (?, ?, ?)');
                        $stmt->execute([$nome, $percentualeSconto, $documentoRichiestoDb]);
                        $successMsg = 'Categoria creata correttamente. Ora puoi selezionarla nelle tariffe.';
                    } else {
                        $idCategoria = (int)($_POST['id_categoria'] ?? 0);
                        if ($idCategoria <= 0) {
                            throw new RuntimeException('Categoria non valida.');
                        }
                        $stmt = $pdo->prepare('UPDATE Categorie_Riduzione SET nome = ?, percentuale_sconto = ?, documento_richiesto = ? WHERE id_categoria = ?');
                        $stmt->execute([$nome, $percentualeSconto, $documentoRichiestoDb, $idCategoria]);
                        $successMsg = 'Categoria aggiornata correttamente.';
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('Esiste già una categoria con questo nome.');
                    }
                    throw $e;
                }
            } elseif ($action === 'delete_categoria') {
                $idCategoria = (int)($_POST['id_categoria'] ?? 0);
                if ($idCategoria <= 0) {
                    throw new RuntimeException('Categoria non valida.');
                }

                $stmt = $pdo->prepare('SELECT COUNT(*) FROM Tariffe WHERE id_categoria = ?');
                $stmt->execute([$idCategoria]);
                $tariffeCollegate = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare('SELECT COUNT(*) FROM Biglietti WHERE id_categoria = ?');
                $stmt->execute([$idCategoria]);
                $bigliettiCollegati = (int)$stmt->fetchColumn();

                if ($tariffeCollegate > 0 || $bigliettiCollegati > 0) {
                    throw new RuntimeException('Non puoi eliminare una categoria già collegata a tariffe o biglietti. Puoi modificarla oppure rimuovere prima le tariffe associate.');
                }

                $stmt = $pdo->prepare('DELETE FROM Categorie_Riduzione WHERE id_categoria = ?');
                $stmt->execute([$idCategoria]);
                $successMsg = 'Categoria eliminata correttamente.';
            } elseif ($action === 'update_user_role') {
                $idUtente = (int)($_POST['id_utente'] ?? 0);
                $nuovoRuolo = $_POST['ruolo'] ?? '';
                $utenteCorrenteId = (int)($_SESSION['utente_id'] ?? 0);

                if ($idUtente <= 0 || !in_array($nuovoRuolo, $ruoliDisponibili, true)) {
                    throw new RuntimeException('Dati utente non validi.');
                }

                if ($idUtente === $utenteCorrenteId) {
                    throw new RuntimeException('Non puoi modificare il tuo ruolo da qui.');
                }

                $stmt = $pdo->prepare('UPDATE Utenti SET ruolo = ? WHERE id_utente = ?');
                $stmt->execute([$nuovoRuolo, $idUtente]);

                $successMsg = 'Ruolo utente aggiornato correttamente.';
            } elseif ($action === 'force_user_password') {
                $idUtente = (int)($_POST['id_utente'] ?? 0);
                $nuovaPassword = trim($_POST['nuova_password'] ?? '');

                if ($idUtente <= 0) {
                    throw new RuntimeException('Utente non valido.');
                }
                if (strlen($nuovaPassword) < 8) {
                    throw new RuntimeException('La nuova password deve avere almeno 8 caratteri.');
                }

                $hash = password_hash($nuovaPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare('UPDATE Utenti SET password_hash = ? WHERE id_utente = ?');
                $stmt->execute([$hash, $idUtente]);
                $successMsg = 'Password utente aggiornata correttamente.';
            } elseif ($action === 'force_user_security') {
                $idUtente = (int)($_POST['id_utente'] ?? 0);
                $domanda = $_POST['domanda_sicurezza'] ?? '';
                $risposta = trim($_POST['risposta_sicurezza'] ?? '');

                if ($idUtente <= 0 || !array_key_exists($domanda, $domandeSicurezza)) {
                    throw new RuntimeException('Domanda di sicurezza non valida.');
                }
                if ($risposta === '') {
                    throw new RuntimeException('Inserisci una nuova risposta di sicurezza.');
                }

                $hashRisposta = password_hash(normalizzaRispostaSicurezza($risposta), PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare('UPDATE Utenti SET domanda_sicurezza = ?, risposta_sicurezza_hash = ? WHERE id_utente = ?');
                $stmt->execute([$domanda, $hashRisposta, $idUtente]);
                $successMsg = 'Domanda e risposta di sicurezza aggiornate correttamente.';
            } elseif ($action === 'delete_user') {
                $idUtente = (int)($_POST['id_utente'] ?? 0);

                if ($idUtente <= 0) {
                    throw new RuntimeException('Utente non valido.');
                }
                if ($idUtente === (int)$_SESSION['utente_id']) {
                    throw new RuntimeException('Non puoi eliminare il tuo account amministratore da questa pagina. Usa la pagina account personale.');
                }

                $stmt = $pdo->prepare('DELETE FROM Utenti WHERE id_utente = ?');
                $stmt->execute([$idUtente]);
                $successMsg = 'Account eliminato correttamente.';
            } elseif ($action === 'create_tariffa' || $action === 'update_tariffa') {
                $tipoBiglietto = $_POST['tipo_biglietto'] ?? '';
                $idCategoria = (int)($_POST['id_categoria'] ?? 0);
                $prezzo = (float)str_replace(',', '.', $_POST['prezzo'] ?? '0');

                if (!in_array($tipoBiglietto, $tipiBiglietto, true) || $idCategoria <= 0 || $prezzo < 0) {
                    throw new RuntimeException('Compila correttamente tutti i campi della tariffa.');
                }

                try {
                    if ($action === 'create_tariffa') {
                        $stmt = $pdo->prepare('INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo) VALUES (?, ?, ?)');
                        $stmt->execute([$tipoBiglietto, $idCategoria, $prezzo]);
                        $successMsg = 'Tariffa creata correttamente.';
                    } else {
                        $id = (int)($_POST['id_tariffa'] ?? 0);
                        if ($id <= 0) throw new RuntimeException('ID tariffa non valido.');
                        $stmt = $pdo->prepare('UPDATE Tariffe SET tipo_biglietto = ?, id_categoria = ?, prezzo = ? WHERE id_tariffa = ?');
                        $stmt->execute([$tipoBiglietto, $idCategoria, $prezzo, $id]);
                        $successMsg = 'Tariffa aggiornata correttamente.';
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('Esiste già una tariffa per questa combinazione di tipo biglietto e categoria.');
                    }
                    throw $e;
                }
            } elseif ($action === 'create_servizio' || $action === 'update_servizio') {
                $nome = trim($_POST['nome'] ?? '');
                $descrizione = trim($_POST['descrizione'] ?? '');
                $prezzo = (float)str_replace(',', '.', $_POST['prezzo'] ?? '0');

                if ($nome === '' || $prezzo < 0) {
                    throw new RuntimeException('Compila correttamente tutti i campi del servizio.');
                }

                if ($action === 'create_servizio') {
                    $stmt = $pdo->prepare('INSERT INTO Servizi_Opzionali (nome, descrizione, prezzo) VALUES (?, ?, ?)');
                    $stmt->execute([$nome, $descrizione, $prezzo]);
                    $successMsg = 'Servizio opzionale creato correttamente.';
                } else {
                    $id = (int)($_POST['id_servizio'] ?? 0);
                    if ($id <= 0) throw new RuntimeException('ID servizio non valido.');
                    $stmt = $pdo->prepare('UPDATE Servizi_Opzionali SET nome = ?, descrizione = ?, prezzo = ? WHERE id_servizio = ?');
                    $stmt->execute([$nome, $descrizione, $prezzo, $id]);
                    $successMsg = 'Servizio opzionale aggiornato correttamente.';
                }
            } else {
                throw new RuntimeException('Azione non riconosciuta.');
            }
        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

try {
    $esposizioni = $pdo->query("SELECT * FROM Esposizioni ORDER BY FIELD(stato,'Pubblicata','Bozza','Conclusa','Annullata'), data_inizio DESC")->fetchAll();
    $categorie = $pdo->query("
        SELECT
            cr.*,
            COALESCE(tariffe_collegate.numero_tariffe, 0) AS numero_tariffe,
            COALESCE(biglietti_collegati.numero_biglietti, 0) AS numero_biglietti
        FROM Categorie_Riduzione cr
        LEFT JOIN (
            SELECT id_categoria, COUNT(*) AS numero_tariffe
            FROM Tariffe
            GROUP BY id_categoria
        ) tariffe_collegate ON tariffe_collegate.id_categoria = cr.id_categoria
        LEFT JOIN (
            SELECT id_categoria, COUNT(*) AS numero_biglietti
            FROM Biglietti
            WHERE id_categoria IS NOT NULL
            GROUP BY id_categoria
        ) biglietti_collegati ON biglietti_collegati.id_categoria = cr.id_categoria
        ORDER BY cr.nome ASC
    ")->fetchAll();
    $tariffe = $pdo->query("
        SELECT t.*, cr.nome AS categoria
        FROM Tariffe t
        JOIN Categorie_Riduzione cr ON cr.id_categoria = t.id_categoria
        ORDER BY FIELD(t.tipo_biglietto,'base','esposizione'), cr.nome
    ")->fetchAll();
    $servizi = $pdo->query("SELECT * FROM Servizi_Opzionali ORDER BY nome ASC")->fetchAll();
    $utenti = $pdo->query("SELECT id_utente, nome, cognome, email, ruolo, email_verificata, domanda_sicurezza, data_registrazione FROM Utenti ORDER BY data_registrazione DESC, cognome ASC")->fetchAll();
    $fasce = $pdo->query("
        SELECT
            f.*,
            COALESCE(prenotazioni.biglietti_prenotati, 0) AS biglietti_prenotati,
            (f.capienza_massima - COALESCE(prenotazioni.biglietti_prenotati, 0)) AS posti_disponibili
        FROM Fasce_Orarie f
        LEFT JOIN (
            SELECT id_fascia, COUNT(*) AS biglietti_prenotati
            FROM Biglietti
            WHERE stato <> 'Annullato'
            GROUP BY id_fascia
        ) prenotazioni ON prenotazioni.id_fascia = f.id_fascia
        ORDER BY f.data ASC, f.ora_ingresso ASC
    ")->fetchAll();

    $fascePerEsposizione = [];
    foreach ($fasce as $fascia) {
        $fascePerEsposizione[(int)$fascia['id_esposizione']][] = $fascia;
    }
} catch (Exception $e) {
    $esposizioni = $categorie = $tariffe = $servizi = $utenti = $fasce = $fascePerEsposizione = [];
    $errorMsg = $errorMsg ?: 'Errore nel caricamento dei dati amministrativi.';
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Vista amministratore</span>
  </div>
</div>

<section class="bg-antracite py-14">
  <div class="max-w-7xl mx-auto px-4 text-center fade-up">
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Area riservata</p>
    <h1 class="font-display text-avorio text-4xl font-bold">Vista amministratore</h1>
    <p class="text-gray-400 mt-3">Gestisci esposizioni, tariffe e servizi opzionali.</p>
    <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
  </div>
</section>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-12">
  <?php if ($successMsg): ?>
    <div class="alert-success floating-alert p-4 rounded text-sm font-body" role="status" data-auto-dismiss="true">✅ <?= clean($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert-error floating-alert p-4 rounded text-sm font-body" role="alert">⚠️ <?= clean($errorMsg) ?></div>
  <?php endif; ?>

  <?php
    $adminMenuItems = [
      ['href' => '#admin-esposizioni', 'label' => 'Esposizioni'],
      ['href' => '#admin-categorie', 'label' => 'Categorie riduzioni'],
      ['href' => '#admin-tariffe', 'label' => 'Tariffe'],
      ['href' => '#admin-servizi', 'label' => 'Servizi'],
      ['href' => '#admin-utenti', 'label' => 'Utenti'],
    ];
  ?>

  <nav class="admin-quick-nav bg-white rounded-2xl shadow border border-avorio-dark p-5 hidden md:block" aria-label="Menu amministrazione">
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-3">Menu amministrazione</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      <?php foreach ($adminMenuItems as $item): ?>
        <a href="<?= clean($item['href']) ?>" class="btn-outline text-center px-5 py-3 rounded text-sm uppercase tracking-wide"><?= clean($item['label']) ?></a>
      <?php endforeach; ?>
    </div>
  </nav>


  <!-- ESPOSIZIONI -->
  <section id="admin-esposizioni" class="scroll-mt-32 bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
    <div class="bg-antracite px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Gestione</p>
        <h2 class="font-display text-2xl text-avorio font-bold">Esposizioni</h2>
      </div>
      <span class="text-gray-400 text-sm">Le bozze sono visibili solo agli amministratori.</span>
    </div>

    <div class="p-6 border-b border-avorio-dark bg-avorio">
      <h3 class="font-display text-xl font-bold text-antracite mb-4">Crea nuova esposizione</h3>
      <form method="POST" class="grid md:grid-cols-6 gap-4 admin-expo-create-form">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="create_esposizione">
        <input type="text" name="titolo" placeholder="Titolo" required class="admin-title-input px-4 py-3 border border-gray-200 rounded-lg text-sm">
        <div class="admin-emoji-field">
          <label for="nuova_esposizione_emoji" class="block text-xs font-bold text-gray-500 uppercase mb-1">Emoji</label>
          <select id="nuova_esposizione_emoji" name="emoji" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm bg-white admin-emoji-select" aria-label="Emoji esposizione">
            <?php foreach ($emojiEsposizioni as $emoji => $label): ?>
              <option value="<?= clean($emoji) ?>"><?= clean($emoji . ' ' . $label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="admin-date-field">
          <label for="nuova_esposizione_data_inizio" class="block text-xs font-bold text-gray-500 uppercase mb-1">Data inizio</label>
          <input type="date" id="nuova_esposizione_data_inizio" name="data_inizio" required class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm">
        </div>
        <div class="admin-date-field">
          <label for="nuova_esposizione_data_fine" class="block text-xs font-bold text-gray-500 uppercase mb-1">Data fine</label>
          <input type="date" id="nuova_esposizione_data_fine" name="data_fine" required class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm">
        </div>
        <div class="admin-state-field">
          <label for="nuova_esposizione_stato" class="block text-xs font-bold text-gray-500 uppercase mb-1">Stato</label>
          <select id="nuova_esposizione_stato" name="stato" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm" aria-label="Stato esposizione">
            <?php foreach ($stati as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
          </select>
        </div>
        <textarea name="descrizione" placeholder="Descrizione" class="md:col-span-5 px-4 py-3 border border-gray-200 rounded-lg text-sm admin-description-field"></textarea>
        <div class="admin-submit-field"><button type="submit" class="btn-oro px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Crea</button></div>
      </form>
    </div>

    <?php if (!empty($esposizioni)): ?>
      <div class="p-6 border-b border-avorio-dark bg-white">
        <label for="cerca-nome-esposizione" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Cerca esposizione per nome</label>
        <div class="flex flex-col sm:flex-row gap-3">
          <input type="search" id="cerca-nome-esposizione" placeholder="Scrivi il nome dell’esposizione..." autocomplete="off" class="flex-1 px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-oro/40">
          <button type="button" id="reset-cerca-nome-esposizione" class="btn-outline px-5 py-3 rounded text-xs uppercase tracking-wide">Mostra tutte</button>
        </div>
        <p id="risultati-cerca-nome-esposizione" class="text-xs text-gray-500 mt-2">Puoi filtrare le esposizioni senza scorrere tutta la lista.</p>
        <p id="nessuna-esposizione-trovata" class="hidden text-sm text-red-600 font-bold mt-3">Nessuna esposizione trovata con questo nome.</p>
      </div>
    <?php endif; ?>

    <span id="admin-fasce" class="block scroll-mt-32"></span>
    <div class="divide-y divide-avorio-dark">
      <?php foreach ($esposizioni as $esp): ?>
        <?php $fasceEsp = $fascePerEsposizione[(int)$esp['id_esposizione']] ?? []; ?>
        <article class="admin-exposition-card m-4 md:m-6 rounded-2xl border-2 border-oro/30 bg-white shadow-lg overflow-hidden" id="admin-fasce-<?= (int)$esp['id_esposizione'] ?>" data-title="<?= clean(strtolower($esp['titolo'])) ?>">
          <div class="bg-avorio px-6 py-5 border-b border-oro/30 text-center md:text-left">
            <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Stai modificando questa esposizione</p>
            <h3 class="font-display text-2xl font-bold text-antracite"><?= clean($esp['titolo']) ?></h3>
            <p class="text-sm text-gray-500 mt-1">Le fasce orarie qui sotto appartengono solo a questa esposizione.</p>
          </div>
          <div class="p-6 space-y-6">
          <form method="POST" class="grid md:grid-cols-7 gap-4 items-start admin-expo-edit-form">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update_esposizione">
            <input type="hidden" name="id_esposizione" value="<?= (int)$esp['id_esposizione'] ?>">
            <div class="admin-title-field">
              <label class="text-xs font-bold text-gray-500 uppercase">Titolo</label>
              <input type="text" name="titolo" value="<?= clean($esp['titolo']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
            </div>
            <div class="admin-emoji-field">
              <label class="text-xs font-bold text-gray-500 uppercase">Emoji</label>
              <select name="emoji" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm bg-white admin-emoji-select" aria-label="Emoji esposizione">
                <?php foreach ($emojiEsposizioni as $emoji => $label): ?>
                  <option value="<?= clean($emoji) ?>" <?= (($esp['emoji'] ?? '🏛️') === $emoji) ? 'selected' : '' ?>><?= clean($emoji . ' ' . $label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="admin-date-field">
              <label class="text-xs font-bold text-gray-500 uppercase">Inizio</label>
              <input type="date" name="data_inizio" value="<?= clean($esp['data_inizio']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
            </div>
            <div class="admin-date-field">
              <label class="text-xs font-bold text-gray-500 uppercase">Fine</label>
              <input type="date" name="data_fine" value="<?= clean($esp['data_fine']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
            </div>
            <div class="admin-state-field">
              <label class="text-xs font-bold text-gray-500 uppercase">Stato</label>
              <select name="stato" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                <?php foreach ($stati as $s): ?>
                  <option value="<?= $s ?>" <?= $esp['stato'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="flex items-end justify-end h-full admin-submit-field">
              <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Salva</button>
            </div>
            <div class="admin-description-wrap">
              <label class="text-xs font-bold text-gray-500 uppercase">Descrizione</label>
              <textarea name="descrizione" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm min-h-20"><?= clean($esp['descrizione'] ?? '') ?></textarea>
            </div>
          </form>

          <div class="rounded-2xl border border-avorio-dark bg-avorio p-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2 mb-4">
              <div>
                <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Fasce orarie</p>
                <h4 class="font-display text-lg font-bold text-antracite">Giorni, orari e capienza per questa esposizione</h4>
              </div>
              <span class="text-xs text-gray-500">Periodo esposizione: <?= date('d/m/Y', strtotime($esp['data_inizio'])) ?> → <?= date('d/m/Y', strtotime($esp['data_fine'])) ?></span>
            </div>

            <form method="POST" class="grid md:grid-cols-6 gap-3 items-end mb-5">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="create_fascia">
              <input type="hidden" name="id_esposizione" value="<?= (int)$esp['id_esposizione'] ?>">
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Data inizio</label>
                <input type="date" value="<?= clean($esp['data_inizio']) ?>" readonly class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm bg-gray-50" aria-label="Data inizio esposizione">
              </div>
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Data fine</label>
                <input type="date" value="<?= clean($esp['data_fine']) ?>" readonly class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm bg-gray-50" aria-label="Data fine esposizione">
              </div>
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Giorno fascia</label>
                <input type="date" name="data" min="<?= clean($esp['data_inizio']) ?>" max="<?= clean($esp['data_fine']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm bg-white">
              </div>
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Ora ingresso</label>
                <input type="time" name="ora_ingresso" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm bg-white">
              </div>
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Capienza massima</label>
                <input type="number" name="capienza_massima" min="1" value="30" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm bg-white">
              </div>
              <div class="md:col-span-1 admin-submit-field">
                <button type="submit" class="btn-oro px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Aggiungi fascia</button>
              </div>
            </form>

            <?php if (empty($fasceEsp)): ?>
              <p class="text-sm text-gray-500 bg-white border border-avorio-dark rounded-xl p-4">Nessuna fascia oraria inserita per questa esposizione.</p>
            <?php else: ?>
              <div class="space-y-3">
                <?php foreach ($fasceEsp as $fascia): ?>
                  <?php
                    $prenotati = (int)$fascia['biglietti_prenotati'];
                    $postiDisponibili = max(0, (int)$fascia['posti_disponibili']);
                  ?>
                  <div class="bg-white border border-avorio-dark rounded-xl p-4">
                    <form method="POST" class="grid md:grid-cols-6 gap-3 items-end admin-inline-form">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="action" value="update_fascia">
                      <input type="hidden" name="id_fascia" value="<?= (int)$fascia['id_fascia'] ?>">
                      <input type="hidden" name="id_esposizione" value="<?= (int)$esp['id_esposizione'] ?>">
                      <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Giorno</label>
                        <input type="date" name="data" min="<?= clean($esp['data_inizio']) ?>" max="<?= clean($esp['data_fine']) ?>" value="<?= clean($fascia['data']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                      </div>
                      <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Ora</label>
                        <input type="time" name="ora_ingresso" value="<?= clean(substr($fascia['ora_ingresso'], 0, 5)) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                      </div>
                      <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Capienza</label>
                        <input type="number" name="capienza_massima" min="<?= max(1, $prenotati) ?>" value="<?= (int)$fascia['capienza_massima'] ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                      </div>
                      <div class="text-xs text-gray-500 leading-5">
                        <span class="block font-bold text-antracite">Prenotati: <?= $prenotati ?></span>
                        <span class="block">Disponibili: <?= $postiDisponibili ?></span>
                      </div>
                      <div class="admin-submit-field"><button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Salva fascia</button></div>
                    </form>
                    <form method="POST" class="mt-3" onsubmit="return confirm('Eliminare questa fascia oraria?');">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="action" value="delete_fascia">
                      <input type="hidden" name="id_fascia" value="<?= (int)$fascia['id_fascia'] ?>">
                      <button type="submit" class="text-xs font-bold uppercase tracking-wide text-red-600 hover:underline" <?= $prenotati > 0 ? 'disabled title="Non puoi eliminare una fascia con biglietti già prenotati" style="opacity:.45;cursor:not-allowed"' : '' ?>>Elimina fascia</button>
                    </form>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- CATEGORIE RIDUZIONE E TARIFFE -->
  <section id="admin-categorie" class="scroll-mt-32 bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
    <div class="bg-antracite px-6 py-5">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Gestione</p>
      <h2 class="font-display text-2xl text-avorio font-bold">Categorie riduzione e tariffe</h2>
    </div>

    <div class="p-6 border-b border-avorio-dark bg-avorio space-y-8">
      <div>
        <h3 class="font-display text-xl font-bold text-antracite mb-2">Categorie riduzione</h3>
        <p class="text-sm text-gray-500 mb-4">Crea nuove categorie, poi selezionale nel menu a tendina delle tariffe.</p>
        <form method="POST" class="grid md:grid-cols-5 gap-4 items-end bg-white border border-avorio-dark rounded-2xl p-4 mb-5">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="create_categoria">
          <div class="md:col-span-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Nome categoria</label>
            <input type="text" name="nome" placeholder="Es. Famiglia, Gruppo, Universitario" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Sconto %</label>
            <input type="number" step="0.01" min="0" max="100" name="percentuale_sconto" value="0" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Documento richiesto</label>
            <input type="text" name="documento_richiesto" placeholder="Facoltativo" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div class="admin-submit-field"><button type="submit" class="btn-oro px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Crea categoria</button></div>
        </form>

        <?php if (empty($categorie)): ?>
          <p class="text-sm text-gray-500 bg-white border border-avorio-dark rounded-xl p-4">Nessuna categoria presente.</p>
        <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($categorie as $cat): ?>
              <?php
                $tariffeCollegate = (int)($cat['numero_tariffe'] ?? 0);
                $bigliettiCollegati = (int)($cat['numero_biglietti'] ?? 0);
                $categoriaInUso = $tariffeCollegate > 0 || $bigliettiCollegati > 0;
              ?>
              <div class="bg-white border border-avorio-dark rounded-xl p-4">
                <form method="POST" class="grid md:grid-cols-6 gap-3 items-end">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="action" value="update_categoria">
                  <input type="hidden" name="id_categoria" value="<?= (int)$cat['id_categoria'] ?>">
                  <div class="md:col-span-2">
                    <label class="text-xs font-bold text-gray-500 uppercase">Nome</label>
                    <input type="text" name="nome" value="<?= clean($cat['nome']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                  </div>
                  <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Sconto %</label>
                    <input type="number" step="0.01" min="0" max="100" name="percentuale_sconto" value="<?= clean((string)$cat['percentuale_sconto']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                  </div>
                  <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Documento</label>
                    <input type="text" name="documento_richiesto" value="<?= clean($cat['documento_richiesto'] ?? '') ?>" placeholder="Facoltativo" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                  </div>
                  <div class="text-xs text-gray-500 leading-5">
                    <span class="block font-bold text-antracite">Tariffe: <?= $tariffeCollegate ?></span>
                    <span class="block">Biglietti: <?= $bigliettiCollegati ?></span>
                  </div>
                  <div class="admin-submit-field"><button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Salva categoria</button></div>
                </form>
                <form method="POST" class="mt-3" onsubmit="return confirm('Eliminare questa categoria?');">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="action" value="delete_categoria">
                  <input type="hidden" name="id_categoria" value="<?= (int)$cat['id_categoria'] ?>">
                  <button type="submit" <?= $categoriaInUso ? 'disabled' : '' ?> class="text-xs uppercase tracking-wide <?= $categoriaInUso ? 'text-gray-300 cursor-not-allowed' : 'text-red-700 hover:underline' ?>">
                    Elimina categoria
                  </button>
                  <?php if ($categoriaInUso): ?>
                    <span class="ml-2 text-xs text-gray-400">Non eliminabile perché già utilizzata.</span>
                  <?php endif; ?>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div id="admin-tariffe" class="border-t border-avorio-dark pt-8 scroll-mt-32">
        <h3 class="font-display text-xl font-bold text-antracite mb-4">Crea nuova tariffa</h3>
        <form method="POST" class="grid md:grid-cols-4 gap-4">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="create_tariffa">
          <select name="tipo_biglietto" class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
            <?php foreach ($tipiBiglietto as $tipo): ?><option value="<?= $tipo ?>"><?= ucfirst($tipo) ?></option><?php endforeach; ?>
          </select>
          <select name="id_categoria" class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
            <?php foreach ($categorie as $cat): ?><option value="<?= (int)$cat['id_categoria'] ?>"><?= clean($cat['nome']) ?></option><?php endforeach; ?>
          </select>
          <input type="number" step="0.01" min="0" name="prezzo" placeholder="Prezzo" required class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
          <div class="admin-submit-field"><button type="submit" class="btn-oro px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Crea tariffa</button></div>
        </form>
      </div>
    </div>

    <div class="divide-y divide-avorio-dark">
      <?php foreach ($tariffe as $t): ?>
        <form method="POST" class="p-6 grid md:grid-cols-5 gap-4 items-end">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="update_tariffa">
          <input type="hidden" name="id_tariffa" value="<?= (int)$t['id_tariffa'] ?>">
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Tipo biglietto</label>
            <select name="tipo_biglietto" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
              <?php foreach ($tipiBiglietto as $tipo): ?>
                <option value="<?= $tipo ?>" <?= $t['tipo_biglietto'] === $tipo ? 'selected' : '' ?>><?= ucfirst($tipo) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Categoria</label>
            <select name="id_categoria" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
              <?php foreach ($categorie as $cat): ?>
                <option value="<?= (int)$cat['id_categoria'] ?>" <?= (int)$t['id_categoria'] === (int)$cat['id_categoria'] ? 'selected' : '' ?>><?= clean($cat['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Prezzo</label>
            <input type="number" step="0.01" min="0" name="prezzo" value="<?= clean((string)$t['prezzo']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div class="admin-submit-field"><button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Salva</button></div>
        </form>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- SERVIZI -->
  <section id="admin-servizi" class="scroll-mt-32 bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
    <div class="bg-antracite px-6 py-5">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Gestione</p>
      <h2 class="font-display text-2xl text-avorio font-bold">Servizi opzionali</h2>
    </div>

    <div class="p-6 border-b border-avorio-dark bg-avorio">
      <h3 class="font-display text-xl font-bold text-antracite mb-4">Crea nuovo servizio</h3>
      <form method="POST" class="grid md:grid-cols-5 gap-4">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="create_servizio">
        <input type="text" name="nome" placeholder="Nome servizio" required class="md:col-span-2 px-4 py-3 border border-gray-200 rounded-lg text-sm">
        <input type="number" step="0.01" min="0" name="prezzo" placeholder="Prezzo" required class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
        <textarea name="descrizione" placeholder="Descrizione" class="md:col-span-1 px-4 py-3 border border-gray-200 rounded-lg text-sm"></textarea>
        <div class="admin-submit-field"><button type="submit" class="btn-oro px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Crea</button></div>
      </form>
    </div>

    <div class="divide-y divide-avorio-dark">
      <?php foreach ($servizi as $s): ?>
        <form method="POST" class="p-6 grid md:grid-cols-5 gap-4 items-start">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="update_servizio">
          <input type="hidden" name="id_servizio" value="<?= (int)$s['id_servizio'] ?>">
          <div class="md:col-span-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Nome</label>
            <input type="text" name="nome" value="<?= clean($s['nome']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Prezzo</label>
            <input type="number" step="0.01" min="0" name="prezzo" value="<?= clean((string)$s['prezzo']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Descrizione</label>
            <textarea name="descrizione" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm"><?= clean($s['descrizione'] ?? '') ?></textarea>
          </div>
          <div class="flex items-end justify-end h-full admin-submit-field">
            <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Salva</button>
          </div>
        </form>
      <?php endforeach; ?>
    </div>
  </section>


  <!-- UTENTI -->
  <section id="admin-utenti" class="scroll-mt-32 bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
    <div class="bg-antracite px-6 py-5">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Gestione</p>
      <h2 class="font-display text-2xl text-avorio font-bold">Utenti e ruoli</h2>
      <p class="text-gray-400 text-sm mt-2">Da qui puoi aggiornare ruoli, forzare una nuova password, cambiare domanda di sicurezza o eliminare account.</p>
    </div>

    <div class="p-6 space-y-5 bg-avorio">
      <?php if (empty($utenti)): ?>
        <p class="bg-white border border-avorio-dark rounded-xl p-4 text-sm text-gray-500">Nessun utente presente.</p>
      <?php else: ?>
        <div class="bg-white border border-avorio-dark rounded-2xl shadow-sm p-5 space-y-3">
          <label for="cerca-email-utente" class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Cerca subito un utente per email</label>
          <div class="flex flex-col md:flex-row gap-3">
            <input type="search" id="cerca-email-utente" placeholder="Scrivi una mail, anche parziale..." autocomplete="off" class="flex-1 px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-oro/40">
            <button type="button" id="reset-cerca-email-utente" class="btn-outline px-5 py-3 rounded text-sm uppercase tracking-wide">Mostra tutti</button>
          </div>
          <p id="risultati-cerca-email-utente" class="text-xs text-gray-500">Puoi filtrare le card degli utenti senza scorrere tutta la lista.</p>
        </div>

        <p id="nessun-utente-trovato" class="hidden bg-white border border-avorio-dark rounded-xl p-4 text-sm text-gray-500">Nessun utente trovato con questa email.</p>

        <?php foreach ($utenti as $u): ?>
          <article class="admin-user-card bg-white border border-avorio-dark rounded-2xl shadow-sm overflow-hidden" data-email="<?= clean(strtolower($u['email'])) ?>">
            <div class="px-5 py-4 border-b border-avorio-dark flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
              <div>
                <h3 class="font-display text-xl font-bold text-antracite">
                  <?= clean($u['nome']) ?> <?= clean($u['cognome']) ?>
                </h3>
                <p class="text-sm text-gray-500 break-all"><?= clean($u['email']) ?></p>
              </div>
              <div class="flex flex-wrap gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide <?= in_array($u['ruolo'], ['amministratore','tester'], true) ? 'bg-oro text-antracite' : 'bg-gray-100 text-gray-700' ?>">
                  <?= clean($ruoloLabel[$u['ruolo']] ?? $u['ruolo']) ?>
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide <?= (int)$u['email_verificata'] === 1 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                  <?= (int)$u['email_verificata'] === 1 ? 'Email verificata' : 'Email non verificata' ?>
                </span>
              </div>
            </div>

            <div class="p-5 grid xl:grid-cols-2 gap-5">
              <?php $isCurrentUser = (int)$u['id_utente'] === (int)($_SESSION['utente_id'] ?? 0); ?>
              <form method="POST" class="bg-avorio rounded-xl border border-oro/20 p-4 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="update_user_role">
                <input type="hidden" name="id_utente" value="<?= (int)$u['id_utente'] ?>">
                <label class="block text-xs font-bold text-gray-500 uppercase">Ruolo utente</label>
                <?php if ($isCurrentUser): ?>
                  <div class="px-4 py-3 border border-oro/30 rounded-lg bg-white text-sm text-gray-600">
                    Il tuo ruolo non può essere modificato da qui.
                  </div>
                <?php else: ?>
                  <div class="flex flex-col sm:flex-row gap-3">
                    <select name="ruolo" class="flex-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                      <?php foreach ($ruoliDisponibili as $ruolo): ?>
                        <option value="<?= clean($ruolo) ?>" <?= $u['ruolo'] === $ruolo ? 'selected' : '' ?>>
                          <?= clean($ruoloLabel[$ruolo] ?? $ruolo) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide">Salva ruolo</button>
                  </div>
                <?php endif; ?>
              </form>

              <form method="POST" class="bg-avorio rounded-xl border border-oro/20 p-4 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="force_user_password">
                <input type="hidden" name="id_utente" value="<?= (int)$u['id_utente'] ?>">
                <label class="block text-xs font-bold text-gray-500 uppercase">Forza cambio password</label>
                <div class="flex flex-col sm:flex-row gap-3">
                  <input type="password" name="nuova_password" minlength="8" placeholder="Nuova password" class="flex-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                  <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide">Aggiorna</button>
                </div>
              </form>

              <form method="POST" class="bg-avorio rounded-xl border border-oro/20 p-4 space-y-3 xl:col-span-2">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="force_user_security">
                <input type="hidden" name="id_utente" value="<?= (int)$u['id_utente'] ?>">
                <label class="block text-xs font-bold text-gray-500 uppercase">Forza cambio domanda di sicurezza</label>
                <div class="grid md:grid-cols-3 gap-3">
                  <select name="domanda_sicurezza" class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
                    <?php foreach ($domandeSicurezza as $value => $label): ?>
                      <option value="<?= clean($value) ?>" <?= ($u['domanda_sicurezza'] ?? '') === $value ? 'selected' : '' ?>><?= clean($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="text" name="risposta_sicurezza" placeholder="Nuova risposta" class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
                  <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide">Aggiorna sicurezza</button>
                </div>
              </form>

              <form method="POST" class="xl:col-span-2 text-right" onsubmit="return confirm('Eliminare definitivamente questo account?');">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="id_utente" value="<?= (int)$u['id_utente'] ?>">
                <button type="submit" <?= (int)$u['id_utente'] === (int)$_SESSION['utente_id'] ? 'disabled title="Non puoi eliminare da qui il tuo account amministratore"' : '' ?> class="text-xs font-bold uppercase tracking-wide <?= (int)$u['id_utente'] === (int)$_SESSION['utente_id'] ? 'text-gray-300 cursor-not-allowed' : 'text-red-700 hover:underline' ?>">
                  Elimina account
                </button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const input = document.getElementById('cerca-email-utente');
  const reset = document.getElementById('reset-cerca-email-utente');
  const cards = Array.from(document.querySelectorAll('.admin-user-card'));
  const noResults = document.getElementById('nessun-utente-trovato');
  const resultsText = document.getElementById('risultati-cerca-email-utente');

  if (!input || !reset || cards.length === 0) {
    return;
  }

  function filtraUtenti() {
    const ricerca = input.value.trim().toLowerCase();
    let visibili = 0;

    cards.forEach(function (card) {
      const email = (card.dataset.email || '').toLowerCase();
      const match = ricerca === '' || email.includes(ricerca);
      card.classList.toggle('hidden', !match);
      if (match) {
        visibili++;
      }
    });

    if (noResults) {
      noResults.classList.toggle('hidden', visibili !== 0);
    }
    if (resultsText) {
      resultsText.textContent = ricerca === ''
        ? 'Puoi filtrare le card degli utenti senza scorrere tutta la lista.'
        : 'Utenti trovati: ' + visibili;
    }
  }

  input.addEventListener('input', filtraUtenti);
  reset.addEventListener('click', function () {
    input.value = '';
    filtraUtenti();
    input.focus();
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const input = document.getElementById('cerca-nome-esposizione');
  const reset = document.getElementById('reset-cerca-nome-esposizione');
  const cards = Array.from(document.querySelectorAll('.admin-exposition-card'));
  const noResults = document.getElementById('nessuna-esposizione-trovata');
  const resultsText = document.getElementById('risultati-cerca-nome-esposizione');

  if (!input || !reset || cards.length === 0) {
    return;
  }

  function filtraEsposizioni() {
    const ricerca = input.value.trim().toLowerCase();
    let visibili = 0;

    cards.forEach(function (card) {
      const titolo = (card.dataset.title || '').toLowerCase();
      const match = ricerca === '' || titolo.includes(ricerca);
      card.classList.toggle('hidden', !match);
      if (match) {
        visibili++;
      }
    });

    if (noResults) {
      noResults.classList.toggle('hidden', visibili !== 0);
    }
    if (resultsText) {
      resultsText.textContent = ricerca === ''
        ? 'Puoi filtrare le esposizioni senza scorrere tutta la lista.'
        : 'Esposizioni trovate: ' + visibili;
    }
  }

  input.addEventListener('input', filtraEsposizioni);
  reset.addEventListener('click', function () {
    input.value = '';
    filtraEsposizioni();
    input.focus();
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const openButton = document.getElementById('adminMobileMenuButton');
  const closeButton = document.getElementById('adminMobileMenuClose');
  const panel = document.getElementById('adminMobileMenuPanel');
  const backdrop = document.getElementById('adminMobileBackdrop');
  const links = panel ? Array.from(panel.querySelectorAll('a')) : [];

  if (!openButton || !closeButton || !panel || !backdrop) {
    return;
  }

  function setAdminMenu(open) {
    panel.hidden = !open;
    backdrop.hidden = !open;
    openButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('admin-menu-open', open);
  }

  openButton.addEventListener('click', function () {
    setAdminMenu(panel.hidden);
  });

  closeButton.addEventListener('click', function () {
    setAdminMenu(false);
  });

  backdrop.addEventListener('click', function () {
    setAdminMenu(false);
  });

  links.forEach(function (link) {
    link.addEventListener('click', function () {
      setAdminMenu(false);
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      setAdminMenu(false);
    }
  });
});
</script>


<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.floating-alert[data-auto-dismiss="true"]').forEach(function (alertBox) {
      window.setTimeout(function () {
        alertBox.style.transition = 'opacity .35s ease, transform .35s ease';
        alertBox.style.opacity = '0';
        alertBox.style.transform = 'translate(-50%, -8px)';
        window.setTimeout(function () {
          alertBox.remove();
        }, 400);
      }, 3500);
    });
  });
</script>

<?php include __DIR__ . '/footer.php'; ?>
