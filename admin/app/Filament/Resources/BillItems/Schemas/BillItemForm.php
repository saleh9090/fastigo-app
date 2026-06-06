<?php

namespace App\Filament\Resources\BillItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BillItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bill_id')
                    ->relationship('bill', 'bill_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('description'),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(1)
                    ->required(),
                TextInput::make('unit_price')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('total')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }
}
