<?php

namespace App\Filament\Resources\Bills\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BillForm
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
                Select::make('customer_id')
                    ->relationship('customer', 'phone')
                    ->searchable()
                    ->preload(),
                TextInput::make('customer_phone')
                    ->tel()
                    ->required(),
                TextInput::make('bill_number')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('total_amount')
                    ->numeric()
                    ->default(0),
                TextInput::make('paid_amount')
                    ->numeric()
                    ->default(0),
                TextInput::make('remaining_amount')
                    ->numeric()
                    ->default(0),
                Select::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ])
                    ->default('unpaid')
                    ->required(),
                Select::make('status')
                    ->options([
                        'in_process' => 'In Process',
                        'ready' => 'Ready',
                        'delivered' => 'Delivered',
                    ])
                    ->default('in_process')
                    ->required(),
                Select::make('created_by')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
