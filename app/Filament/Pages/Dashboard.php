<?php

namespace App\Filament\Pages;


use App\Models\Service;
use App\Models\Station;
use App\Models\Ticket;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\ServiceStatsWidget;
use App\Filament\Widgets\TicketsChartWidget;
use App\Filament\Widgets\WaitingTimesWidget;



class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.dashboard';
    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            DashboardStatsOverview::class,
            ServiceStatsWidget::class,
            TicketsChartWidget::class,
            WaitingTimesWidget::class,
        ];
    }
}
