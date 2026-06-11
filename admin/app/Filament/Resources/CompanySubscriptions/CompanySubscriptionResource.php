<?php

namespace App\Filament\Resources\CompanySubscriptions;

use App\Filament\Resources\CompanySubscriptions\Pages\CreateCompanySubscription;
use App\Filament\Resources\CompanySubscriptions\Pages\EditCompanySubscription;
use App\Filament\Resources\CompanySubscriptions\Pages\ListCompanySubscriptions;
use App\Filament\Resources\CompanySubscriptions\Schemas\CompanySubscriptionForm;
use App\Filament\Resources\CompanySubscriptions\Tables\CompanySubscriptionsTable;
use App\Models\CompanySubscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanySubscriptionResource extends Resource
{
    protected static ?string $model = CompanySubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return CompanySubscriptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanySubscriptionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanySubscriptions::route('/'),
            'create' => CreateCompanySubscription::route('/create'),
            'edit' => EditCompanySubscription::route('/{record}/edit'),
        ];
    }
}
