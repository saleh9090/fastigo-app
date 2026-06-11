<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('arabic_name')
                    ->label('Company name (Arabic)'),
                TextInput::make('commercial_registration'),
                TextInput::make('contact_person')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Textarea::make('address')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(['active' => 'Active', 'suspended' => 'Suspended'])
                    ->default('active')
                    ->required(),
            ]);
    }
}
