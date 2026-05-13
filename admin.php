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

                if ($titolo === '' || !$dataInizio || !$dataFine || !in_array($stato, $stati, true)) {
                    throw new RuntimeException('Compila correttamente tutti i campi dell\'esposizione.');
                }
                if ($dataFine < $dataInizio) {
                    throw new RuntimeException('La data di fine non può precedere la data di inizio.');
                }

                if ($action === 'create_esposizione') {
                    $stmt = $pdo->prepare('INSERT INTO Esposizioni (titolo, descrizione, data_inizio, data_fine, stato) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$titolo, $descrizione, $dataInizio, $dataFine, $stato]);
                    $successMsg = 'Esposizione creata correttamente.';
                } else {
                    $id = (int)($_POST['id_esposizione'] ?? 0);
                    if ($id <= 0) throw new RuntimeException('ID esposizione non valido.');
                    $stmt = $pdo->prepare('UPDATE Esposizioni SET titolo = ?, descrizione = ?, data_inizio = ?, data_fine = ?, stato = ? WHERE id_esposizione = ?');
                    $stmt->execute([$titolo, $descrizione, $dataInizio, $dataFine, $stato, $id]);
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
    $esposizioni = $categorie = $tariffe = $servizi = $fasce = $fascePerEsposizione = [];
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
    <div class="alert-success p-4 rounded text-sm font-body">✅ <?= clean($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert-error p-4 rounded text-sm font-body">⚠️ <?= clean($errorMsg) ?></div>
  <?php endif; ?>

  <nav class="bg-white rounded-2xl shadow border border-avorio-dark p-5 sticky top-24 z-30">
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-3">Menu amministrazione</p>
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
      <a href="#admin-esposizioni" class="btn-outline text-center px-5 py-3 rounded text-sm uppercase tracking-wide">Esposizioni</a>
      <a href="#admin-categorie" class="btn-outline text-center px-5 py-3 rounded text-sm uppercase tracking-wide">Categorie riduzioni</a>
      <a href="#admin-tariffe" class="btn-outline text-center px-5 py-3 rounded text-sm uppercase tracking-wide">Tariffe</a>
      <a href="#admin-servizi" class="btn-outline text-center px-5 py-3 rounded text-sm uppercase tracking-wide">Servizi</a>
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
      <form method="POST" class="grid md:grid-cols-5 gap-4">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="create_esposizione">
        <input type="text" name="titolo" placeholder="Titolo" required class="md:col-span-2 px-4 py-3 border border-gray-200 rounded-lg text-sm">
        <input type="date" name="data_inizio" required class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
        <input type="date" name="data_fine" required class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
        <select name="stato" class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
          <?php foreach ($stati as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
        </select>
        <textarea name="descrizione" placeholder="Descrizione" class="md:col-span-4 px-4 py-3 border border-gray-200 rounded-lg text-sm"></textarea>
        <button type="submit" class="btn-oro px-5 py-3 rounded text-sm uppercase tracking-wide">Crea</button>
      </form>
    </div>

    <span id="admin-fasce" class="block scroll-mt-32"></span>
    <div class="divide-y divide-avorio-dark">
      <?php foreach ($esposizioni as $esp): ?>
        <?php $fasceEsp = $fascePerEsposizione[(int)$esp['id_esposizione']] ?? []; ?>
        <article class="m-4 md:m-6 rounded-2xl border-2 border-oro/30 bg-white shadow-lg overflow-hidden" id="admin-fasce-<?= (int)$esp['id_esposizione'] ?>">
          <div class="bg-avorio px-6 py-5 border-b border-oro/30 text-center md:text-left">
            <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Stai modificando questa esposizione</p>
            <h3 class="font-display text-2xl font-bold text-antracite"><?= clean($esp['titolo']) ?></h3>
            <p class="text-sm text-gray-500 mt-1">Le fasce orarie qui sotto appartengono solo a questa esposizione.</p>
          </div>
          <div class="p-6 space-y-6">
          <form method="POST" class="grid md:grid-cols-6 gap-4 items-start">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update_esposizione">
            <input type="hidden" name="id_esposizione" value="<?= (int)$esp['id_esposizione'] ?>">
            <div class="md:col-span-2">
              <label class="text-xs font-bold text-gray-500 uppercase">Titolo</label>
              <input type="text" name="titolo" value="<?= clean($esp['titolo']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
            </div>
            <div>
              <label class="text-xs font-bold text-gray-500 uppercase">Inizio</label>
              <input type="date" name="data_inizio" value="<?= clean($esp['data_inizio']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
            </div>
            <div>
              <label class="text-xs font-bold text-gray-500 uppercase">Fine</label>
              <input type="date" name="data_fine" value="<?= clean($esp['data_fine']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
            </div>
            <div>
              <label class="text-xs font-bold text-gray-500 uppercase">Stato</label>
              <select name="stato" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                <?php foreach ($stati as $s): ?>
                  <option value="<?= $s ?>" <?= $esp['stato'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="flex items-end h-full">
              <button type="submit" class="btn-outline w-full px-4 py-3 rounded text-sm uppercase tracking-wide">Salva</button>
            </div>
            <div class="md:col-span-6">
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

            <form method="POST" class="grid md:grid-cols-5 gap-3 items-end mb-5">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="create_fascia">
              <input type="hidden" name="id_esposizione" value="<?= (int)$esp['id_esposizione'] ?>">
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Giorno</label>
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
              <div class="md:col-span-2">
                <button type="submit" class="btn-oro w-full px-5 py-3 rounded text-sm uppercase tracking-wide">Aggiungi fascia</button>
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
                    <form method="POST" class="grid md:grid-cols-6 gap-3 items-end">
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
                      <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide">Salva fascia</button>
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
          <button type="submit" class="btn-oro px-5 py-3 rounded text-sm uppercase tracking-wide">Crea categoria</button>
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
                  <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide">Salva categoria</button>
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
          <button type="submit" class="btn-oro px-5 py-3 rounded text-sm uppercase tracking-wide">Crea tariffa</button>
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
          <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide">Salva</button>
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
        <button type="submit" class="btn-oro px-5 py-3 rounded text-sm uppercase tracking-wide">Crea</button>
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
          <div class="flex items-end h-full">
            <button type="submit" class="btn-outline w-full px-4 py-3 rounded text-sm uppercase tracking-wide">Salva</button>
          </div>
        </form>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
