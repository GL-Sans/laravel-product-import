<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DomCrawler\Crawler;
use App\Jobs\ImportProductsJob;

class ImportProducts extends Command
{
    protected $signature = 'import:products {--limit=100 : Numero massimo di prodotti da importare}';
    protected $description = 'Importa prodotti da sandbox.oxylabs.io';

    const BASE_URL       = 'https://sandbox.oxylabs.io/products';
    const ITEMS_PER_PAGE = 32;
    const CONCURRENCY    = 5;

    public function handle()
    {
        $limit       = (int) $this->option('limit');
        $pagesNeeded = (int) ceil($limit / self::ITEMS_PER_PAGE);

        $this->info("Avvio import — limite: $limit prodotti, pagine: $pagesNeeded");

        $client = new Client([
            'timeout' => 15,
            'headers' => ['User-Agent' => 'Mozilla/5.0 (compatible; ProductImporter/1.0)'],
        ]);

        // Scarica prima pagina e ricava il totale pagine
        $firstHtml  = $client->get(self::BASE_URL . '?page=1')->getBody()->getContents();
        $firstData  = $this->extractNextData($firstHtml);
        $lastPage   = $firstData['pageCount'] ?? 1;
        $totalPages = min($pagesNeeded, $lastPage);

        $this->info("Pagine totali sul sito: $lastPage — Da scaricare: $totalPages");

        $allProducts = $this->parseProducts($firstData['products'] ?? []);
        $this->info("Pagina 1/$totalPages — " . count($allProducts) . " prodotti");

        // Pagine 2..N in parallelo
        if ($totalPages > 1) {
            $pageNumbers = range(2, $totalPages);

            $requests = function () use ($pageNumbers) {
                foreach ($pageNumbers as $page) {
                    yield $page => new Request('GET', self::BASE_URL . '?page=' . $page);
                }
            };

            $pool = new Pool($client, $requests(), [
                'concurrency' => self::CONCURRENCY,
                'fulfilled'   => function ($response, $page) use (&$allProducts, $totalPages) {
                    $data        = $this->extractNextData($response->getBody()->getContents());
                    $parsed      = $this->parseProducts($data['products'] ?? []);
                    $allProducts = array_merge($allProducts, $parsed);
                    $this->info("Pagina $page/$totalPages — " . count($parsed) . " prodotti");
                },
                'rejected' => function ($reason, $page) {
                    $this->warn("Pagina $page fallita: " . $reason->getMessage());
                },
            ]);

            $pool->promise()->wait();
        }

        // Deduplica e applica limite
        $allProducts = collect($allProducts)
            ->unique('sku')
            ->take($limit)
            ->values();

        $this->info("Dispatch di {$allProducts->count()} job in coda...");

        $bar = $this->output->createProgressBar($allProducts->count());
        $bar->start();

        foreach ($allProducts as $product) {
            ImportProductsJob::dispatch($product);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Tutti i job sono stati messi in coda.');
    }

    // ── Estrae il JSON da __NEXT_DATA__ ───────────────────────────────────────

    private function extractNextData(string $html): array
    {
        $crawler = new Crawler($html);
        $script  = $crawler->filter('#__NEXT_DATA__');

        if (!$script->count()) return [];

        $json = json_decode($script->text(), true);
        return $json['props']['pageProps'] ?? [];
    }

    // ── Mappa i prodotti dal JSON al formato del DB ───────────────────────────

    private function parseProducts(array $items): array
    {
        $products = [];

        foreach ($items as $item) {
            // genre è una stringa tipo "['Action Adventure', 'Fantasy']"
            $genreRaw = $item['genre'] ?? '';
            preg_match_all("/'([^']+)'/", $genreRaw, $genreMatches);
            $category = !empty($genreMatches[1])
                ? implode(', ', $genreMatches[1])
                : null;

            // platform è una stringa tipo "['nintendo-64']"
            $platformRaw = $item['platform'] ?? '';
            preg_match_all("/'([^']+)'/", $platformRaw, $platformMatches);
            $platform = !empty($platformMatches[1])
                ? implode(', ', $platformMatches[1])
                : null;

            // Il JSON non ha un campo price, usiamo meta_score come base
            $metaScore = $item['meta_score'] ?? 0;
            $price     = round($metaScore * 0.99, 2);

            $products[] = [
                'name'        => $item['game_name'] ?? 'N/A',
                'sku'         => 'OXY-' . ($item['id'] ?? uniqid()),
                'price'       => $price,
                'description' => $item['description'] ?? null,
                'category'    => $category,
                'image_url'   => null,
                'source_url'  => $item['url'] ?? null,
                'platform'    => $platform,
                'rating'      => $item['rating'] ?? null,
                'developer'   => $item['developer'] ?? null,
            ];
        }

        return $products;
    }
}