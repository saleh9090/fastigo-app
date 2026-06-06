<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Company;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validateCompanyEmployeeLimit($data);

        return $data;
    }

    private function validateCompanyEmployeeLimit(array $data): void
    {
        if (! in_array($data['role'] ?? null, ['company_manager', 'branch_employee'], true)) {
            return;
        }

        $company = Company::with('subscriptionPackage')->find($data['company_id'] ?? null);

        if (! $company) {
            throw ValidationException::withMessages([
                'data.company_id' => 'Company is required for company managers and branch employees.',
            ]);
        }

        if (! $company->canAddEmployee($this->record->id)) {
            throw ValidationException::withMessages([
                'data.company_id' => 'This company has reached its subscription employee limit.',
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
