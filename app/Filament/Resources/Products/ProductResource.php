<?php

namespace App\Filament\Resources;

use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Schemas\Schema;

class ProductResource extends Resource
{
    protected static ?string $model = \App\Models\Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('title')->required(),
                TextInput::make('sku')->required(),
                TextInput::make('price')->required(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('sku'),
                TextColumn::make('price'),
            ])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecords::route('/'),
            'create' => CreateRecord::route('/create'),
            'edit' => EditRecord::route('/{record}/edit'),
        ];
    }
}