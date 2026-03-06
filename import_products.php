<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

$products = json_decode(file_get_contents('products.json'), true);

$client = new Client([
    'base_uri' => 'http://localhost:8000', // URL locale Laravel
]);

foreach ($products as $product) {
    $response = $client->post('/api/import', [
        'json' => $product,
    ]);
    echo $response->getBody() . "\n";
}