<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Generato</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full text-center">
        <h1 class="text-3xl font-bold mb-6">Ticket Generato</h1>
        
        <div class="text-8xl font-bold mb-6 text-blue-600">
            {{ $ticket->ticket_number }}
        </div>
        
        <div class="mb-6">
            <p class="text-xl">Servizio: {{ $ticket->service->name }}</p>
            <p class="text-gray-600">Data: {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
        </div>
        
        <div class="bg-gray-100 p-4 rounded-lg mb-6">
            <p class="text-lg">In attesa: <span class="font-bold">{{ $ticket->service->waiting_count }}</span> persone</p>
        </div>
        
        <a href="{{ route('totem.index') }}" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg shadow transition duration-300">
            Torna al Menu
        </a>
        
        <div class="mt-6 text-gray-600">
            <p>Prendere nota del numero e attendere la chiamata sul monitor</p>
        </div>
    </div>
    
    <script>
        // Reindirizza automaticamente al menu principale dopo 15 secondi
        setTimeout(function() {
            window.location.href = "{{ route('totem.index') }}";
        }, 15000);
        
        // Stampa automaticamente il ticket
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>