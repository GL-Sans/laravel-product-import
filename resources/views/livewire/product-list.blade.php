<div class="min-h-screen bg-gray-50 py-10 px-4">

    <div class="max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Catalogo Prodotti</h1>
            <p class="text-gray-500 mt-1">{{ $products->total() }} prodotti trovati</p>
        </div>

        {{-- Filtri --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 mb-6 flex flex-col sm:flex-row gap-4">

            {{-- Ricerca --}}
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-600 mb-1">Cerca</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Nome prodotto..."
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
            </div>

            {{-- Filtro categoria --}}
            <div class="sm:w-64">
                <label class="block text-sm font-medium text-gray-600 mb-1">Categoria</label>
                <select
                    wire:model.live="categoryFilter"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">Tutte le categorie</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- Tabella --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-16">
                            Img
                        </th>

                        {{-- Colonna Nome --}}
                        <th
                            wire:click="sortBy('name')"
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:text-indigo-600 select-none"
                        >
                            <div class="flex items-center gap-1">
                                Nome
                                @if($sortField === 'name')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @else
                                    <span class="opacity-30">↕</span>
                                @endif
                            </div>
                        </th>

                        {{-- Colonna Categoria --}}
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">
                            Categoria
                        </th>

                        {{-- Colonna Prezzo --}}
                        <th
                            wire:click="sortBy('price')"
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:text-indigo-600 select-none"
                        >
                            <div class="flex items-center gap-1">
                                Prezzo
                                @if($sortField === 'price')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @else
                                    <span class="opacity-30">↕</span>
                                @endif
                            </div>
                        </th>

                        {{-- Colonna Data --}}
                        <th
                            wire:click="sortBy('created_at')"
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:text-indigo-600 select-none hidden lg:table-cell"
                        >
                            <div class="flex items-center gap-1">
                                Aggiunto
                                @if($sortField === 'created_at')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @else
                                    <span class="opacity-30">↕</span>
                                @endif
                            </div>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        <tr
                            class="hover:bg-indigo-50 transition-colors duration-150"
                            x-data="{ open: false }"
                        >
                            {{-- Immagine --}}
                            <td class="px-4 py-3">
                                @if($product->images->first())
                                    <img
                                        src="{{ $product->images->first()->url }}"
                                        alt="{{ $product->name }}"
                                        class="w-12 h-12 object-cover rounded-lg border border-gray-200"
                                    />
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-xs">
                                        N/A
                                    </div>
                                @endif
                            </td>

                            {{-- Nome + descrizione espandibile --}}
                            <td class="px-4 py-3">
                                <div
                                    class="font-medium text-gray-900 cursor-pointer hover:text-indigo-600"
                                    @click="open = !open"
                                >
                                    {{ $product->name }}
                                </div>
                                <div
                                    x-show="open"
                                    x-transition
                                    class="mt-1 text-sm text-gray-500 max-w-lg"
                                >
                                    {{ $product->description ?? 'Nessuna descrizione disponibile.' }}
                                </div>
                            </td>

                            {{-- Categoria --}}
                            <td class="px-4 py-3 hidden md:table-cell">
                                @if($product->category)
                                    <span class="inline-block bg-indigo-100 text-indigo-700 text-xs font-medium px-2 py-1 rounded-full">
                                        {{ Str::limit($product->category, 30) }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>

                            {{-- Prezzo --}}
                            <td class="px-4 py-3 font-semibold text-gray-800">
                                € {{ number_format($product->price, 2, ',', '.') }}
                            </td>

                            {{-- Data --}}
                            <td class="px-4 py-3 text-sm text-gray-400 hidden lg:table-cell">
                                {{ $product->created_at->format('d/m/Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                                Nessun prodotto trovato.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginazione --}}
        <div class="mt-6">
            {{ $products->links() }}
        </div>

    </div>

</div>