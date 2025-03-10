<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TotemController extends Controller
{
    protected TicketService $ticketService;
    
    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }
    
    /**
     * Mostra la pagina principale del totem
     * Mostra solo i servizi di reception
     */
    public function index(): View
    {
        // Ottieni solo i servizi di reception
        $receptionServices = Service::where('is_reception', true)->get();
        
        if ($receptionServices->isEmpty()) {
            // Se non ci sono servizi di reception configurati, mostra un errore
            return view('totem.error', [
                'message' => 'Nessun servizio di reception configurato. Contattare l\'amministratore.'
            ]);
        }
        
        return view('totem.index', [
            'receptionServices' => $receptionServices
        ]);
    }
    
    /**
     * Genera un nuovo ticket per il servizio di reception selezionato
     */
    public function createTicket(Request $request)
    {
        $request->validate(['service_id' => 'required|exists:services,id']);
        
        $serviceId = $request->input('service_id');
        
        // Verifica che il servizio sia effettivamente di reception
        $service = Service::findOrFail($serviceId);
        if (!$service->is_reception) {
            return redirect()->back()->with('error', 'Servizio non valido.');
        }
        
        // Crea il ticket per il servizio di reception
        $ticket = $this->ticketService->createTicket(
            type: Ticket::TYPE_SERVICE, // Tutti i ticket sono di tipo servizio
            serviceId: $serviceId,
            currentServiceId: $serviceId
        );
        
        return view('totem.ticket', compact('ticket'));
    }
    
    /**
     * Genera un nuovo ticket per un servizio
     */
    public function createServiceTicket(Request $request)
    {
        $request->validate(['service_id' => 'required|exists:services,id']);
        
        $serviceId = $request->input('service_id');
        $infoService = Service::where('is_reception', true)->first();
        
        if (!$infoService) {
            return redirect()->back()->with('error', 'Servizio reception non configurato!');
        }
        
        $ticket = $this->ticketService->createTicket(
            type: Ticket::TYPE_SERVICE,
            serviceId: $serviceId,
            currentServiceId: $infoService->id
        );
        
        return view('totem.ticket', compact('ticket'));
    }
}
