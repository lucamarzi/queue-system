<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use App\Models\Ticket;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget;

class ServiceStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.service-stats-widget';
    
    protected int|string|array $columnSpan = 'full';
    
    protected static ?int $sort = 2;
    
    public function getServiceStats()
    {
        $services = Service::all();
        $stats = [];
        
        foreach ($services as $service) {
            $waitingCount = Ticket::where('current_service_id', $service->id)
                ->where('status', 'waiting')
                ->count();
                
            $completedToday = Ticket::where('current_service_id', $service->id)
                ->whereDate('completed_at', Carbon::today())
                ->where('status', 'completed')
                ->count();
                
            $avgWaitTimeMinutes = Ticket::where('current_service_id', $service->id)
                ->whereDate('completed_at', Carbon::today())
                ->whereNotNull('first_called_at')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_called_at)) as avg_time'))
                ->first()
                ->avg_time ?? 0;
                
            $avgServiceTimeMinutes = Ticket::where('current_service_id', $service->id)
                ->whereDate('completed_at', Carbon::today())
                ->whereNotNull('first_called_at')
                ->whereNotNull('completed_at')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, first_called_at, completed_at)) as avg_time'))
                ->first()
                ->avg_time ?? 0;
                
            $stats[] = [
                'service' => $service,
                'waiting' => $waitingCount,
                'completed' => $completedToday,
                'avg_wait_time' => round($avgWaitTimeMinutes),
                'avg_service_time' => round($avgServiceTimeMinutes),
            ];
        }
        
        return $stats;
    }
}
