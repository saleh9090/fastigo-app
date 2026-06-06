<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->saved(fn (?string $state): bool => filled($state)),
                TextInput::make('phone')
                    ->tel(),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('role')
                    ->options([
                        'platform_admin' => 'Platform Admin',
                        'company_manager' => 'Company Manager',
                        'branch_employee' => 'Branch Employee',
                        'public_customer' => 'Public Customer',
                    ])
                    ->default('branch_employee')
                    ->required(),
                Toggle::make('active')
                    ->default(true),
            ]);
    }
}
