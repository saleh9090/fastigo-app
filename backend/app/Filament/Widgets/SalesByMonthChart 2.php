<?php

namespace App\Filament\Widgets;

use App\Models\Bill;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesByMonthChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Sales by Month';

    protected string $color = 'success';

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        [$labels, $monthKeys] = $this->getLastTwelveMonths();

        $sales = Bill::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw('SUM(total_amount) as total')
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->groupBy('month')
            ->pluck('total', 'month');

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => array_map(
                        fn (string $month): float => round((float) ($sales[$month] ?? 0), 3),
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
