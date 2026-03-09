<?php

use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\Image;
use App\Jobs\ImportProductsJob;
use App\Livewire\ProductList;

Route::get('/test-rel', function () {

    $product = Product::create([
        'name' => 'Test Product',
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

Route::get('/view/products', \App\Livewire\ProductList::class);