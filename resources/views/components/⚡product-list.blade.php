<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div>
    <table>
        <thead>
            <tr>
                <th wire:click="sortBy('name')">Name</th>
                <th wire:click="sortBy('price')">Price</th>
                <th>Image</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->price }}</td>
                    <td>
                        @if($product->images->first())
                            <img src="{{ $product->images->first()->url }}" width="50">
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $products->links() }}
</div>