{{-- resources/views/welcome.blade.php --}}
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
            <!-- Totem -->
            <a href="{{ route('totem.index') }}" target="_blank" class="link-card bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-blue-500 text-white p-4">
                    <h2 class="text-2xl font-bold">Totem</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600">Interfaccia per la generazione dei ticket per gli utenti.</p>
                    <div class="mt-4 flex justify-end">
                        <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                            Apri in nuova scheda
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
            
            <!-- Monitor Reception -->
            <a href="{{ route('monitor.reception') }}" target="_blank" class="link-card bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-green-500 text-white p-4">
                    <h2 class="text-2xl font-bold">Monitor Reception</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600">Visualizzazione dei ticket chiamati in reception.</p>
                    <div class="mt-4 flex justify-end">
                        <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                            Apri in nuova scheda
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
            
            <!-- Monitor Servizi -->
            <a href="{{ route('monitor.services') }}" target="_blank" class="link-card bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-purple-500 text-white p-4">
                    <h2 class="text-2xl font-bold">Monitor Servizi</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600">Visualizzazione dei ticket chiamati nei vari servizi.</p>
                    <div class="mt-4 flex justify-end">
                        <span class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                            Apri in nuova scheda
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
            
            <!-- Dashboard Operatore -->
            <a href="{{ route('station.index') }}" target="_blank" class="link-card bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-yellow-500 text-white p-4">
                    <h2 class="text-2xl font-bold">Dashboard Operatore</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600">Accesso alla dashboard per gli operatori delle postazioni.</p>
                    <div class="mt-4 flex justify-end">
                        <span class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                            Apri in nuova scheda
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
            
            <!-- Admin -->
            <a href="{{ route('filament.admin.auth.login') }}" target="_blank" class="link-card bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-red-500 text-white p-4">
                    <h2 class="text-2xl font-bold">Admin</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600">Accesso al pannello di amministrazione del sistema.</p>
                    <div class="mt-4 flex justify-end">
                        <span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                            Apri in nuova scheda
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
        </div>
    </main>
    
    <footer class="bg-gray-200 py-4">
        <div class="container mx-auto px-4 text-center text-gray-600">
            &copy; {{ date('Y') }} Sistema di Gestione Code
        </div>
    </footer>
</body>
</html>