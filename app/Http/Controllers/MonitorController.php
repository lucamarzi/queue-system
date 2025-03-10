<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class MonitorController extends Controller
{
    /**
     * Monitor per le reception
     */
    public function receptionMonitor(): View
    {
        // Otteniamo tutti i servizi di reception
        $receptionServices = Service::where('is_reception', true)->get();
        
        if ($receptionServices->isEmpty()) {
            return view('monitor.error', [
                'message' => 'Nessun servizio reception configurato!'
            ]);
        }
        
        return view('monitor.reception', [
            'receptionServices' => $receptionServices
        ]);
    }
    
    /**
     * Monitor per i servizi non-reception
     */
    public function servicesMonitor(): View
    {
        $services = Service::where('is_reception', false)->get();
        
        return view('monitor.services', [
            'services' => $services
        ]);
    }
    
    /**
     * API per aggiornare lo stato del monitor
     */
    public function getMonitorData(Request $request)
    {
        // Verifica se la richiesta è per le reception o per tutti i servizi
        $isReception = $request->boolean('is_reception', false);
        
        if ($isReception) {
            $receptionServices = Service::where('is_reception', true)->get();
            
            if ($receptionServices->isEmpty()) {
                return response()->json(['error' => 'Nessun servizio reception configurato'], 404);
            }
            
            // Otteniamo tutti gli ID dei servizi di reception
            $receptionServiceIds = $receptionServices->pluck('id')->toArray();
            
            Log::info('Reception service IDs', ['ids' => $receptionServiceIds]);
            
            // Recupera tutti i ticket chiamati per tutte le reception
            $calledTickets = Ticket::whereIn('current_service_id', $receptionServiceIds)
                ->whereIn('status', [Ticket::STATUS_CALLED],)
                ->with(['logs' => function($query) {
                    $query->whereIn('status_to', [Ticket::STATUS_CALLED])
                        ->with('station')
                        ->orderBy('created_at', 'desc')
                        ->limit(1);
                }, 'currentService'])
                ->orderBy('first_called_at', 'desc')
                ->take(20) // Aumentato il numero di ticket da mostrare perché ci sono più reception
                ->get();
            
            Log::info('Called tickets count', ['count' => $calledTickets->count()]);
            
            // Trasforma i dati per il frontend
            $tickets = $calledTickets->map(function($ticket) {
                // Prendi il log più recente per ottenere la stazione
                $latestLog = $ticket->logs->first();
                $stationName = $latestLog && $latestLog->station ? $latestLog->station->name : 'N/D';
                $serviceName = $ticket->currentService ? $ticket->currentService->name : 'N/D';
                
                return [
                    'number' => $ticket->ticket_number,
                    'station' => $stationName,
                    'service' => $serviceName,
                    'status' => $ticket->status,
                    'called_at' => $ticket->first_called_at ? $ticket->first_called_at->format('H:i:s') : null,
                ];
            });
            
            // Debug info
            Log::info('Reception Monitor Data', [
                'services' => $receptionServices->pluck('name')->toArray(),
                'tickets_found' => $calledTickets->count(),
                'ticket_data' => $tickets->take(5)->toArray() // Limitato a 5 per il log
            ]);
                
            return response()->json([
                'receptionServices' => $receptionServices->pluck('name')->toArray(),
                'tickets' => $tickets
            ]);
        } else {
            $services = Service::where('is_reception', false)->get();
            $result = [];
            
            foreach ($services as $service) {
                $calledTickets = Ticket::where('current_service_id', $service->id)
                    ->whereIn('status', [Ticket::STATUS_CALLED])
                    ->with(['logs' => function($query) {
                        $query->whereIn('status_to', [Ticket::STATUS_CALLED])
                            ->with('station')
                            ->orderBy('created_at', 'desc')
                            ->limit(1);
                    }])
                    ->orderBy('first_called_at', 'desc')
                    ->take(5)
                    ->get();
                
                $tickets = $calledTickets->map(function($ticket) {
                    $latestLog = $ticket->logs->first();
                    $stationName = $latestLog && $latestLog->station ? $latestLog->station->name : 'N/D';
                    
                    return [
                        'number' => $ticket->ticket_number,
                        'station' => $stationName,
                        'status' => $ticket->status,
                        'called_at' => $ticket->first_called_at ? $ticket->first_called_at->format('H:i:s') : null,
                    ];
                });
                
                $result[] = [
                    'service' => $service->name,
                    'tickets' => $tickets
                ];
            }
            
            return response()->json($result);
        }
    }
}