<?php

namespace App\Filament\Resources\Branches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->when(
                    request()->integer('company_id'),
                    fn (Builder $query, int $companyId): Builder => $query->where('company_id', $companyId),
                ))
            ->columns([
                TextColumn::make('company_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->formatStateUsing(fn ($state, $record): string => $record->company?->arabic_name
                        ? "{$state} ({$record->company->arabic_name})"
                        : (string) $state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Branch')
                    ->formatStateUsing(fn ($state, $record): string => $record->arabic_name
                        ? "{$state} ({$record->arabic_name})"
                        : (string) $state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
