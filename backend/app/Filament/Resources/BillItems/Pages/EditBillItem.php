<?php

namespace App\Filament\Resources\BillItems\Pages;

use App\Filament\Resources\BillItems\BillItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBillItem extends EditRecord
{
    protected static string $resource = BillItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
