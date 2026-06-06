<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FastigoStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Companies', Company::count())
                ->icon(Heroicon::OutlinedBuildingOffice),
            Stat::make('Active Companies', Company::where('status', 'active')->count())
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('Total Branches', Branch::count())
                ->icon(Heroicon::OutlinedMapPin),
            Stat::make('Total Customers', Customer::count())
                ->icon(Heroicon::OutlinedUserGroup),
        ];
    }
}
