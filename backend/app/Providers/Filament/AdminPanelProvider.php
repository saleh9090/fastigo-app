<?php

namespace App\Providers\Filament;

use App\Filament\Resources\BillItems\BillItemResource;
use App\Filament\Resources\Bills\BillResource;
use App\Filament\Resources\Branches\BranchResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\ExpensesByMonthChart;
use App\Filament\Widgets\FastigoStatsOverview;
use App\Filament\Widgets\SalesByMonthChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                BillItemResource::class,
                BillResource::class,
                BranchResource::class,
                CompanyResource::class,
                CustomerResource::class,
                ExpenseCategoryResource::class,
                ExpenseResource::class,
                ProductCategoryResource::class,
                ProductResource::class,
                UserResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                ExpensesByMonthChart::class,
                FastigoStatsOverview::class,
                FilamentInfoWidget::class,
                SalesByMonthChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
