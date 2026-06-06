<?php

namespace App\Filament\Resources\SubscriptionPackages\Pages;

use App\Filament\Resources\SubscriptionPackages\SubscriptionPackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscriptionPackage extends CreateRecord
{
    protected static string $resource = SubscriptionPackageResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
