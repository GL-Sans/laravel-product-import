<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use App\Models\Product;
use Illuminate\Support\Facades\App;
use Symfony\Component\DomCrawler\Crawler;

// Bootstrap di Laravel se esegui lo script fuori dal framework
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ─── Configurazione ───────────────────────────────────────────────────────────
const BASE_URL       = 'https://sandbox.oxylabs.io/products';
const PRODUCTS_LIMIT = 100;   // quanti prodotti importare (0 = tutti)
const ITEMS_PER_PAGE = 32;    // prodotti per pagina sul sito
const CONCURRENCY    = 5;     // richieste HTTP parallele
const REQUEST_DELAY  = 200;   // ms di pausa tra le richieste (cortesia)
// ─────────────────────────────────────────────────────────────────────────────

$outputFile = __DIR__ . '/data/products.json';
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0777, true);
}

$client = new Client([
    'timeout'         => 15,
    'connect_timeout' => 10,
    'headers'         => [
        'User-Agent' => 'Mozilla/5.0 (compatible; ProductImporter/1.0)',
        'Accept'     => 'text/html,application/xhtml+xml',
    ],
]);

// ─── Calcola il numero di pagine necessarie ───────────────────────────────────
$pagesNeeded = PRODUCTS_LIMIT > 0
    ? (int) ceil(PRODUCTS_LIMIT / ITEMS_PER_PAGE)
    : null; // null = scarica tutto

echo "Configurazione: limite=" . (PRODUCTS_LIMIT ?: 'tutti') . " prodotti, "
   . ($pagesNeeded ? "pagine=$pagesNeeded" : "pagine=tutte") . "\n";
echo "Connessione a " . BASE_URL . "...\n\n";

// ─── Scarica la prima pagina per scoprire il totale reale ─────────────────────
try {
    $firstHtml = $client->get(BASE_URL . '?page=1')->getBody()->getContents();
} catch (\Exception $e) {
    die("Errore nel caricamento della prima pagina: " . $e->getMessage() . "\n");
}

$firstCrawler = new Crawler($firstHtml);

// Ricava il numero totale di pagine dall'ultimo link di paginazione
$lastPageNumber = 1;
$firstCrawler->filter('a')->each(function (Crawler $node) use (&$lastPageNumber) {
    if (preg_match('/[?&]page=(\d+)/', $node->attr('href') ?? '', $m)) {
        $lastPageNumber = max($lastPageNumber, (int) $m[1]);
    }
});

$totalPages = $pagesNeeded ? min($pagesNeeded, $lastPageNumber) : $lastPageNumber;
echo "Pagine totali sul sito: $lastPageNumber — Pagine da scaricare: $totalPages\n\n";

// ─── Funzione di parsing HTML ─────────────────────────────────────────────────
function parseProducts(string $html, int &$idCounter): array
{
    $crawler   = new Crawler($html);
    $extracted = [];

    $crawler->filter('li.product-item, div.product-item, [class*="product"]')
        ->each(function (Crawler $node) use (&$extracted, &$idCounter) {
            // Prova selettori comuni; il sito usa tag <li> con anchor inside
            $titleNode = $node->filter('h4, h3, h2, .title, [class*="title"]');
            if (!$titleNode->count()) return;

            $title = trim($titleNode->first()->text());
            if (!$title) return;

            // Prezzo: cerca il testo che contiene "€" o cifre con virgola/punto
            $priceRaw = '';
            $node->filter('*')->each(function (Crawler $n) use (&$priceRaw) {
                if ($priceRaw) return;
                $t = trim($n->text());
                if (preg_match('/[\d,\.]+\s*€/', $t)) {
                    $priceRaw = $t;
                }
            });
            $price = $priceRaw
                ? (float) str_replace([',', '€', ' '], ['.', '', ''], $priceRaw)
                : 0.0;

            // Generi / categorie: spesso sono testi piccoli o tag
            $genres = [];
            $node->filter('p, span, small, [class*="genre"], [class*="tag"]')
                ->each(function (Crawler $n) use (&$genres) {
                    $t = trim($n->text());
                    if ($t && strlen($t) < 60 && !preg_match('/[€\d]/', $t)) {
                        $genres[] = $t;
                    }
                });

            // Descrizione: cerca il paragrafo più lungo
            $description = '';
            $node->filter('p')->each(function (Crawler $n) use (&$description) {
                $t = trim($n->text());
                if (strlen($t) > strlen($description)) {
                    $description = $t;
                }
            });

            // URL del prodotto
            $href     = $node->filter('a')->count() ? $node->filter('a')->first()->attr('href') : '';
            $fullHref = $href ? rtrim('https://sandbox.oxylabs.io', '/') . '/' . ltrim($href, '/') : '';

            // ID dal path /products/{id}
            preg_match('#/products/(\d+)#', $href ?? '', $idMatch);
            $remoteId  = $idMatch[1] ?? null;
            $idCounter = $remoteId ? max($idCounter, (int) $remoteId) : $idCounter;

            $extracted[] = [
                'name'        => $title,
                'sku'         => 'OXY-' . ($remoteId ?? (++$idCounter)),
                'price'       => $price,
                'description' => $description ?: null,
                'category'    => !empty($genres) ? implode(', ', array_unique($genres)) : null,
                'image_url'   => null, // immagini inline SVG, non HTTP URL recuperabile
                'source_url'  => $fullHref ?: null,
            ];
        });

    // Fallback: selettore anchor con h4 diretti (struttura del sito sandbox)
    if (empty($extracted)) {
        $crawler->filter('a')->each(function (Crawler $anchor) use (&$extracted, &$idCounter) {
            if (!preg_match('#/products/(\d+)#', $anchor->attr('href') ?? '', $idMatch)) return;

            $remoteId  = (int) $idMatch[1];
            $idCounter = max($idCounter, $remoteId);

            $title = trim($anchor->filter('h4')->count()
                ? $anchor->filter('h4')->text()
                : $anchor->text());
            if (!$title) return;

            // Prezzo e generi sono fratelli dell'anchor; risali al genitore <li>
            $li = $anchor; // in questo sito il genitore <li> non è wrappato nell'anchor
            // Cerca prezzo nel testo completo del fragment HTML
            preg_match('/([\d,\.]+)\s*€/', $anchor->html(), $priceMatch);
            $price = $priceMatch ? (float) str_replace(',', '.', $priceMatch[1]) : 0.0;

            // Raccoglie testi brevi come generi
            $allText  = strip_tags($anchor->html());
            $lines    = array_filter(array_map('trim', explode("\n", $allText)));
            $genres   = [];
            $descParts = [];
            foreach ($lines as $line) {
                if ($line === $title) continue;
                if (preg_match('/€/', $line)) continue;
                if (in_array(strtolower($line), ['add to basket', 'add to cart'])) continue;
                if (strlen($line) < 60) {
                    $genres[] = $line;
                } else {
                    $descParts[] = $line;
                }
            }

            $extracted[] = [
                'name'        => $title,
                'sku'         => 'OXY-' . $remoteId,
                'price'       => $price,
                'description' => !empty($descParts) ? implode(' ', $descParts) : null,
                'category'    => !empty($genres) ? implode(', ', array_unique($genres)) : null,
                'image_url'   => null,
                'source_url'  => 'https://sandbox.oxylabs.io/products/' . $remoteId,
            ];
        });
    }

    return $extracted;
}

