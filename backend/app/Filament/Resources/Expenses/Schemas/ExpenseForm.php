<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('expense_category_id')
                    ->relationship('expenseCategory', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->default(0)
                    ->required(),
                DatePicker::make('expense_date')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Select::make('created_by')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
