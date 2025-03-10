<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CloseOpenTickets extends Command
{
    /**
     * Il nome e la firma del comando della console.
     *
     * @var string
     */
    protected $signature = 'tickets:close-open {--force : Forza la chiusura indipendentemente dall\'orario}';

    /**
     * La descrizione del comando della console.
     *
     * @var string
     */
    protected $description = 'Chiude tutti i ticket non completati (dopo le 20:00 o con flag --force)';

    /**
     * Esegui il comando della console.
     */
    public function handle()
    {
        $now = Carbon::now();
        $isAfter8PM = $now->hour >= 16;
        $forceClose = $this->option('force');
        
        if (!$isAfter8PM && !$forceClose) {
            $this->error('Non è ancora l\'orario di chiusura (20:00). Usa il flag --force per forzare la chiusura.');
            return Command::FAILURE;
        }
        
        // Trova tutti i ticket che non sono stati completati o abbandonati
        $openTickets = Ticket::whereNotIn('status', [Ticket::STATUS_COMPLETED, Ticket::STATUS_ABANDONED])
            ->whereDate('created_at', $now->toDateString())
            ->get();
        
        $count = $openTickets->count();
        if ($count === 0) {
            $this->info('Nessun ticket aperto da chiudere.');
            return Command::SUCCESS;
        }
        
        $this->info("Trovati $count ticket aperti da chiudere.");
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        DB::beginTransaction();
        
        try {
            foreach ($openTickets as $ticket) {
                $oldStatus = $ticket->status;
                
                // Aggiorna lo stato del ticket
                $ticket->status = Ticket::STATUS_COMPLETED;
                $ticket->completed_at = $now;
                $ticket->save();
                
                // Crea un log per il ticket
                TicketLog::create([
                    'ticket_id' => $ticket->id,
                    'status_from' => $oldStatus,
                    'status_to' => Ticket::STATUS_COMPLETED,
                    'station_id' => null, // Nessuna stazione associata (chiusura automatica)
                ]);
                
                $bar->advance();
            }
            
            DB::commit();
            
            $bar->finish();
            $this->newLine();
            $this->info("$count ticket sono stati chiusi con successo.");
            
            Log::info("Chiusura automatica: $count ticket chiusi.", [
                'date' => $now->toDateTimeString(),
                'forced' => $forceClose
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->newLine();
            $this->error('Si è verificato un errore durante la chiusura dei ticket: ' . $e->getMessage());
            
            Log::error('Errore durante la chiusura automatica dei ticket', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