// ─── Scarica e processa tutte le pagine ───────────────────────────────────────
$allProducts = [];
$idCounter   = 0;
$errors      = 0;

// Elabora prima pagina già scaricata
$parsed = parseProducts($firstHtml, $idCounter);
$allProducts = array_merge($allProducts, $parsed);
echo "Pagina 1/$totalPages — " . count($parsed) . " prodotti trovati\n";

// Pagine 2..N con richieste concorrenti
if ($totalPages > 1) {
    $pageNumbers = range(2, $totalPages);
    $requests    = function () use ($client, $pageNumbers) {
        foreach ($pageNumbers as $page) {
            yield $page => new Request('GET', BASE_URL . '?page=' . $page);
        }
    };

    $pool = new Pool($client, $requests(), [
        'concurrency' => CONCURRENCY,
        'fulfilled'   => function ($response, $page) use (&$allProducts, &$idCounter, $totalPages) {
            $html   = $response->getBody()->getContents();
            $parsed = parseProducts($html, $idCounter);
            $allProducts = array_merge($allProducts, $parsed);
            echo "Pagina $page/$totalPages — " . count($parsed) . " prodotti trovati\n";
            usleep(REQUEST_DELAY * 1000);
        },
        'rejected' => function ($reason, $page) use (&$errors) {
            echo "⚠️  Pagina $page fallita: " . $reason->getMessage() . "\n";
            $errors++;
        },
    ]);

    $promise = $pool->promise();
    $promise->wait();
}

// ─── Applica il limite ────────────────────────────────────────────────────────
if (PRODUCTS_LIMIT > 0 && count($allProducts) > PRODUCTS_LIMIT) {
    $allProducts = array_slice($allProducts, 0, PRODUCTS_LIMIT);
}

// Deduplica per SKU
$seen    = [];
$unique  = [];
foreach ($allProducts as $p) {
    if (!isset($seen[$p['sku']])) {
        $seen[$p['sku']] = true;
        $unique[]        = $p;
    }
}
$allProducts = $unique;

echo "\n── Totale prodotti raccolti: " . count($allProducts) . " ──\n\n";

// ─── Salva su file JSON ───────────────────────────────────────────────────────
file_put_contents($outputFile, json_encode($allProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "File JSON salvato in: $outputFile\n";

// ─── Importa nel database Laravel ────────────────────────────────────────────
echo "Importazione nel database...\n";
$imported = 0;
foreach ($allProducts as $p) {
    try {
        Product::updateOrCreate(
            ['sku' => $p['sku']],
            $p
        );
        $imported++;
    } catch (\Exception $e) {
        echo "  ⚠️  Errore SKU {$p['sku']}: " . $e->getMessage() . "\n";
    }
}

echo "Importati/aggiornati: $imported prodotti"
   . ($errors ? " ($errors pagine con errori)" : "") . "\n";