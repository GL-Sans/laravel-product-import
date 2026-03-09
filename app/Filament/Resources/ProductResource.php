<?php

namespace App\Filament\Resources;

use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Schemas\Schema;
use App\Filament\Resources\ProductResource\Pages;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Products';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->columnSpanFull(),
                TextInput::make('sku')->required(),
                TextInput::make('price')->numeric()->required(),
                TextInput::make('category'),
                TextInput::make('source_url')->url()->columnSpanFull(),
                Textarea::make('description')->columnSpanFull(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sku')->searchable(),
                TextColumn::make('price')->money('EUR')->sortable(),
                TextColumn::make('category')->searchable(),
            ])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}