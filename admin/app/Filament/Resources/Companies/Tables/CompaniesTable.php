<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Filament\Resources\Branches\BranchResource;
use App\Filament\Resources\CompanySubscriptions\CompanySubscriptionResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Company')
                    ->formatStateUsing(fn ($state, $record): string => $record->arabic_name
                        ? "{$state} ({$record->arabic_name})"
                        : (string) $state)
                    ->searchable(),
                TextColumn::make('commercial_registration')
                    ->searchable(),
                TextColumn::make('contact_person')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('currentSubscription.subscriptionPackage.name')
                    ->label('Package')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('currentSubscription.subscription_start')
                    ->label('Subscription start')
                    ->date()
                    ->sortable(),
                TextColumn::make('currentSubscription.subscription_end')
                    ->label('Subscription end')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
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
                Action::make('branches')
                    ->label('Branches')
                    ->icon(Heroicon::OutlinedBuildingStorefront)
                    ->url(fn ($record): string => BranchResource::getUrl('index', [
                        'company_id' => $record->getKey(),
                    ])),
                Action::make('subscriptions')
                    ->label('Subscriptions')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->url(fn ($record): string => CompanySubscriptionResource::getUrl('index', [
                        'company_id' => $record->getKey(),
                    ])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
