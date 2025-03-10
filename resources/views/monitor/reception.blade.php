{{-- resources/views/monitor/reception.blade.php --}}
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Reception</title>
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
        }
        
        .ticket-card {
            transition: all 0.3s ease;
        }
        
        .ticket-num {
            font-size: 42px;
            font-weight: bold;
        }
        
        .current-time {
            font-size: 28px;
            font-weight: bold;
        }
        
        .current-date {
            font-size: 20px;
        }
        
        .reception-name {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            color: white;
            background-color: #4f46e5;
            margin-left: 10px;
        }
    </style>
</head>
<body class="bg-gray-100 h-screen p-4">
    <div class="container mx-auto h-full flex flex-col">
        <header class="bg-blue-600 text-white p-6 rounded-t-lg">
            <h1 class="text-4xl font-bold text-center">MONITOR RECEPTION</h1>
            <p class="text-center text-xl">Ticket chiamati</p>
        </header>
        
        <main class="flex-grow bg-white p-6 rounded-b-lg shadow-lg">
            <div id="tickets-container" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- I ticket saranno inseriti qui tramite JavaScript -->
                <div class="p-8 text-center text-gray-500 text-2xl">
                    Caricamento...
                </div>
            </div>
        </main>
        
        <footer class="mt-6 text-center text-gray-600">
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
        console.log('Monitor Reception script loaded');
        
        // Mappa colori per identificare visivamente le reception
        const receptionColors = [
            '#4f46e5', // Indigo
            '#0891b2', // Cyan
            '#7c3aed', // Purple
            '#0284c7', // Sky Blue
            '#0d9488', // Teal
            '#2563eb', // Blue
            '#8b5cf6', // Violet
            '#0ea5e9', // Light Blue
        ];
        
        let receptionServicesMap = {};
        let lastSeenTickets = []; // Array per tenere traccia degli ultimi ticket visti
        
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
        
        // Carica i ticket chiamati
        function loadCalledTickets() {
            console.log('Loading called tickets...');
            const url = '{{ route("monitor.data") }}?is_reception=1';
            console.log('Fetching data from: ' + url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Data received:', data);
                    const container = document.getElementById('tickets-container');
                    
                    // Salva i servizi di reception e assegna un colore a ciascuno
                    if (data.receptionServices && Array.isArray(data.receptionServices)) {
                        data.receptionServices.forEach((name, index) => {
                            receptionServicesMap[name] = receptionColors[index % receptionColors.length];
                        });
                    }
                    
                    if (!data.tickets || data.tickets.length === 0) {
                        console.log('No tickets found');
                        container.innerHTML = '<div class="col-span-2 p-8 text-center text-gray-500 text-2xl">Nessun ticket chiamato al momento</div>';
                        lastSeenTickets = [];
                        return;
                    }
                    
                    console.log('Found ' + data.tickets.length + ' tickets');
                    
                    // Controllo se ci sono nuovi ticket
                    const currentTicketNumbers = data.tickets.map(ticket => ticket.number);
                    let newTickets = false;
                    
                    // Se è il primo caricamento, salviamo solo i ticket senza suonare
                    if (lastSeenTickets.length === 0) {
                        lastSeenTickets = currentTicketNumbers;
                    } else {
                        // Controlla se ci sono ticket che non abbiamo visto prima
                        for (const ticketNumber of currentTicketNumbers) {
                            if (!lastSeenTickets.includes(ticketNumber)) {
                                console.log('New ticket detected:', ticketNumber);
                                newTickets = true;
                                break;
                            }
                        }
                        
                        // Aggiorna la lista dei ticket visti
                        lastSeenTickets = currentTicketNumbers;
                    }
                    
                    // Riproduci il suono se ci sono nuovi ticket
                    if (newTickets) {
                        playNotificationSound();
                    }
                    
                    let html = '';
                    data.tickets.forEach((ticket, index) => {
                        const isNew = index === 0; // Considera il primo ticket come il più recente
                        const receptionColor = receptionServicesMap[ticket.service] || '#4f46e5'; // Colore default indigo
                        
                        html += `
                            <div class="ticket-card bg-gray-50 rounded-lg shadow p-6 flex flex-col items-center ${isNew ? 'flash-animation' : ''}">
                                <div class="ticket-num text-blue-600">${ticket.number}</div>
                                <div class="text-2xl">
                                    Postazione: <span class="font-bold">${ticket.station}</span>
                                    <span class="reception-name" style="background-color: ${receptionColor}">
                                        ${ticket.service}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Errore nel caricamento dei dati:', error);
                    const container = document.getElementById('tickets-container');
                    container.innerHTML = '<div class="col-span-2 p-8 text-center text-red-500 text-2xl">Errore nel caricamento dei dati</div>';
                });
        }
        
        // Inizializza
        updateDateTime();
        loadCalledTickets();
        
        // Aggiorna periodicamente
        setInterval(updateDateTime, 1000);
        setInterval(loadCalledTickets, 5000);
    </script>
</body>
</html>