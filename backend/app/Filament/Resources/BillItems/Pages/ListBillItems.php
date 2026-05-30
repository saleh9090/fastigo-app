<?php

namespace App\Filament\Resources\BillItems\Pages;

use App\Filament\Resources\BillItems\BillItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillItems extends ListRecords
{
    protected static string $resource = BillItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
