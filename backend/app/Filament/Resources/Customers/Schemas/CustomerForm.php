<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                Select::make('country_code')
                    ->options([
                        '+968' => '+968 Oman',
                        '+971' => '+971 UAE',
                        '+966' => '+966 Saudi Arabia',
                        '+974' => '+974 Qatar',
                        '+973' => '+973 Bahrain',
                        '+965' => '+965 Kuwait',
                    ])
                    ->default('+968')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('country_code', $get('country_code')),
                    ),
                TextInput::make('email')
                    ->email(),
                Toggle::make('active')
                    ->default(true),
            ]);
    }
}
