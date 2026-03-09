# Laravel Product Import

Sistema di scraping e importazione asincrona di prodotti da [sandbox.oxylabs.io](https://sandbox.oxylabs.io/products), con pannello di amministrazione Filament e frontend dinamico Livewire.

---

## Stack Tecnologico

| Area | Tecnologie |
|---|---|
| Backend | Laravel 11, PHP 8.2+ |
| Scraping | Guzzle HTTP, Symfony DomCrawler |
| Queue | Laravel Queue (driver: database) |
| Database | MySQL |
| Admin Panel | Filament 3 |
| Frontend | Livewire 3, AlpineJS, TailwindCSS |

---

## Architettura e Funzionalità

### 1. Crawler — `ImportProducts` Artisan Command

Il comando `php artisan import:products` esegue lo scraping di `sandbox.oxylabs.io`.

Il sito è costruito con **Next.js** e il contenuto dei prodotti viene renderizzato lato client tramite JavaScript, quindi non è accessibile direttamente nel DOM HTML. La soluzione adottata è estrarre il tag `<script id="__NEXT_DATA__">` che Next.js inietta in ogni pagina con i dati pre-idratati in formato JSON. Symfony DomCrawler viene utilizzato per selezionare questo nodo del DOM e json_decode per accedere ai dati strutturati.

```
HTML statico → DomCrawler → #__NEXT_DATA__ → JSON → parseProducts()
```

**Dati estratti per ogni prodotto:**
- `game_name` → `name`
- `id` → `sku` (formato `OXY-{id}`)
- `description`
- `genre` → `category`
- `platform`, `developer`, `rating`
- `url` → `source_url`

> **Nota sul campo `price`:** Il prezzo visualizzato sul sito (`91,99 €`) è generato esclusivamente dal JavaScript del browser e non è presente nel JSON statico né in nessun endpoint API del sito. Il valore viene quindi calcolato proporzionalmente al `meta_score` del gioco con la formula `(meta_score / 100) * 80 + 19.99`, producendo un range realistico tra ~20€ e ~100€.

Le pagine vengono scaricate in **parallelo** tramite `GuzzleHttp\Pool` con concurrency configurabile.

### 2. Importazione Asincrona

La route `POST /api/import` accetta un array JSON di prodotti e li mette in coda tramite `ImportProductsJob`. Ogni job salva il prodotto e la relativa immagine in modo atomico.

```
POST /api/import → ImportProductsJob (queue) → Product::updateOrCreate() + Image::create()
```

L'uso di `updateOrCreate` sulla colonna `sku` garantisce l'idempotenza: rieseguire l'import non crea duplicati.

### 3. Modelli e Relazioni

```
Product (1) ──hasMany──> Image (N)
Image   (N) ──belongsTo─> Product (1)
```

La tabella `images` ha una foreign key `product_id` con `onDelete('cascade')`.

### 4. Pannello Admin — Filament

Accessibile su `/admin`. Permette:
- Visualizzazione di tutti i prodotti con preview immagine
- Modifica e cancellazione dei record
- Ricerca per nome, SKU, categoria
- Ordinamento per nome e prezzo

### 5. Frontend — Livewire + AlpineJS

Pagina pubblica su `/view/products`:
- Prodotti impaginati (25 per pagina)
- Ordinamento dinamico per nome, prezzo, data inserimento
- Filtro per categoria
- Reattiva senza ricaricamenti pagina (Livewire)
- Layout responsive con TailwindCSS

---

## Prerequisiti

- PHP >= 8.2
- Composer
- Node.js >= 18 + npm
- Estensioni PHP: `pdo_sqlite`, `curl`, `dom`, `json`

---

## Installazione

### 1. Clone e dipendenze

```bash
git clone https://github.com/tuo-username/laravel-product-import.git
cd laravel-product-import
composer install
npm install && npm run build
```

### 2. Configurazione ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Modifica `.env` con i dati del tuo database:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

QUEUE_CONNECTION=database
```

### 3. Database e migrazioni

```bash
php artisan migrate
```

### 4. Crea utente admin Filament

```bash
php artisan make:filament-user
```

---

## Esecuzione

Avvia tutti i servizi in terminali separati:

```bash
# Terminale 1 — Worker queue
php artisan queue:work

# Terminale 2 — Server locale
php artisan serve
```

### Importa i prodotti

```bash
# Importa 100 prodotti (default)
php artisan import:products

# Importa un numero specifico
php artisan import:products --limit=50
php artisan import:products --limit=200
```

---

## Route principali

| Metodo | URL | Descrizione |
|---|---|---|
| GET | `/` | Homepage |
| GET | `/view/products` | Frontend prodotti (Livewire) |
| POST | `/api/import` | Importazione via API JSON |
| GET | `/api/products` | Lista prodotti JSON |
| GET | `/admin` | Pannello Filament |

---

## Struttura del Progetto

```
app/
├── Console/Commands/
│   └── ImportProducts.php        # Crawler + dispatch job
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── ProductImportController.php  # POST /api/import
│   │   └── ProductController.php
│   └── Requests/
│       └── ImportProductsRequest.php
├── Jobs/
│   └── ImportProductsJob.php     # Salvataggio asincrono
├── Livewire/
│   └── ProductList.php           # Componente /view/products
├── Models/
│   ├── Product.php               # hasMany(Image)
│   └── Image.php                 # belongsTo(Product)
└── Filament/Resources/
    └── ProductResource.php       # Admin CRUD
```

---

## Note Tecniche

- **Idempotenza:** L'import può essere rieseguito senza creare duplicati grazie a `updateOrCreate` con chiave `sku`.
- **Concurrency:** Le pagine del sito vengono scaricate in parallelo (default: 5 connessioni simultanee) tramite `GuzzleHttp\Pool`.
- **Next.js scraping:** L'estrazione dei dati sfrutta il JSON iniettato da Next.js nel DOM (`#__NEXT_DATA__`), una tecnica più affidabile del parsing HTML per siti SPA/SSR.