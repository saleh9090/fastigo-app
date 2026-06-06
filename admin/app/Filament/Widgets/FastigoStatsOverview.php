<?php

namespace App\Filament\Widgets;

use App\Models\Bill;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FastigoStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalSales = (float) Bill::sum('total_amount');
        $totalExpenses = (float) Expense::sum('amount');
        $netProfit = $totalSales - $totalExpenses;

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
            Stat::make('Total Bills', Bill::count())
                ->icon(Heroicon::OutlinedDocumentText),
            Stat::make('Total Sales', $this->formatMoney($totalSales))
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success'),
            Stat::make('Total Expenses', $this->formatMoney($totalExpenses))
                ->icon(Heroicon::OutlinedReceiptPercent)
                ->color('danger'),
            Stat::make('Net Profit', $this->formatMoney($netProfit))
                ->icon(Heroicon::OutlinedChartBar)
                ->color($netProfit >= 0 ? 'success' : 'danger'),
        ];
    }

    private function formatMoney(float $amount): string
    {
        return 'OMR ' . number_format($amount, 3);
    }
}
