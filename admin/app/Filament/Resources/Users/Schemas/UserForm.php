<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('branch_id', null))
                    ->searchable()
                    ->preload(),
                Select::make('branch_id')
                    ->relationship(
                        name: 'branch',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                            ->where('company_id', $get('company_id') ?: 0),
                    )
                    ->searchable()
                    ->disabled(fn (Get $get): bool => blank($get('company_id')))
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
