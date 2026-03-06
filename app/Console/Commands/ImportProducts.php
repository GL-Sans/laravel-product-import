<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Jobs\ImportProductsJob;

class ImportProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    

public function handle()
    {

        $response = Http::get('https://fakestoreapi.com/products');

        $products = $response->json();

        foreach ($products as $product) {

            ImportProductsJob::dispatch([
                'name' => $product['title'],
                'sku' => uniqid(),
                'price' => $product['price'],
                'image_url' => $product['image'],
            ]);

        }

        $this->info('Products imported');

    }
}
