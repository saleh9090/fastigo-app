<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ExpensesByMonthChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Expenses by Month';

    protected string $color = 'danger';

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        [$labels, $monthKeys] = $this->getLastTwelveMonths();

        $expenses = Expense::query()
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as month")
            ->selectRaw('SUM(amount) as total')
            ->where('expense_date', '>=', now()->startOfMonth()->subMonths(11)->toDateString())
            ->groupBy('month')
            ->pluck('total', 'month');

        return [
            'datasets' => [
                [
                    'label' => 'Expenses',
                    'data' => array_map(
                        fn (string $month): float => round((float) ($expenses[$month] ?? 0), 3),
                        $monthKeys,
                    ),
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function getLastTwelveMonths(): array
    {
        $months = collect(range(11, 0))
            ->map(fn (int $monthsAgo): Carbon => now()->startOfMonth()->subMonths($monthsAgo));

        return [
            $months->map(fn (Carbon $month): string => $month->format('M Y'))->all(),
            $months->map(fn (Carbon $month): string => $month->format('Y-m'))->all(),
        ];
    }
}
