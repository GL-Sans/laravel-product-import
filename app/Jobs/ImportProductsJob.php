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
        $product = Product::updateOrCreate(
            ['sku' => $this->productData['sku']],
            [
                'name'        => $this->productData['name'],
                'price'       => $this->productData['price'] ?? 0,
                'description' => $this->productData['description'] ?? null,
                'category'    => $this->productData['category'] ?? null,
            ]
        );

        if (!empty($this->productData['image_url'])) {
            $product->images()->updateOrCreate(
                ['url' => $this->productData['image_url']]
            );
        }
    }
}