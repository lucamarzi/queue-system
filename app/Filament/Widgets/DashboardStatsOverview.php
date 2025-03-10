<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

use App\Models\Service;
use App\Models\Station;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';
    
    protected function getStats(): array
    {
        // Totale ticket oggi
        $todayTickets = Ticket::whereDate('created_at', Carbon::today())->count();
        
        // Ticket in attesa
        $waitingTickets = Ticket::where('status', 'waiting')->count();
        
        // Postazioni attive
        $activeStations = Station::where('status', 'active')->count();
        
        // Tempo medio di attesa (solo per i ticket completati oggi)
        $avgWaitingTime = Ticket::whereDate('completed_at', Carbon::today())
            ->whereNotNull('first_called_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_called_at)) as avg_waiting_time'))
            ->first()
            ->avg_waiting_time ?? 0;
        
        return [
            Stat::make('Ticket oggi', $todayTickets)
                ->description('Ticket generati oggi')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            
            Stat::make('In attesa', $waitingTickets)
                ->description('Ticket in attesa di chiamata')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
            
            Stat::make('Postazioni attive', $activeStations)
                ->description('Postazioni in funzione')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('info'),
            
            Stat::make('Tempo medio attesa', round($avgWaitingTime) . ' min')
                ->description('Tempo medio di attesa oggi')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}