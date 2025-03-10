<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TicketsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Ticket giornalieri (ultimi 7 giorni)';
    
    protected function getData(): array
    {
        $data = [];
        $labels = [];
        
        // Ultimi 7 giorni
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d/m');
            
            $ticketsCount = Ticket::whereDate('created_at', $date)->count();
            $completedCount = Ticket::whereDate('completed_at', $date)->count();
            
            $data['ticket'][] = $ticketsCount;
            $data['completed'][] = $completedCount;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Ticket emessi',
                    'data' => $data['ticket'],
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#36A2EB',
                ],
                [
                    'label' => 'Ticket completati',
                    'data' => $data['completed'],
                    'backgroundColor' => '#4BC0C0',
                    'borderColor' => '#4BC0C0',
                ],
            ],
            'labels' => $labels,
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
}
