<?php

namespace App\Filament\Resources\CompanySubscriptions\Pages;

use App\Filament\Resources\CompanySubscriptions\CompanySubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanySubscription extends CreateRecord
{
    protected static string $resource = CompanySubscriptionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', [
            'company_id' => $this->record->company_id,
        ]);
    }
}
