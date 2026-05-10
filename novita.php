# info.php

```php
<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

$pageTitle = 'Informazioni - Museo Storico Severi';
include 'header.php';
?>

<main class="max-w-7xl mx-auto px-6 py-12">
    <section class="text-center mb-16">
        <h1 class="text-5xl font-bold mb-6 text-[#2C2C2C]">Informazioni sul Museo</h1>
        <p class="text-lg text-gray-700 max-w-3xl mx-auto leading-relaxed">
            Il Museo Storico Severi custodisce opere d’arte, reperti storici e collezioni permanenti dedicate alla cultura e alla memoria artistica.
        </p>
    </section>

    <section class="grid md:grid-cols-2 gap-10">
        <div class="bg-white rounded-2xl shadow-md p-8 border border-gray-100">
            <h2 class="text-2xl font-semibold mb-4 text-[#C9A84C]">Orari di apertura</h2>
            <ul class="space-y-2 text-gray-700">
                <li>Lunedì - Venerdì: 09:00 - 18:00</li>
                <li>Sabato: 10:00 - 20:00</li>
                <li>Domenica: 10:00 - 19:00</li>
            </ul>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-8 border border-gray-100">
            <h2 class="text-2xl font-semibold mb-4 text-[#6B8CAE]">Contatti</h2>
            <p class="text-gray-700 mb-2">📍 Via delle Arti 12, Padova</p>
            <p class="text-gray-700 mb-2">📞 +39 049 1234567</p>
            <p class="text-gray-700">✉ info@museoseveri.it</p>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
```

---

# novita.php

```php
<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

$pageTitle = 'Novità - Museo Storico Severi';
include 'header.php';
?>

<main class="max-w-7xl mx-auto px-6 py-12">
    <section class="text-center mb-16">
        <h1 class="text-5xl font-bold mb-6 text-[#2C2C2C]">Novità ed Eventi</h1>
        <p class="text-lg text-gray-700 max-w-3xl mx-auto leading-relaxed">
            Rimani aggiornato sulle ultime esposizioni, eventi culturali e iniziative del Museo Storico Severi.
        </p>
    </section>

    <section class="grid md:grid-cols-3 gap-8">
        <article class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 hover:shadow-lg transition">
            <img src="img/eventi/mostra1.jpg" alt="Mostra Rinascimentale" class="w-full h-56 object-cover">
            <div class="p-6">
                <h2 class="text-2xl font-semibold mb-3 text-[#C9A84C]">Mostra Rinascimentale</h2>
                <p class="text-gray-700 leading-relaxed">
                    Un viaggio immersivo tra le opere più iconiche del Rinascimento italiano.
                </p>
            </div>
        </article>

        <article class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 hover:shadow-lg transition">
            <img src="img/eventi/conferenza.jpg" alt="Conferenza Arte Moderna" class="w-full h-56 object-cover">
            <div class="p-6">
                <h2 class="text-2xl font-semibold mb-3 text-[#6B8CAE]">Conferenza Arte Moderna</h2>
                <p class="text-gray-700 leading-relaxed">
                    Incontro con storici dell’arte e approfondimenti sulle correnti contemporanee.
                </p>
            </div>
        </article>

        <article class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 hover:shadow-lg transition">
            <img src="img/eventi/laboratorio.jpg" alt="Laboratorio Creativo" class="w-full h-56 object-cover">
            <div class="p-6">
                <h2 class="text-2xl font-semibold mb-3 text-[#2C2C2C]">Laboratorio Creativo</h2>
                <p class="text-gray-700 leading-relaxed">
                    Attività didattiche e laboratori interattivi dedicati a studenti e famiglie.
                </p>
            </div>
        </article>
    </section>
</main>

<?php include 'footer.php'; ?>
```
