@extends('layouts.operator')

@section('content')
<div class="p-4">
    <div class="flex flex-col md:flex-row gap-4">
        <!-- Pannello sinistro - Stato postazione e azioni -->
        <div class="w-full md:w-1/3 bg-white rounded-lg shadow-md p-4">
            <h2 class="text-2xl font-bold mb-4">Postazione {{ $station->name }}</h2>
            <div class="mb-6">
                <p class="text-lg mb-2">Servizio: <span class="font-bold">{{ $service->name }}</span></p>
                <p class="text-lg mb-2">
                    Stato: 
                    <span id="station-status" class="inline-block px-2 py-1 rounded text-white font-bold
                        {{ $station->status === 'active' ? 'bg-green-500' : 
                           ($station->status === 'busy' ? 'bg-purple-500' : 
                            ($station->status === 'paused' ? 'bg-yellow-500' : 'bg-red-500')) }}">
                        {{ $station->status === 'active' ? 'Attiva' : 
                           ($station->status === 'busy' ? 'Occupata' : 
                            ($station->status === 'paused' ? 'In pausa' : 'Chiusa')) }}
                    </span>
                </p>
            </div>
            
            <div class="mb-8">
                <h3 class="text-lg font-bold mb-2">Cambia stato</h3>
                <div class="flex flex-wrap gap-2">
                     <button id="btn-active" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md"
                            {{ $station->status === 'active' ? 'disabled' : '' }}>
                        Attiva
                    </button>
                    <button id="btn-paused" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md"
                            {{ $station->status === 'paused' ? 'disabled' : '' }}>
                        Pausa
                    </button>
                </div>
            </div>
            
            <div class="mt-4">
                <form action="{{ route('station.close') }}" method="POST">
                    @csrf
                    <button id="btn-close-station" type="submit" class="w-full px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md">
                        Chiudi Postazione
                    </button>
                </form>
            </div>

        </div>
        
        <!-- Pannello centrale - Ticket corrente -->
        <div class="w-full md:w-1/3 bg-white rounded-lg shadow-md p-4">
            <h2 class="text-2xl font-bold mb-4">Ticket Corrente</h2>
            
            <div id="current-ticket-container">
                @if($currentTicket)
                    <div id="current-ticket" data-id="{{ $currentTicket->id }}" class="text-center p-6 bg-blue-50 rounded-lg">
                        <div class="text-6xl font-bold mb-6 text-blue-600">{{ $currentTicket->ticket_number }}</div>
                        <p class="text-lg mb-4">In attesa da: <span class="font-bold">{{ $currentTicket->created_at->diffForHumans() }}</span></p>
                        
                        <div class="flex gap-2 justify-center mt-6">
                             @if($currentTicket->status === 'called')
                            <button id="btn-start" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-md">
                                Inizia servizio
                            </button>
                            @endif
                            <button id="btn-complete" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md">
                                Completa
                            </button>
                            <button id="btn-abandon" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md">
                                Abbandona
                            </button>
                        </div>
                    </div>
                    <div class="mt-8">
                    <h3 class="text-lg font-bold mb-2">Trasferisci a servizio</h3>
                    <div id="transfer-options" class="mt-4 hidden">
                        <select id="transfer-service" class="w-full p-2 border rounded mb-2">
                            <option value="">Seleziona servizio...</option>
                            @foreach($transferServices as $transferService)
                                <option value="{{ $transferService->id }}">{{ $transferService->name }}</option>
                            @endforeach
                        </select>
                        <button id="btn-transfer" class="w-full px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-md">
                            Trasferisci
                        </button>
                    </div>
                </div>
                @else
                    <div class="text-center p-6 bg-gray-50 rounded-lg">
                        <p class="text-lg text-gray-500">Nessun ticket in gestione</p>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Pannello destro - Ticket in attesa -->
        <div class="w-full md:w-1/3 bg-white rounded-lg shadow-md p-4">
            <h2 class="text-2xl font-bold mb-4">In attesa ({{ count($waitingTickets) }})</h2>
            
            <div id="waiting-tickets-container" class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($waitingTickets as $ticket)
                    <div class="p-3 bg-gray-50 rounded flex justify-between items-center">
                        <div>
                            <span class="font-bold text-xl">{{ $ticket->ticket_number }}</span>
                            <p class="text-sm text-gray-500">{{ $ticket->created_at->diffForHumans() }}</p>
                        </div>
                        <div>
                            <span class="px-2 py-1 rounded text-xs {{ $ticket->type === 'info' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ $ticket->type === 'info' ? 'Info' : 'Servizio' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-gray-500">
                        Nessun ticket in attesa
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        //Costanti
        const startBtn = document.getElementById('btn-start');
        const completeBtn = document.getElementById('btn-complete');
        const abandonBtn = document.getElementById('btn-abandon');
        const currentTicket = document.getElementById('current-ticket');
        const transferOptions = document.getElementById('transfer-options');
        const stationStatus = document.getElementById('station-status').textContent.trim();


        // Gestione dei pulsanti di stato
        document.getElementById('btn-active').addEventListener('click', function() {
            changeStationStatus('active');
        });

        
        document.getElementById('btn-paused').addEventListener('click', function() {
            changeStationStatus('paused');
        });
        
        /*
        if(!currentTicket) {
            // Chiamata prossimo ticket
            document.getElementById('btn-call-next').addEventListener('click', function() {
                callNextTicket();
            });
        }
        */

        // Gestione ticket corrente

        
        if (startBtn) {
            startBtn.addEventListener('click', function() {
                startTicket();
            });
        }


        
        if (completeBtn) {
            completeBtn.addEventListener('click', function() {
                completeTicket();
            });
        }
        
        
        if (abandonBtn) {
            abandonBtn.addEventListener('click', function() {
                abandonTicket();
            });
        }
        
        // Gestione trasferimento

        
        if (currentTicket && transferOptions) {
            transferOptions.classList.remove('hidden');
            
            document.getElementById('btn-transfer').addEventListener('click', function() {
                const serviceId = document.getElementById('transfer-service').value;
                if (!serviceId) {
                    alert('Seleziona un servizio per il trasferimento');
                    return;
                }
                
                transferTicket(currentTicket.dataset.id, serviceId);
            });
        }
        
        // Funzioni per le chiamate API
        function changeStationStatus(status) {
            fetch('{{ route("operator.station-status") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStationStatus(status);
                    
                    // Aggiorna stato dei pulsanti
                    document.getElementById('btn-active').disabled = (status === 'active');
                    document.getElementById('btn-paused').disabled = (status === 'paused');
                    if(!currentTicket)
                        document.getElementById('btn-call-next').disabled = (status !== 'active');
                    
                    // Se la postazione diventa attiva
                    if (status === 'active') {
                        if (data.ticket) {
                            // Se c'è un ticket assegnato, aggiorna l'interfaccia
                            updateCurrentTicket(data.ticket);
                            // Non serve il polling perché abbiamo già un ticket
                        } else {
                            // Se non ci sono ticket assegnati, avvia il polling
                            startPolling();
                        }
                    } else {
                        // Se la postazione non è più attiva, ferma il polling
                       //stopPolling();
                    }
                    
                    // Se la postazione diventa occupata, aggiorna l'interfaccia
                    if (status === 'busy') {
                        // Se c'è un ticket corrente, aggiorna il suo stato a "In gestione"
                        const currentTicket = document.getElementById('current-ticket');
                        if (currentTicket) {
                            const statusEl = currentTicket.querySelector('p:nth-child(3) span');
                            if (statusEl) {
                                statusEl.textContent = 'In gestione';
                            }
                            
                            // Nascondi il pulsante "Inizia servizio" se presente
                            const startBtn = document.getElementById('btn-start');
                            if (startBtn) {
                                startBtn.style.display = 'none';
                            }
                        }
                    }
                    
                    // Se la postazione è chiusa, rimuovi il ticket corrente
                    if (status === 'closed') {
                        document.getElementById('current-ticket-container').innerHTML = `
                            <div class="text-center p-6 bg-gray-50 rounded-lg">
                                <p class="text-lg text-gray-500">Nessun ticket in gestione</p>
                            </div>
                        `;
                    }
                } else {
                    alert('Errore: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Si è verificato un errore durante l\'aggiornamento dello stato');
            });
        }
        
        function updateStationStatus(status) {
            const statusElement = document.getElementById('station-status');
            
            // Rimuovi classi precedenti
            statusElement.classList.remove('bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500');
            
            // Aggiungi nuova classe e testo
            if (status === 'active') {
                statusElement.classList.add('bg-green-500');
                statusElement.textContent = 'Attiva';
            } else if (status === 'busy') {
                statusElement.classList.add('bg-purple-500');
                statusElement.textContent = 'Occupata';
            } else if (status === 'paused') {
                statusElement.classList.add('bg-yellow-500');
                statusElement.textContent = 'In pausa';
            } else {
                statusElement.classList.add('bg-red-500');
                statusElement.textContent = 'Chiusa';
            }
        }
        
        function callNextTicket() {
            fetch('{{ route("operator.call-next") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCurrentTicket(data.ticket);
                    refreshWaitingTickets();
                } else {
                    alert('Errore: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Si è verificato un errore durante la chiamata del ticket');
            });
        }
        
        function startTicket() {
            const currentTicket = document.getElementById('current-ticket');
            if (!currentTicket) return;
            
            fetch('{{ route("operator.start-ticket") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ticket_id: currentTicket.dataset.id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Aggiorna il ticket mostrato come "in gestione"
                    const statusEl = currentTicket.querySelector('p:nth-child(3) span');
                    if (statusEl) {
                        statusEl.textContent = 'In gestione';
                    }
                    
                    // Nascondi il pulsante "Inizia servizio"
                    const startBtn = document.getElementById('btn-start');
                    if (startBtn) {
                        startBtn.style.display = 'none';
                    }
                    
                    // Aggiorna lo stato della postazione a "occupata"
                    updateStationStatus('busy');
                    
                    // Aggiorna stato dei pulsanti
                    document.getElementById('btn-active').disabled = false;
                    document.getElementById('btn-paused').disabled = false;
                    //document.getElementById('btn-call-next').disabled = true;
  
                } else {
                    alert('Errore: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Si è verificato un errore durante l\'avvio del servizio');
            });
        }        

        function completeTicket() {
            const currentTicket = document.getElementById('current-ticket');
            if (!currentTicket) return;
            
            fetch('{{ route("operator.complete-ticket") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ticket_id: currentTicket.dataset.id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Rimuovi il ticket corrente
                    document.getElementById('current-ticket-container').innerHTML = `
                        <div class="text-center p-6 bg-gray-50 rounded-lg">
                            <p class="text-lg text-gray-500">Nessun ticket in gestione</p>
                        </div>
                    `;
                    
                    // Nascondi le opzioni di trasferimento
                    const transferOptions = document.getElementById('transfer-options');
                    if (transferOptions) {
                        transferOptions.classList.add('hidden');
                    }
                    
                    // Aggiorna lo stato della postazione a "attiva"
                    updateStationStatus('active');
                    
                    // Aggiorna stato dei pulsanti
                    document.getElementById('btn-active').disabled = true;
                    document.getElementById('btn-paused').disabled = false;
                    //document.getElementById('btn-call-next').disabled = false;
                    
                    // Avvia il polling per cercare nuovi ticket
                    startPolling();
                } else {
                    alert('Errore: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Si è verificato un errore durante il completamento del ticket');
            });
        }
        
        function abandonTicket() {
            const currentTicket = document.getElementById('current-ticket');
            if (!currentTicket) return;
            
            if (!confirm('Sei sicuro di voler contrassegnare questo ticket come abbandonato?')) {
                return;
            }
            
            fetch('{{ route("operator.abandon-ticket") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ticket_id: currentTicket.dataset.id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Rimuovi il ticket corrente
                    document.getElementById('current-ticket-container').innerHTML = `
                        <div class="text-center p-6 bg-gray-50 rounded-lg">
                            <p class="text-lg text-gray-500">Nessun ticket in gestione</p>
                        </div>
                    `;
                    
                    // Nascondi le opzioni di trasferimento
                    const transferOptions = document.getElementById('transfer-options');
                    if (transferOptions) {
                        transferOptions.classList.add('hidden');
                    }

                    // Aggiorna lo stato della postazione a "attiva"
                    updateStationStatus('active');

                    // Aggiorna stato dei pulsanti
                    document.getElementById('btn-active').disabled = true;
                    document.getElementById('btn-paused').disabled = false;
                    //document.getElementById('btn-call-next').disabled = false; 

                    // Avvia il polling per cercare nuovi ticket
                    startPolling();

                } else {
                    alert('Errore: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Si è verificato un errore durante l\'abbandono del ticket');
            });
        }
        
        function transferTicket(ticketId, serviceId) {
            fetch('{{ route("operator.transfer-ticket") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ 
                    ticket_id: ticketId,
                    service_id: serviceId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Rimuovi il ticket corrente
                    document.getElementById('current-ticket-container').innerHTML = `
                        <div class="text-center p-6 bg-gray-50 rounded-lg">
                            <p class="text-lg text-gray-500">Nessun ticket in gestione</p>
                        </div>
                    `;
                    
                    // Nascondi le opzioni di trasferimento
                    const transferOptions = document.getElementById('transfer-options');
                    if (transferOptions) {
                        transferOptions.classList.add('hidden');
                    }

                    // Aggiorna lo stato della postazione a "attiva"
                    updateStationStatus('active');
                    
                    // Aggiorna stato dei pulsanti
                    document.getElementById('btn-active').disabled = true;
                    document.getElementById('btn-paused').disabled = false;
                    //document.getElementById('btn-call-next').disabled = false;

                    // Avvia il polling per cercare nuovi ticket
                    startPolling();

                    
                    alert('Ticket trasferito con successo');
                } else {
                    alert('Errore: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                alert('Si è verificato un errore durante il trasferimento del ticket');
            });
        }
        
        function  updateCurrentTicket(ticket) {
            document.getElementById('current-ticket-container').innerHTML = `
                <div id="current-ticket" data-id="${ticket.id}" class="text-center p-6 bg-blue-50 rounded-lg">
                    <div class="text-6xl font-bold mb-6 text-blue-600">${ticket.number}</div>
                    <p class="text-lg mb-2">Tipo: <span class="font-bold">${ticket.type === 'info' ? 'Informazioni' : 'Servizio'}</span></p>
                    <p class="text-lg mb-4">In attesa da: <span class="font-bold">${ticket.created_at}</span></p>
                    
                    <div class="flex gap-2 justify-center mt-6">
                        <button id="btn-complete" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md">
                            Completa
                        </button>
                        <button id="btn-abandon" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md">
                            Abbandona
                        </button>
                    </div>
                </div>
            `;
            
            // Aggiungi event listener ai nuovi pulsanti
            document.getElementById('btn-complete').addEventListener('click', function() {
                completeTicket();
            });
            
            document.getElementById('btn-abandon').addEventListener('click', function() {
                abandonTicket();
            });
            
            // Mostra le opzioni di trasferimento (se presenti)
            const transferOptions = document.getElementById('transfer-options');
            if (transferOptions) {
                transferOptions.classList.remove('hidden');
            }

            // Aggiorna lo stato dei pulsanti
            updateButtonState();
        }
        
        function refreshWaitingTickets() {
            // In un'applicazione reale, chiameresti un endpoint API per ottenere i ticket in attesa aggiornati
            // Per semplicità, qui facciamo solo un ricaricamento della pagina dopo un breve ritardo
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        // Variabile per tenere traccia dell'intervallo di polling
        let pollingInterval = null;

        // Funzione per avviare il polling
        function startPolling() {
            // Pulisci eventuali intervalli esistenti prima di crearne uno nuovo
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }

            // Imposta l'intervallo per controllare i ticket ogni 5 secondi
            pollingInterval = setInterval(checkWaitingTickets, 5000);
            
            // Log per debug
            console.log('Polling avviato: controllo ticket ogni 5 secondi');
        }

        // Funzione per fermare il polling
        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
                
                // Log per debug
                console.log('Polling fermato');
            }
        }

        // Funzione per controllare se ci sono ticket in attesa
        function checkWaitingTickets() {
            // Verifica se la postazione è attiva e non ha ticket in gestione
            const stationStatus = document.getElementById('station-status').textContent.trim();
            const currentTicket = document.getElementById('current-ticket');
            
            if (stationStatus !== 'Attiva' || currentTicket) {
                // Se la postazione non è attiva o ha già un ticket, ferma il polling
                stopPolling();
                return;
            }
            
            // Effettua una chiamata AJAX per verificare la presenza di ticket in attesa
            fetch('{{ route("operator.check-waiting-tickets") }}', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Se ci sono ticket in attesa, assegna il primo
                    if (data.hasWaitingTickets) {
                        console.log('Trovato ticket in attesa. Assegnazione in corso...');
                        callNextTicket();
                        stopPolling(); // Ferma il polling dopo l'assegnazione
                    } else {
                        console.log('Nessun ticket in attesa. Continuo il polling...');
                    }
                } else {
                    console.error('Errore durante il controllo dei ticket:', data.error);
                }
            })
            .catch(error => {
                console.error('Errore di rete durante il polling:', error);
            });
        }

        // Verifica se avviare il polling all'avvio
        
        if (stationStatus === 'Attiva' && !currentTicket) {
            // Se la postazione è attiva ma non ha ticket, avvia il polling
            startPolling();
        }

        // Variabile per tenere traccia dell'intervallo di aggiornamento dei ticket in attesa
        let waitingTicketsInterval = null;

        // Funzione per avviare l'aggiornamento periodico della lista dei ticket in attesa
        function startWaitingTicketsUpdater() {
            // Pulisci eventuali intervalli esistenti prima di crearne uno nuovo
            if (waitingTicketsInterval) {
                clearInterval(waitingTicketsInterval);
            }

            // Imposta l'intervallo per aggiornare la lista dei ticket in attesa ogni 10 secondi
            // Utilizziamo un intervallo più lungo di quello del polling principale per ridurre il carico sul server
            waitingTicketsInterval = setInterval(updateWaitingTickets, 10000);
            
            // Log per debug
            console.log('Aggiornamento ticket in attesa avviato: ogni 10 secondi');
        }

        // Funzione per fermare l'aggiornamento periodico
        function stopWaitingTicketsUpdater() {
            if (waitingTicketsInterval) {
                clearInterval(waitingTicketsInterval);
                waitingTicketsInterval = null;
                
                // Log per debug
                console.log('Aggiornamento ticket in attesa fermato');
            }
        }

        // Funzione per aggiornare la lista dei ticket in attesa
        function updateWaitingTickets() {
            // Fetch per ottenere i ticket in attesa aggiornati
            fetch('{{ route("operator.get-waiting-tickets") }}', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Aggiorna il contatore nel titolo
                    const waitingCount = data.tickets.length;
                    
                    // Trova l'elemento h2 che contiene il testo "In attesa" - correzione del selettore
                    const headings = document.querySelectorAll('h2');
                    headings.forEach(heading => {
                        if (heading.textContent.includes('In attesa')) {
                            heading.textContent = `In attesa (${waitingCount})`;
                        }
                    });
                    
                    // Referenza al container dei ticket in attesa
                    const waitingTicketsContainer = document.getElementById('waiting-tickets-container');
                    
                    // Se non ci sono ticket in attesa, mostra un messaggio
                    if (waitingCount === 0) {
                        waitingTicketsContainer.innerHTML = `
                            <div class="p-4 text-center text-gray-500">
                                Nessun ticket in attesa
                            </div>
                        `;
                        return;
                    }
                    
                    // Crea l'HTML per i ticket in attesa
                    let ticketsHtml = '';
                    data.tickets.forEach(ticket => {
                        const timeAgo = ticket.created_at_diff;
                        const ticketType = ticket.type === 'info' ? 'Info' : 'Servizio';
                        const typeClass = ticket.type === 'info' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';
                        
                        ticketsHtml += `
                            <div class="p-3 bg-gray-50 rounded flex justify-between items-center ticket-item" data-id="${ticket.id}">
                                <div>
                                    <span class="font-bold text-xl">${ticket.ticket_number}</span>
                                    <p class="text-sm text-gray-500">${timeAgo}</p>
                                </div>
                                <div>
                                    <span class="px-2 py-1 rounded text-xs ${typeClass}">
                                        ${ticketType}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    
                    // Aggiorna il contenitore con i nuovi ticket
                    waitingTicketsContainer.innerHTML = ticketsHtml;
                    
                    console.log('Lista ticket in attesa aggiornata');
                } else {
                    console.error('Errore durante l\'aggiornamento dei ticket in attesa:', data.error);
                }
            })
            .catch(error => {
                console.error('Errore di rete durante l\'aggiornamento dei ticket in attesa:', error);
            });
        }

        // Funzione per controllare e aggiornare lo stato dei pulsanti in base al ticket corrente
        function updateButtonState() {
            const currentTicket = document.getElementById('current-ticket');
            const btnActive = document.getElementById('btn-active');
            const btnPaused = document.getElementById('btn-paused');
            const btnCloseStation = document.getElementById('btn-close-station'); // Pulsante per chiudere la postazione
            
            // Se c'è un ticket corrente (in gestione o chiamato)
            if (currentTicket) {
                // Disabilita tutti i pulsanti di cambio stato quando c'è un ticket assegnato
                btnActive.disabled = true;
                btnPaused.disabled = true;
                btnCloseStation.disabled = true;

            } else {
                // Se non c'è un ticket corrente, aggiorna i pulsanti in base allo stato della postazione
                const stationStatus = document.getElementById('station-status').textContent.trim();
                
                btnActive.disabled = (stationStatus === 'Attiva');
                btnPaused.disabled = (stationStatus === 'In pausa');
                btnCloseStation.disabled = false; // Abilita il pulsante per chiudere la postazione quando non ci sono ticket
            }
        }        

        // Avvia l'aggiornamento periodico dei ticket in attesa
        startWaitingTicketsUpdater();
    });
</script>
@endsection