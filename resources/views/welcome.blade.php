<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema di Gestione Code</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f4f6;
        }
        
        .link-card {
            transition: all 0.3s ease;
        }
        
        .link-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white shadow-md py-8">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold">Sistema di Gestione Code</h1>
            <p class="text-xl mt-2">Seleziona una delle opzioni disponibili</p>
        </div>
    </header>
    
    <main class="flex-grow container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {{-- Totem --}}
            <x-link-card 
                route="{{ route('totem.index') }}" 
                title="Totem" 
                description="Interfaccia per la generazione dei ticket per gli utenti." 
                color="blue" 
            />
            
            {{-- Monitor Reception --}}
            <x-link-card 
                route="{{ route('monitor.reception') }}" 
                title="Monitor Reception" 
                description="Visualizzazione dei ticket chiamati in reception." 
                color="green" 
            />
            
            {{-- Monitor Servizi --}}
            <x-link-card 
                route="{{ route('monitor.services') }}" 
                title="Monitor Servizi" 
                description="Visualizzazione dei ticket chiamati nei vari servizi." 
                color="purple" 
            />
            
            {{-- Dashboard Operatore --}}
            <x-link-card 
                route="{{ route('station.index') }}" 
                title="Dashboard Operatore" 
                description="Accesso alla dashboard per gli operatori delle postazioni." 
                color="yellow" 
            />
            
            {{-- Admin --}}
            <x-link-card 
                route="{{ route('filament.admin.auth.login') }}" 
                title="Dashboard Admin" 
                description="Accesso al pannello di amministrazione del sistema." 
                color="red" 
            />
        </div>
    </main>
    
    <footer class="bg-gray-200 py-4">
        <div class="container mx-auto px-4 text-center text-gray-600">
            &copy; {{ date('Y') }} Sistema di Gestione Code
        </div>
    </footer>
</body>
</html>