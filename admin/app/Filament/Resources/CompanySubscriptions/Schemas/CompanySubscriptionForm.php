<?php

namespace App\Filament\Resources\CompanySubscriptions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class CompanySubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => request()->integer('company_id') ?: null)
                    ->disabled(fn () => request()->filled('company_id'))
                    ->dehydrated()
                    ->required(),
                Select::make('subscription_package_id')
                    ->relationship('subscriptionPackage', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                DatePicker::make('subscription_start'),
                DatePicker::make('subscription_end'),
            ]);
    }
}
