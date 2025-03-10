<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Station;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\DailyCounter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketService
{
    /**
     * Crea un nuovo ticket
     */
    public function createTicket(string $type, int $serviceId, int $currentServiceId = null, string $phoneNumber = null): Ticket
    {
        // Ottieni il servizio per il prefisso
        $service = Service::findOrFail($serviceId);
        $prefix = $service->ticket_prefix;
        
        // Genera il numero progressivo giornaliero
        $ticketNumber = $this->generateDailyNumber($prefix);
        
        // Se non è specificato il current_service_id, usa il service_id
        if (!$currentServiceId) {
            $currentServiceId = $serviceId;
        }
        
        // Crea il ticket
        $ticket = new Ticket([
            'ticket_number' => $ticketNumber,
            'type' => $type,
            'status' => Ticket::STATUS_WAITING,
            'service_id' => $serviceId,
            'current_service_id' => $currentServiceId,
            'phone_number' => $phoneNumber,
        ]);
        
        $ticket->save();
        
        // Crea il log
        $this->createTicketLog(
            ticket: $ticket,
            statusFrom: null,
            statusTo: Ticket::STATUS_WAITING
        );
        
        // Incrementa il contatore di attesa del servizio
        $currentService = Service::findOrFail($currentServiceId);
        $currentService->increment('waiting_count');
        
        return $ticket;
    }
    
    /**
     * Genera un numero di ticket progressivo per la giornata corrente
     */
    private function generateDailyNumber(string $prefix): string 
    {
        $today = Carbon::today()->toDateString();
        
        // Blocca la riga per evitare race condition
        DB::beginTransaction();
        
        try {
            // Cerca o crea il contatore per la data odierna
            $counter = DailyCounter::firstOrCreate(
                ['date' => $today],
                ['counter' => 0]
            );
            
            // Incrementa il contatore
            $counter->increment('counter');
            
            // Formatta il numero con padding di zeri (es. A001)
            $ticketNumber = $prefix . str_pad($counter->counter, 3, '0', STR_PAD_LEFT);
            
            DB::commit();
            
            return $ticketNumber;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Assegna il prossimo ticket alla postazione
     */
    public function assignNextTicketToStation(Station $station): ?Ticket
    {
        // Verifica che la postazione sia attiva
        if (!$station->isActive()) {
            return null;
        }
        
        // Ottieni il servizio
        $service = $station->service;
        if (!$service) {
            return null;
        }
        
        DB::beginTransaction();
        
        try {
            // Ottieni il prossimo ticket in attesa
            $nextTicket = $service->getNextTicket();
            
            if (!$nextTicket) {
                DB::commit();
                return null;
            }
            
            // Aggiorna lo stato del ticket a CALLED e imposta la data della prima chiamata
            $oldStatus = $nextTicket->status;
            $nextTicket->status = Ticket::STATUS_CALLED;
            if (!$nextTicket->first_called_at) {
                $nextTicket->first_called_at = now();
            }
            $nextTicket->save();
            
            // Decrementa il contatore di attesa del servizio
            $service->decrement('waiting_count');
            
            // Crea il log
            $this->createTicketLog(
                ticket: $nextTicket,
                statusFrom: $oldStatus,
                statusTo: Ticket::STATUS_CALLED,
                station: $station
            );
            
            DB::commit();
            
            return $nextTicket;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Mette in servizio un ticket (inizia la gestione)
     */
    public function startTicketService(Ticket $ticket, Station $station): bool
    {
        if ($ticket->status !== Ticket::STATUS_CALLED) {
            return false;
        }
        
        DB::beginTransaction();
        
        try {
            // Aggiorna lo stato del ticket
            $oldStatus = $ticket->status;
            $ticket->status = Ticket::STATUS_IN_PROGRESS;
            $ticket->save();

            // Aggiorna lo stato della postazione a BUSY
            $station->status = Station::STATUS_BUSY;
            $station->save();
            
            // Crea il log
            $this->createTicketLog(
                ticket: $ticket,
                statusFrom: $oldStatus,
                statusTo: Ticket::STATUS_IN_PROGRESS,
                station: $station
            );
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
 
     /**
     * Cambia lo stato di un ticket in "in_progress" quando la postazione diventa occupata
     */
    public function setTicketInProgressForStation(Station $station): bool
    {
        // Cerca il ticket chiamato dalla postazione
        $ticket = Ticket::where('status', Ticket::STATUS_CALLED)
            ->whereHas('logs', function ($query) use ($station) {
                $query->where('station_id', $station->id)
                      ->where('status_to', Ticket::STATUS_CALLED)
                      ->orderBy('created_at', 'desc');
            })
            ->first();
            
        if (!$ticket) {
            return false;
        }
        
        // Usa il metodo startTicketService per cambiare lo stato
        return $this->startTicketService($ticket, $station);
    }   

    /**
     * Completa un ticket
     */
    public function completeTicket(Ticket $ticket, Station $station): bool
    {
        if ($ticket->status !== Ticket::STATUS_IN_PROGRESS && $ticket->status !== Ticket::STATUS_CALLED) {
            return false;
        }
        
        DB::beginTransaction();
        
        try {
            // Aggiorna lo stato del ticket
            $oldStatus = $ticket->status;
            $ticket->status = Ticket::STATUS_COMPLETED;
            $ticket->completed_at = now();
            $ticket->save();

            // Reimposta lo stato della postazione ad ACTIVE
            $station->status = Station::STATUS_ACTIVE;
            $station->save();
            
            // Crea il log
            $this->createTicketLog(
                ticket: $ticket,
                statusFrom: $oldStatus,
                statusTo: Ticket::STATUS_COMPLETED,
                station: $station
            );
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Trasferisce un ticket ad un altro servizio
     */
    public function transferTicket(Ticket $ticket, Service $service, Station $station): bool
    {
        if ($ticket->status !== Ticket::STATUS_IN_PROGRESS && $ticket->status !== Ticket::STATUS_CALLED) {
            return false;
        }
        
        // Non permettere il trasferimento allo stesso servizio
        if ($ticket->current_service_id === $service->id) {
            return false;
        }
        
        DB::beginTransaction();
        
        try {
            // Se il ticket è di tipo INFO, completalo invece di trasferirlo
            if ($ticket->type === Ticket::TYPE_INFO) {
                $this->completeTicket($ticket, $station);
                DB::commit();
                return true;
            }
            
            // Salva il vecchio stato e servizio
            $oldStatus = $ticket->status;
            $oldServiceId = $ticket->current_service_id;
            
            // Aggiorna il servizio corrente del ticket e lo stato a WAITING
            $ticket->current_service_id = $service->id;
            $ticket->status = Ticket::STATUS_WAITING;
            $ticket->save();

            // Reimposta lo stato della postazione ad ACTIVE
            $station->status = Station::STATUS_ACTIVE;
            $station->save();
            
            // Crea il log
            $this->createTicketLog(
                ticket: $ticket,
                statusFrom: $oldStatus,
                statusTo: Ticket::STATUS_WAITING,
                station: $station
            );
            
            // Incrementa il contatore di attesa del nuovo servizio
            $service->increment('waiting_count');
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Segna un ticket come abbandonato
     */
    public function abandonTicket(Ticket $ticket, Station $station): bool
    {
        if ($ticket->status !== Ticket::STATUS_CALLED && $ticket->status !== Ticket::STATUS_IN_PROGRESS) {
            return false;
        }
        
        DB::beginTransaction();
        
        try {
            // Aggiorna lo stato del ticket
            $oldStatus = $ticket->status;
            $ticket->status = Ticket::STATUS_ABANDONED;
            $ticket->completed_at = now(); // Impostiamo completed_at per conteggiarlo nelle statistiche
            $ticket->save();

            // Reimposta lo stato della postazione ad ACTIVE
            $station->status = Station::STATUS_ACTIVE;
            $station->save();
            
            // Se il ticket era in attesa, decrementa il contatore di attesa del servizio
            if ($oldStatus === Ticket::STATUS_WAITING) {
                $service = Service::findOrFail($ticket->current_service_id);
                $service->decrement('waiting_count');
            }
            
            // Crea il log
            $this->createTicketLog(
                ticket: $ticket,
                statusFrom: $oldStatus,
                statusTo: Ticket::STATUS_ABANDONED,
                station: $station
            );
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Richiama un ticket già chiamato ma che è stato saltato
     */
    public function recallTicket(Ticket $ticket, Station $station): bool
    {
        if ($ticket->status !== Ticket::STATUS_CALLED) {
            return false;
        }
        
        DB::beginTransaction();
        
        try {
            // Non cambiamo lo stato, rimane CALLED, aggiorniamo solo il log
            $this->createTicketLog(
                ticket: $ticket,
                statusFrom: Ticket::STATUS_CALLED,
                statusTo: Ticket::STATUS_CALLED, // Stesso stato ma nuovo log
                station: $station
            );
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Ottiene i ticket in attesa per un servizio
     */
    public function getWaitingTickets(Service $service)
    {
        return $service->currentTickets()
            ->where('status', Ticket::STATUS_WAITING)
            ->orderBy('created_at')
            ->get();
    }
    
    /**
     * Ottiene le statistiche per un servizio in una data specificata
     */
    public function getServiceStats(Service $service, $date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();
        
        // Ticket totali creati nella data specificata
        $totalCreated = Ticket::where('current_service_id', $service->id)
            ->whereDate('created_at', $date)
            ->count();
        
        // Ticket completati nella data specificata
        $completed = Ticket::where('current_service_id', $service->id)
            ->whereDate('completed_at', $date)
            ->whereIn('status', [Ticket::STATUS_COMPLETED, Ticket::STATUS_ABANDONED])
            ->count();
        
        // Tempo medio di attesa
        $avgWaitTime = Ticket::where('current_service_id', $service->id)
            ->whereDate('completed_at', $date)
            ->whereNotNull('first_called_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_called_at)) as avg_time'))
            ->first()
            ->avg_time ?? 0;
        
        // Tempo medio di servizio
        $avgServiceTime = Ticket::where('current_service_id', $service->id)
            ->whereDate('completed_at', $date)
            ->whereNotNull('first_called_at')
            ->whereNotNull('completed_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, first_called_at, completed_at)) as avg_time'))
            ->first()
            ->avg_time ?? 0;
        
        // Ticket abbandonati
        $abandoned = Ticket::where('current_service_id', $service->id)
            ->whereDate('completed_at', $date)
            ->where('status', Ticket::STATUS_ABANDONED)
            ->count();
        
        return [
            'service' => $service,
            'total_created' => $totalCreated,
            'completed' => $completed,
            'waiting' => $service->waiting_count,
            'avg_wait_time' => round($avgWaitTime),
            'avg_service_time' => round($avgServiceTime),
            'abandoned' => $abandoned,
            'abandoned_percent' => $totalCreated > 0 ? round(($abandoned / $totalCreated) * 100, 2) : 0,
        ];
    }
    
    /**
     * Crea un log per il ticket
     */
    private function createTicketLog(
        Ticket $ticket,
        ?string $statusFrom,
        string $statusTo,
        ?Station $station = null
    ): TicketLog {
        $log = new TicketLog([
            'ticket_id' => $ticket->id,
            'status_from' => $statusFrom ?: 'new',
            'status_to' => $statusTo,
            'station_id' => $station ? $station->id : null,
            'user_id' => null, // Non c'è un utente autenticato per le postazioni
        ]);
        
        $log->save();
        
        return $log;
    }
    
    /**
     * Invia notifiche SMS per il ticket (se il numero di telefono è presente)
     */
    public function sendSmsNotification(Ticket $ticket): bool
    {
        if (!$ticket->phone_number) {
            return false;
        }
        
        // Qui implementeresti l'integrazione con il servizio SMS
        // Per ora è un placeholder
        $service = Service::find($ticket->current_service_id);
        $serviceName = $service ? $service->name : 'servizio';
        
        // Esempio di log dell'SMS
        Log::info("SMS inviato al numero {$ticket->phone_number} per il ticket {$ticket->ticket_number} del servizio {$serviceName}");
        
        return true; // Simuliamo successo
    }
}