<?php
http_response_code(404);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Pagina non trovata';

include __DIR__ . '/header.php';
?>

<main class="min-h-screen bg-avorio py-16 px-4 flex items-center">
    <section class="max-w-3xl mx-auto w-full bg-white rounded-2xl shadow-xl border border-gray-100 p-8 md:p-12 text-center">
        <img
            src="<?= SITE_URL ?>/img/logo.svg"
            alt="Logo Museo Storico Severi"
            class="h-28 w-auto mx-auto mb-6 object-contain"
        >

        <p class="text-oro text-xs uppercase tracking-[0.25em] font-body font-bold mb-3">
            Errore 404
        </p>

        <h1 class="font-display text-4xl md:text-5xl font-bold text-antracite mb-5">
            Pagina non trovata
        </h1>

        <p class="text-gray-600 text-lg leading-relaxed mb-8">
            L’indirizzo che hai inserito non è corretto oppure la pagina che stai cercando non è più disponibile.
            Puoi tornare alla homepage del Museo Storico Severi o rientrare alla pagina precedente.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a
                href="<?= SITE_URL ?>/index.php"
                class="inline-flex justify-center items-center bg-oro text-antracite font-bold px-6 py-3 rounded-xl hover:bg-oro-dark transition-colors"
            >
                Torna alla Home
            </a>

            <a
                href="javascript:history.back()"
                class="inline-flex justify-center items-center border-2 border-oro text-oro font-bold px-6 py-3 rounded-xl hover:bg-oro hover:text-antracite transition-colors"
            >
                Torna indietro
            </a>
        </div>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
