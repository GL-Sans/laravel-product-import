<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $product = Product::create([
            'name' => $request->name,
            'sku' => $request->sku,
            'price' => $request->price
        ]);

        return response()->json($product);
    }
}
