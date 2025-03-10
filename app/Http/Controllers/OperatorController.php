<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Station;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperatorController extends Controller
{
    protected TicketService $ticketService;
    
    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }
    
    /**
     * Dashboard operatore
     */
    public function dashboard(Request $request): View
    {
        // Ottiene la postazione dalla request (impostata dal middleware)
        $station = $request->attributes->get('station');
        $service = $station->service;
        $currentTicket = null;
        $waitingTickets = [];
        
        // Se la postazione è attiva o occupata, ottieni il ticket chiamato
        if ($station->isActive()) {
            $currentTicket = Ticket::where('status', Ticket::STATUS_CALLED)
                ->whereHas('logs', function ($query) use ($station) {
                    $query->where('station_id', $station->id)
                          ->where('status_to', Ticket::STATUS_CALLED)
                          ->orderBy('created_at', 'desc');
                })
                ->first();
                
            // Se non c'è un ticket in corso, cerca un ticket chiamato
            if (!$currentTicket) {
                $currentTicket = Ticket::where('status', Ticket::STATUS_CALLED)
                    ->whereHas('logs', function ($query) use ($station) {
                        $query->where('station_id', $station->id)
                              ->where('status_to', Ticket::STATUS_CALLED)
                              ->orderBy('created_at', 'desc');
                    })
                    ->first();
            }
        }

        //Se la postazione è occupata ottinei il ticket corrente
        if ($station->isBusy()) {
            $currentTicket = Ticket::where('status', Ticket::STATUS_IN_PROGRESS)
                ->whereHas('logs', function ($query) use ($station) {
                    $query->where('station_id', $station->id)
                          ->where('status_to', Ticket::STATUS_IN_PROGRESS)
                          ->orderBy('created_at', 'desc');
                })
                ->first();
        }
        
        // Ottieni i ticket in attesa per il servizio
        if ($service) {
            $waitingTickets = $this->ticketService->getWaitingTickets($service);
        }

        // Ottieni tutti i servizi per il trasferimento (sia per reception che per altri servizi)
        $transferServices = Service::where('id', '!=', $service->id)->get();
        
        return view('operator.dashboard', [
            'station' => $station,
            'service' => $service,
            'currentTicket' => $currentTicket,
            'waitingTickets' => $waitingTickets,
            'transferServices' => $transferServices,
        ]);
    }
    
    /**
     * Imposta lo stato della postazione
     */
    public function setStationStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:active,busy,paused,closed',
        ]);
        
        // Ottiene la postazione dalla request (impostata dal middleware)
        $station = $request->attributes->get('station');
        
        if (!$station) {
            return response()->json(['error' => 'Nessuna postazione associata a questo operatore'], 404);
        }
        
        if ($station->is_locked) {
            return response()->json(['error' => 'La postazione è bloccata, contatta l\'amministratore'], 403);
        }
        
        $oldStatus = $station->status;
        $newStatus = $request->input('status');
        
        // Aggiorna lo stato della postazione
        $station->status = $newStatus;
        $station->save();
        
        // Se la postazione diventa attiva, assegna automaticamente il prossimo ticket
        if ($newStatus === Station::STATUS_ACTIVE && $oldStatus !== Station::STATUS_ACTIVE) {
            $this->ticketService->assignNextTicketToStation($station);
        }

        // Se la postazione diventa occupata, imposta il ticket corrente come "in_progress"
        if ($newStatus === Station::STATUS_BUSY && $oldStatus !== Station::STATUS_BUSY) {
            $this->setCurrentTicketInProgress($station);
        }
        
        return response()->json([
            'success' => true, 
            'message' => 'Stato postazione aggiornato',
            'status' => $newStatus
        ]);
    }
    
    /**
     * Imposta il ticket corrente come "in_progress"
     */
    private function setCurrentTicketInProgress(Station $station)
    {
        // Trova il ticket chiamato per questa postazione
        $currentTicket = Ticket::where('status', Ticket::STATUS_CALLED)
            ->whereHas('logs', function ($query) use ($station) {
                $query->where('station_id', $station->id)
                    ->where('status_to', Ticket::STATUS_CALLED)
                    ->orderBy('created_at', 'desc');
            })
            ->first();
            
        if ($currentTicket) {
            $this->ticketService->startTicketService($currentTicket, $station);
        }
    }

    /**
     * Chiama il prossimo ticket
     */
    public function callNextTicket(Request $request)
    {
        // Ottiene la postazione dalla request (impostata dal middleware)
        $station = $request->attributes->get('station');
        
        if (!$station) {
            return response()->json(['error' => 'Nessuna postazione associata a questo operatore'], 404);
        }
        
        if (!$station->isActive()) {
            return response()->json(['error' => 'La postazione non è attiva. Attiva la postazione prima di chiamare un ticket.'], 400);
        }
        
        $ticket = $this->ticketService->assignNextTicketToStation($station);
        
        if (!$ticket) {
            return response()->json(['error' => 'Nessun ticket in attesa'], 404);
        }
        
        return response()->json([
            'success' => true,
            'ticket' => [
                'id' => $ticket->id,
                'number' => $ticket->ticket_number,
                'type' => $ticket->type,
                'created_at' => $ticket->created_at->diffForHumans(),
            ]
        ]);
    }
    
    /**
     * Inizia a servire un ticket (cambio stato da CALLED a IN_PROGRESS)
     */
    public function startTicket(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
        ]);
        
        // Ottiene la postazione dalla request
        $station = $request->attributes->get('station');
        
        if (!$station) {
            return response()->json(['error' => 'Nessuna postazione associata a questo operatore'], 404);
        }
        
        $ticketId = $request->input('ticket_id');
        
        $ticket = Ticket::findOrFail($ticketId);
        
        // Verifica che il ticket sia stato chiamato da questa postazione
        $latestLog = $ticket->logs()
            ->where('station_id', $station->id)
            ->where('status_to', Ticket::STATUS_CALLED)
            ->latest()
            ->first();
            
        if (!$latestLog) {
            return response()->json(['error' => 'Questo ticket non è stato chiamato da questa postazione'], 403);
        }
        
        $result = $this->ticketService->startTicketService($ticket, $station);
        
        if (!$result) {
            return response()->json(['error' => 'Impossibile aggiornare lo stato del ticket'], 500);
        }

        // Imposta la postazione come occupata
        $station->status = Station::STATUS_BUSY;
        $station->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Ticket in gestione'
        ]);
    }
    
    /**
     * Completa un ticket
     */
    public function completeTicket(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
        ]);
        
        // Ottiene la postazione dalla request
        $station = $request->attributes->get('station');
        
        if (!$station) {
            return response()->json(['error' => 'Nessuna postazione associata a questo operatore'], 404);
        }
        
        $ticketId = $request->input('ticket_id');
        
        $ticket = Ticket::findOrFail($ticketId);
        
        // Verifica che il ticket sia effettivamente in gestione da questa postazione
        $latestLog = $ticket->logs()
            ->where('station_id', $station->id)
            ->whereIn('status_to', [Ticket::STATUS_CALLED, Ticket::STATUS_IN_PROGRESS])
            ->latest()
            ->first();
            
        if (!$latestLog) {
            return response()->json(['error' => 'Questo ticket non è in gestione da questa postazione'], 403);
        }
        
        $result = $this->ticketService->completeTicket($ticket, $station);
        
        if (!$result) {
            return response()->json(['error' => 'Impossibile completare il ticket'], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Ticket completato con successo'
        ]);
    }
    
    /**
     * Trasferisce un ticket ad un altro servizio (solo per reception)
     */
    public function transferTicket(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'service_id' => 'required|exists:services,id',
        ]);
        
        // Ottiene la postazione dalla request
        $station = $request->attributes->get('station');
        
        if (!$station) {
            return response()->json(['error' => 'Nessuna postazione associata a questo operatore'], 404);
        }
        
        // Verifica che la postazione sia una reception
        if (!$station->service->is_reception) {
            return response()->json(['error' => 'Solo le postazioni di reception possono trasferire i ticket'], 403);
        }
        
        $ticketId = $request->input('ticket_id');
        $serviceId = $request->input('service_id');
        
        $ticket = Ticket::findOrFail($ticketId);
        $service = Service::findOrFail($serviceId);
        
        
        // Verifica che il ticket sia effettivamente in gestione da questa postazione
        $latestLog = $ticket->logs()
            ->where('station_id', $station->id)
            ->whereIn('status_to', [Ticket::STATUS_CALLED, Ticket::STATUS_IN_PROGRESS])
            ->latest()
            ->first();
            
        if (!$latestLog) {
            return response()->json(['error' => 'Questo ticket non è in gestione da questa postazione'], 403);
        }
        
        $result = $this->ticketService->transferTicket($ticket, $service, $station);
        
        if (!$result) {
            return response()->json(['error' => 'Impossibile trasferire il ticket'], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Ticket trasferito con successo'
        ]);
    }
    
    /**
     * Abbandona un ticket (cliente non si presenta)
     */
    public function abandonTicket(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
        ]);
        
        // Ottiene la postazione dalla request
        $station = $request->attributes->get('station');
        
        if (!$station) {
            return response()->json(['error' => 'Nessuna postazione associata a questo operatore'], 404);
        }
        
        $ticketId = $request->input('ticket_id');
        
        $ticket = Ticket::findOrFail($ticketId);
        
        // Verifica che il ticket sia effettivamente in gestione da questa postazione
        $latestLog = $ticket->logs()
            ->where('station_id', $station->id)
            ->whereIn('status_to', [Ticket::STATUS_CALLED, Ticket::STATUS_IN_PROGRESS])
            ->latest()
            ->first();
            
        if (!$latestLog) {
            return response()->json(['error' => 'Questo ticket non è in gestione da questa postazione'], 403);
        }
        
        $result = $this->ticketService->abandonTicket($ticket, $station);
        
        if (!$result) {
            return response()->json(['error' => 'Impossibile abbandonare il ticket'], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Ticket contrassegnato come abbandonato'
        ]);
    }

    public function checkWaitingTickets(Request $request)
    {
        // Ottiene la postazione dalla request (impostata dal middleware)
        $station = $request->attributes->get('station');
        
        if (!$station) {
            return response()->json(['error' => 'Nessuna postazione associata a questo operatore'], 404);
        }
        
        $service = $station->service;
        
        if (!$service) {
            return response()->json(['error' => 'Nessun servizio associato a questa postazione'], 404);
        }
        
        // Controlla se ci sono ticket in attesa
        $waitingTicketsCount = Ticket::where('current_service_id', $service->id)
            ->where('status', Ticket::STATUS_WAITING)
            ->count();
        
        return response()->json([
            'success' => true,
            'hasWaitingTickets' => $waitingTicketsCount > 0,
            'waitingCount' => $waitingTicketsCount
        ]);
    }

    /**
     * Restituisce la lista aggiornata dei ticket in attesa per il servizio della postazione
     */
    public function getWaitingTickets(Request $request)
    {
        // Ottiene la postazione dalla request (impostata dal middleware)
        $station = $request->attributes->get('station');
        
        if (!$station) {
            return response()->json(['error' => 'Nessuna postazione associata a questo operatore'], 404);
        }
        
        $service = $station->service;
        
        if (!$service) {
            return response()->json(['error' => 'Nessun servizio associato a questa postazione'], 404);
        }
        
        // Ottiene i ticket in attesa
        $waitingTickets = $this->ticketService->getWaitingTickets($service);
        
        // Formatta i ticket per il frontend
        $formattedTickets = $waitingTickets->map(function($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'type' => $ticket->type,
                'created_at_diff' => $ticket->created_at->diffForHumans(),
                'created_at' => $ticket->created_at->format('Y-m-d H:i:s')
            ];
        });
        
        return response()->json([
            'success' => true,
            'tickets' => $formattedTickets
        ]);
    }
}