<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ImportProductsRequest;
use App\Jobs\ImportProductsJob;
use App\Http\Controllers\Controller;

class ProductImportController extends Controller
{
    public function import(ImportProductsRequest $request)
    {
        foreach ($request->validated() as $product) {
            ImportProductsJob::dispatch($product);
        }

        return response()->json([
            'status' => 'Import started'
        ]);
    }
}