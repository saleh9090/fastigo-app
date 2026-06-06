<?php

namespace App\Filament\Resources\BillItems;

use App\Filament\Resources\BillItems\Pages\CreateBillItem;
use App\Filament\Resources\BillItems\Pages\EditBillItem;
use App\Filament\Resources\BillItems\Pages\ListBillItems;
use App\Filament\Resources\BillItems\Schemas\BillItemForm;
use App\Filament\Resources\BillItems\Tables\BillItemsTable;
use App\Models\BillItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BillItemResource extends Resource
{
    protected static ?string $model = BillItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return BillItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillItems::route('/'),
            'create' => CreateBillItem::route('/create'),
            'edit' => EditBillItem::route('/{record}/edit'),
        ];
    }
}
