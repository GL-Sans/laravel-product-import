<?php

use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\Image;

Route::get('/test-rel', function () {

    $product = Product::create([
        'title' => 'Test Product',
        'price' => 20,
        'category' => 'Test'
    ]);

    $product->images()->create([
        'url' => 'test-image.jpg'
    ]);

    return $product->load('images');
});

Route::get('/', function () {
    return 'API running';
});

/* Route::get('/seed-test', function () {
    Product::create([
        'title' => 'Prodotto Test',
        'price' => 19.99,
        'category' => 'Categoria A'
    ]);

    return 'Prodotto creato';
});

Route::get('/products', function () {
    $products = Product::all();
    return view('products', compact('products'));
});
*/