<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ProductList extends Component
{
    use WithPagination;

    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public string $search = '';
    public string $categoryFilter = '';

    protected $queryString = [
        'search'          => ['except' => ''],
        'categoryFilter'  => ['except' => ''],
        'sortField'       => ['except' => 'created_at'],
        'sortDirection'   => ['except' => 'desc'],
    ];

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->when($this->search, fn ($q) =>
                $q->where('name', 'like', '%' . $this->search . '%')
            )
            ->when($this->categoryFilter, fn ($q) =>
                $q->where('category', 'like', '%' . $this->categoryFilter . '%')
            )
            ->with('images')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(25);

        $categories = Product::query()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->flatMap(fn ($c) => explode(', ', $c))
            ->unique()
            ->sort()
            ->values();

        return view('livewire.product-list', compact('products', 'categories'));
    }
}