<?php
require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;
use App\Jobs\ImportProductsJob;

echo "📥 Avvio import prodotti...\n";

// Carica JSON locale
$data = json_decode(file_get_contents(__DIR__ . '/data/products.json'), true);

if (!$data) {
    die("❌ Errore: dati non validi\n");
}

// Manda i dati alla queue Laravel
foreach ($data as $product) {
    ImportProductsJob::dispatch($product);
}

echo "✅ Tutti i prodotti sono stati inviati alla queue.\n";