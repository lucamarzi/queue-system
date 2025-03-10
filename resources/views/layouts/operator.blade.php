<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Sistema di Gestione Code') }} - Operatore</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
                <h1 class="text-2xl font-bold">Sistema Gestione Code</h1>
                <span class="ml-4 px-2 py-1 bg-blue-800 rounded text-sm">Operatore</span>
            </div>
            
            <div class="flex items-center">
                <span class="mr-4">Postazione: {{ $station->name }}</span>
                <form action="{{ route('station.close') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-red-700 hover:bg-red-800 text-white px-3 py-1 rounded">
                        Chiudi Postazione
                    </button>
                </form>
            </div>
        </div>
    </header>
    
    <main class="flex-grow container mx-auto py-4 px-4">
        @yield('content')
    </main>
    
    <footer class="bg-gray-200 py-4">
        <div class="container mx-auto px-4 text-center text-gray-600">
            &copy; {{ date('Y') }} Sistema di Gestione Code
        </div>
    </footer>
    
    @yield('scripts')
</body>
</html>