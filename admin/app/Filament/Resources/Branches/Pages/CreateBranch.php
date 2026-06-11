<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Resources\Branches\BranchResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $company = Company::with('currentSubscription.subscriptionPackage')->find($data['company_id'] ?? null);

        if ($company && ! $company->canAddBranch()) {
            throw ValidationException::withMessages([
                'data.company_id' => 'This company has reached its subscription branch limit.',
            ]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
