{{-- resources/views/monitor/services.blade.php --}}
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="300">
    <title>Monitor Servizi</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @keyframes flash {
            0% { background-color: rgba(59, 130, 246, 0.1); }
            50% { background-color: rgba(59, 130, 246, 0.3); }
            100% { background-color: rgba(59, 130, 246, 0.1); }
        }
        .flash-animation {
            animation: flash 2s infinite;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .main-container {
            display: flex;
            height: 100vh;
            flex-direction: column;
        }
        
        .content-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        .left-panel {
            width: 60%;
            border-right: 2px solid #ddd;
            padding: 1rem;
            overflow-y: auto;
            background-color: #f9fafb;
        }
        
        .right-panel {
            width: 40%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #f0f9ff;
            padding: 1rem;
        }
        
        .service-header {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            padding-bottom: 8px;
            margin-bottom: 12px;
            border-bottom: 2px solid #3b82f6;
        }
        
        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .ticket-table th {
            background-color: #e5e7eb;
            text-align: left;
            padding: 10px;
            font-weight: bold;
            border-bottom: 2px solid #d1d5db;
        }
        
        .ticket-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .ticket-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .ticket-table tr:hover {
            background-color: #f3f4f6;
        }
        
        .ticket-number-cell {
            font-size: 20px;
            font-weight: bold;
            color: #1d4ed8;
        }
        
        .last-ticket-container {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
        }
        
        .last-ticket-label {
            font-size: 28px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 1rem;
        }
        
        .last-ticket-number {
            font-size: 72px;
            font-weight: bold;
            color: #1d4ed8;
        }
        
        .last-ticket-station {
            font-size: 24px;
            margin-top: 1rem;
        }
        
        .station-label {
            font-weight: bold;
        }
        
        .no-ticket-message {
            font-size: 24px;
            color: #6b7280;
            text-align: center;
        }
        
        .current-time {
            font-size: 28px;
            font-weight: bold;
        }
        
        .current-date {
            font-size: 20px;
        }
        
        .footer {
            padding: 1rem;
            text-align: center;
            background-color: #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <header class="bg-blue-600 text-white p-4 shadow-md">
            <h1 class="text-4xl font-bold text-center">MONITOR SERVIZI</h1>
            <p class="text-center text-xl">Ticket chiamati</p>
        </header>
        
        <div class="content-container">
            <!-- Pannello sinistro: elenco tabellare di tutti i ticket chiamati -->
            <div class="left-panel" id="all-tickets-panel">
                <div id="services-container">
                    <!-- I servizi e relativi ticket saranno inseriti qui tramite JavaScript -->
                    <div class="p-8 text-center text-gray-500 text-2xl">
                        Caricamento...
                    </div>
                </div>
            </div>
            
            <!-- Pannello destro: ultimo ticket chiamato -->
            <div class="right-panel" id="last-ticket-panel">
                <div id="last-ticket-content">
                    <!-- Il contenuto sarà aggiornato via JavaScript -->
                    <div class="no-ticket-message">
                        Nessun ticket chiamato al momento
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="footer">
            <div class="current-time" id="current-time"></div>
            <div class="current-date" id="current-date"></div>
        </footer>
    </div>
    
    <!-- Audio per la notifica -->
    <audio id="notification-sound" preload="auto">
        <source src="{{ asset('sounds/notification.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('sounds/notification.wav') }}" type="audio/wav">
        Il tuo browser non supporta l'elemento audio.
    </audio>
    
    <script>
    // Debug console
    console.log('Monitor Servizi script loaded');
    
    // Variabile per tenere traccia dei ticket già visualizzati per ogni servizio
    let lastSeenTickets = {}; // { serviceId: [ticketNumbers] }
    
    // Variabile per tenere traccia dell'ultimo ticket chiamato
    let lastTicketCalled = null;
    
    // Aggiorna data e ora
    function updateDateTime() {
        const now = new Date();
        document.getElementById('current-time').textContent = now.toLocaleTimeString('it-IT');
        document.getElementById('current-date').textContent = now.toLocaleDateString('it-IT', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }
    
    // Riproduci il suono di notifica
    function playNotificationSound() {
        const audio = document.getElementById('notification-sound');
        
        // Reset audio to start
        audio.pause();
        audio.currentTime = 0;
        
        // Play the audio
        audio.play().catch(error => {
            console.error('Error playing audio:', error);
        });
    }
    
    // Aggiorna l'ultimo ticket chiamato
    function updateLastTicket(ticket, serviceName) {
        console.log('Updating last ticket:', ticket, serviceName);
        const lastTicketPanel = document.getElementById('last-ticket-content');
        
        if (!ticket) {
            lastTicketPanel.innerHTML = `
                <div class="no-ticket-message">
                    Nessun ticket chiamato al momento
                </div>
            `;
            return;
        }
        
        lastTicketPanel.innerHTML = `
            <div class="last-ticket-container flash-animation">
                <div class="last-ticket-label">Ultimo Ticket Chiamato</div>
                <div class="last-ticket-number">${ticket.number}</div>
                <div class="last-ticket-station">
                    <span class="station-label">Postazione:</span> 
                    <span>${ticket.station}</span>
                </div>
                <div class="mt-2">
                    <span class="font-bold">Servizio:</span> 
                    <span>${serviceName}</span>
                </div>
            </div>
        `;
    }
    
    // Carica i ticket chiamati per ogni servizio
    function loadServicesData() {
        console.log('Loading services data...');
        fetch('{{ route("monitor.data") }}')
            .then(response => response.json())
            .then(data => {
                console.log('Services data received:', data);
                const container = document.getElementById('services-container');
                
                if (!data || data.length === 0) {
                    container.innerHTML = '<div class="p-8 text-center text-gray-500 text-2xl">Nessun servizio attivo al momento</div>';
                    updateLastTicket(null);
                    return;
                }
                
                let newTicketsDetected = false; // Flag per indicare se ci sono nuovi ticket
                let newestTicket = null;
                let newestTicketService = '';
                let newestTicketTimestamp = null;
                let hasAnyTickets = false;
                
                let html = '';
                
                // Elabora i dati di ogni servizio
                data.forEach(service => {
                    const serviceName = service.service;
                    
                    // Solo se ci sono ticket chiamati per questo servizio
                    if (service.tickets && service.tickets.length > 0) {
                        const currentTickets = service.tickets.map(ticket => ticket.number);
                        hasAnyTickets = true;
                        
                        // Se non abbiamo ancora visto questo servizio, lo inizializziamo
                        if (!lastSeenTickets[serviceName]) {
                            lastSeenTickets[serviceName] = currentTickets;
                        } else {
                            // Controlla se ci sono ticket che non abbiamo visto prima
                            for (const ticketNumber of currentTickets) {
                                if (!lastSeenTickets[serviceName].includes(ticketNumber)) {
                                    console.log('New ticket detected for service', serviceName, ':', ticketNumber);
                                    newTicketsDetected = true;
                                    break;
                                }
                            }
                            
                            // Aggiorna la lista dei ticket visti per questo servizio
                            lastSeenTickets[serviceName] = currentTickets;
                        }
                        
                        html += `
                            <div class="mb-8">
                                <h2 class="service-header">${serviceName}</h2>
                                <table class="ticket-table">
                                    <thead>
                                        <tr>
                                            <th>Numero Ticket</th>
                                            <th>Postazione</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        service.tickets.forEach((ticket, index) => {
                            // Per ogni ticket, verifica se potrebbe essere il più recente
                            // Se è il primo ticket del servizio, consideralo il più recente
                            if (index === 0) {
                                if (!newestTicket || !newestTicketTimestamp) {
                                    newestTicket = ticket;
                                    newestTicketService = serviceName;
                                    newestTicketTimestamp = new Date(); // Timestamp corrente
                                    console.log('Found newest ticket (first in service):', ticket.number, serviceName);
                                }
                            }
                            
                            html += `
                                <tr>
                                    <td class="ticket-number-cell">${ticket.number}</td>
                                    <td>${ticket.station}</td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }
                });
                
                // Aggiorna l'elenco dei ticket
                container.innerHTML = html || '<div class="p-8 text-center text-gray-500 text-2xl">Nessun ticket chiamato al momento</div>';
                
                // Aggiorna l'ultimo ticket chiamato nella sezione a destra
                console.log('Newest ticket found:', newestTicket, newestTicketService);
                if (newestTicket && hasAnyTickets) {
                    updateLastTicket(newestTicket, newestTicketService);
                    
                    // Se è un nuovo ticket rispetto all'ultimo visualizzato
                    if (!lastTicketCalled || lastTicketCalled.number !== newestTicket.number) {
                        lastTicketCalled = newestTicket;
                        
                        // Riproduci il suono solo se è un nuovo ticket
                        if (newTicketsDetected) {
                            playNotificationSound();
                        }
                    }
                } else {
                    // Se non ci sono ticket, nascondi l'ultimo ticket chiamato
                    updateLastTicket(null);
                }
            })
            .catch(error => {
                console.error('Errore nel caricamento dei dati:', error);
                const container = document.getElementById('services-container');
                container.innerHTML = '<div class="p-8 text-center text-red-500 text-2xl">Errore nel caricamento dei dati</div>';
            });
    }
    
    // Inizializza
    updateDateTime();
    loadServicesData();
    
    // Aggiorna periodicamente
    setInterval(updateDateTime, 1000);
    setInterval(loadServicesData, 5000);
</script>
</body>
</html>