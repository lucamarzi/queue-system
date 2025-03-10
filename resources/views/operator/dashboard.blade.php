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
                        {{ $station->status === 'active' ? 'bg-green-500' : ($station->status === 'paused' ? 'bg-yellow-500' : 'bg-red-500') }}">
                        {{ $station->status === 'active' ? 'Attiva' : ($station->status === 'paused' ? 'In pausa' : 'Chiusa') }}
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
                    <button id="btn-busy" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md"
                            {{ $station->status === 'busy' ? 'disabled' : '' }}>
                        Pausa
                    </button>
                    <button id="btn-paused" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md"
                            {{ $station->status === 'paused' ? 'disabled' : '' }}>
                        Pausa
                    </button>
                </div>
                <div class="mt-4">
                    <form action="{{ route('station.close') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md">
                            Chiudi Postazione
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="mb-6">
                <button id="btn-call-next" class="w-full px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-lg font-bold"
                        {{ $station->status !== 'active' ? 'disabled' : '' }}>
                    Chiama prossimo ticket
                </button>
            </div>
            
            @if($service->is_reception)
            <div class="mt-8">
                <h3 class="text-lg font-bold mb-2">Trasferisci a servizio</h3>
                <div id="transfer-options" class="mt-4 hidden">
                    <select id="transfer-service" class="w-full p-2 border rounded mb-2">
                        <option value="">Seleziona servizio...</option>
                        @foreach(\App\Models\Service::where('is_reception', false)->get() as $transferService)
                            <option value="{{ $transferService->id }}">{{ $transferService->name }}</option>
                        @endforeach
                    </select>
                    <button id="btn-transfer" class="w-full px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-md">
                        Trasferisci
                    </button>
                </div>
            </div>
            @endif
        </div>
        
        <!-- Pannello centrale - Ticket corrente -->
        <div class="w-full md:w-1/3 bg-white rounded-lg shadow-md p-4">
            <h2 class="text-2xl font-bold mb-4">Ticket Corrente</h2>
            
            <div id="current-ticket-container">
                @if($currentTicket)
                    <div id="current-ticket" data-id="{{ $currentTicket->id }}" class="text-center p-6 bg-blue-50 rounded-lg">
                        <div class="text-6xl font-bold mb-6 text-blue-600">{{ $currentTicket->ticket_number }}</div>
                        <p class="text-lg mb-2">Tipo: <span class="font-bold">{{ $currentTicket->type === 'info' ? 'Informazioni' : 'Servizio' }}</span></p>
                        <p class="text-lg mb-4">In attesa da: <span class="font-bold">{{ $currentTicket->created_at->diffForHumans() }}</span></p>
                        
                        <div class="flex gap-2 justify-center mt-6">
                            <button id="btn-complete" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md">
                                Completa
                            </button>
                            <button id="btn-abandon" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md">
                                Abbandona
                            </button>
                        </div>
                    </div>
                @else
                    <div class="text-center p-6 bg-gray-50 rounded-lg">
                        <p class="text-lg text-gray-500">Nessun ticket in gestione</p>
                        <p class="text-sm text-gray-400 mt-2">Clicca su "Chiama prossimo ticket" per iniziare</p>
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
        // Gestione dei pulsanti di stato
        document.getElementById('btn-active').addEventListener('click', function() {
            changeStationStatus('active');
        });
        
        document.getElementById('btn-paused').addEventListener('click', function() {
            changeStationStatus('paused');
        });
        
        // Chiamata prossimo ticket
        document.getElementById('btn-call-next').addEventListener('click', function() {
            callNextTicket();
        });
        
        // Gestione ticket corrente
        const completeBtn = document.getElementById('btn-complete');
        if (completeBtn) {
            completeBtn.addEventListener('click', function() {
                completeTicket();
            });
        }
        
        const abandonBtn = document.getElementById('btn-abandon');
        if (abandonBtn) {
            abandonBtn.addEventListener('click', function() {
                abandonTicket();
            });
        }
        
        // Gestione trasferimento
        const currentTicket = document.getElementById('current-ticket');
        const transferOptions = document.getElementById('transfer-options');
        
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
                    document.getElementById('btn-call-next').disabled = (status !== 'active');
                    
                    // Se la postazione è chiusa, rimuovi il ticket corrente
                    if (status === 'closed') {
                        document.getElementById('current-ticket-container').innerHTML = `
                            <div class="text-center p-6 bg-gray-50 rounded-lg">
                                <p class="text-lg text-gray-500">Nessun ticket in gestione</p>
                                <p class="text-sm text-gray-400 mt-2">Clicca su "Chiama prossimo ticket" per iniziare</p>
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
            statusElement.classList.remove('bg-green-500', 'bg-yellow-500', 'bg-red-500');
            
            // Aggiungi nuova classe e testo
            if (status === 'active') {
                statusElement.classList.add('bg-green-500');
                statusElement.textContent = 'Attiva';
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
                            <p class="text-sm text-gray-400 mt-2">Clicca su "Chiama prossimo ticket" per iniziare</p>
                        </div>
                    `;
                    
                    // Nascondi le opzioni di trasferimento
                    const transferOptions = document.getElementById('transfer-options');
                    if (transferOptions) {
                        transferOptions.classList.add('hidden');
                    }
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
                            <p class="text-sm text-gray-400 mt-2">Clicca su "Chiama prossimo ticket" per iniziare</p>
                        </div>
                    `;
                    
                    // Nascondi le opzioni di trasferimento
                    const transferOptions = document.getElementById('transfer-options');
                    if (transferOptions) {
                        transferOptions.classList.add('hidden');
                    }
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
                            <p class="text-sm text-gray-400 mt-2">Clicca su "Chiama prossimo ticket" per iniziare</p>
                        </div>
                    `;
                    
                    // Nascondi le opzioni di trasferimento
                    const transferOptions = document.getElementById('transfer-options');
                    if (transferOptions) {
                        transferOptions.classList.add('hidden');
                    }
                    
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
        
        function updateCurrentTicket(ticket) {
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
        }
        
        function refreshWaitingTickets() {
            // In un'applicazione reale, chiameresti un endpoint API per ottenere i ticket in attesa aggiornati
            // Per semplicità, qui facciamo solo un ricaricamento della pagina dopo un breve ritardo
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    });
</script>
@endsection