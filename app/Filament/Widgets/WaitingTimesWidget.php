<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WaitingTimesWidget extends ChartWidget
{
    protected static ?string $heading = 'Tempi medi di attesa per servizio (oggi)';
    
    protected function getData(): array
    {
        $services = DB::table('services')
            ->join('tickets', 'services.id', '=', 'tickets.current_service_id')
            ->whereDate('tickets.completed_at', Carbon::today())
            ->whereNotNull('tickets.first_called_at')
            ->select(
                'services.name',
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, tickets.created_at, tickets.first_called_at)) as avg_waiting_time')
            )
            ->groupBy('services.name')
            ->get();
            
        $labels = $services->pluck('name')->toArray();
        $data = $services->pluck('avg_waiting_time')->map(function($value) {
            return round($value);
        })->toArray();
        
        return [
            'datasets' => [
                [
                    'label' => 'Tempo medio di attesa (minuti)',
                    'data' => $data,
                    'backgroundColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                        '#9966FF', '#FF9F40', '#8AC249', '#EA5545'
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }
    
    protected function getType(): string
    {
        return 'bar';
    }
}
