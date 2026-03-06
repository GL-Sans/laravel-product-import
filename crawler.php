<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

$outputFile = __DIR__ . '/data/products.json';

// Crea la cartella data se non esiste
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0777, true);
}

echo "📥 Scaricando prodotti dal sandbox...\n";

$client = new Client([
    'base_uri' => 'https://sandbox.oxylabs.io/',
    'timeout'  => 10,
]);

try {
    $response = $client->get('products', [
        'headers' => [
            'Accept' => 'application/json',
        ]
    ]);

    $body = $response->getBody()->getContents();

    // Decodifica JSON
    $productsData = json_decode($body, true);

    if (!is_array($productsData)) {
        throw new Exception("Dati non validi ricevuti dal sandbox.");
    }

    // Mappa i dati per il nostro import
    $products = [];
    foreach ($productsData as $item) {
        $products[] = [
            'name' => $item['title'] ?? 'N/A',
            'sku' => $item['sku'] ?? null,
            'price' => $item['price'] ?? 0,
            'description' => $item['description'] ?? null,
            'category' => $item['category'] ?? null,
            'image_url' => $item['image_url'] ?? null,
        ];
    }

    file_put_contents($outputFile, json_encode($products, JSON_PRETTY_PRINT));

    echo "✅ Prodotti salvati in $outputFile\n";

} catch (\Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}