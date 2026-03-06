<?php 

use Illuminate\Support\Facades\Route;
use App\Jobs\ImportProductsJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ProductImportController;
use App\Http\Controllers\ProductController;

Route::post('/import', [ProductImportController::class, 'import']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products', [ProductController::class, 'index']);
