<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Branches\BranchResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\CompanySubscriptions\CompanySubscriptionResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\SubscriptionPackages\SubscriptionPackageResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\FastigoStatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
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
                'primary' => [
                    50 => '#F6F4FE',
                    100 => '#EDE9FE',
                    200 => '#DFDAF3',
                    300 => '#C7BAFA',
                    400 => '#9C84F6',
                    500 => '#6A49F2',
                    600 => '#593DD4',
                    700 => '#4630A6',
                    800 => '#32236F',
                    900 => '#1D1445',
                    950 => '#120C2E',
                ],
                'gray' => [
                    50 => '#FFFFFF',
                    100 => '#F6F4FE',
                    200 => '#DFDAF3',
                    300 => '#D4D2DD',
                    400 => '#9995B1',
                    500 => '#73708E',
                    600 => '#3E3F66',
                    700 => '#32236F',
                    800 => '#1D1445',
                    900 => '#120C2E',
                    950 => '#0B071C',
                ],
            ])
            ->resources([
                BranchResource::class,
                CompanyResource::class,
                CompanySubscriptionResource::class,
                CustomerResource::class,
                SubscriptionPackageResource::class,
                UserResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                FastigoStatsOverview::class,
                FilamentInfoWidget::class,
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
