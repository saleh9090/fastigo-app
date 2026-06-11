<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->arabic_name
                        ? "{$record->name} ({$record->arabic_name})"
                        : $record->name)
                    ->searchable()
                    ->preload()
                    ->default(fn () => request()->integer('company_id') ?: null)
                    ->disabled(fn () => request()->filled('company_id'))
                    ->dehydrated()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('arabic_name')
                    ->label('Branch name (Arabic)'),
                TextInput::make('phone')
                    ->tel(),
                Textarea::make('address')
                    ->columnSpanFull(),
            ]);
    }
}
