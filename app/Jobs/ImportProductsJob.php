<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $productData;

    public function __construct(array $productData)
    {
        $this->productData = $productData;
    }

    public function handle()
    {
        $product = Product::create([
            'name' => $this->productData['name'] ?? 'N/A',
            'sku' => $this->productData['sku'] ?? null,
            'price' => $this->productData['price'] ?? 0,
            'description' => $this->productData['description'] ?? null,
            'category' => $this->productData['category'] ?? null
        ]);

        if (!empty($this->productData['image_url'])) {
            $product->images()->create([
                'url' => $this->productData['image_url']
            ]);
        }
    }
}