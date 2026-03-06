Panoramica del Progetto
Il sistema implementa un crawler di prodotti e un sistema di importazione asincrona utilizzando l'ecosistema Laravel.

Stack Tecnologico
Backend: Laravel (Queue, HTTP Client)

Database: MySQL

Pannello Admin: Filament (Livewire, AlpineJS, TailwindCSS)

Architettura e Funzionalità
1. Product Crawler & Queue
Il sistema recupera i dati dall'endpoint esterno https://fakestoreapi.com/products.

Dati estratti: Titolo, prezzo, URL immagine, SKU.

Asincronicità: L'importazione è gestita tramite il Job ImportProductsJob. Questo approccio garantisce scalabilità senza bloccare i processi principali.

2. Modelli e Relazioni
La struttura del database segue una logica uno-a-molti:

Product: Gestisce le informazioni principali.

Image: Collega le risorse multimediali ai prodotti.

Relazione: Product → hasMany → Image.

3. Pannello di Controllo (Filament)
Accessibile a /admin, permette la gestione completa (CRUD) dei prodotti e l'anteprima delle immagini caricate.

Installazione Rapida
Clone & Setup:

Bash
git clone https://github.com/tuo-username/laravel-product-import.git
cd laravel-product-import
composer install

Ambiente:

Genera la chiave: php artisan key:generate.

Configura il database nel file .env.

Database & Admin:

Bash
php artisan migrate
# Crea l'utente tramite Tinker
php artisan tinker
"\App\Models\User::create([
    'name'=>'Admin',
    'email'=>'admin@test.com',
    'password'=>bcrypt('password')])"

Esecuzione:

Avvia il worker: php artisan queue:work.

Lancia l'importazione: php artisan import:products.

Avvia il server: php artisan serve.