<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema di Gestione Code - Totem</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen">
    <div class="flex flex-col h-full">
        <header class="bg-blue-600 text-white p-4 shadow-md">
            <h1 class="text-3xl font-bold text-center">Benvenuto</h1>
            <p class="text-center text-xl">Seleziona un servizio per richiedere un ticket</p>
        </header>
        
        <main class="flex-grow flex flex-col items-center justify-center p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-4xl">
                @if(session('error'))
                    <div class="col-span-1 md:col-span-2 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                        {{ session('error') }}
                    </div>
                @endif
                
                @foreach($receptionServices as $service)
                    <div>
                        <form action="{{ route('totem.create-ticket') }}" method="POST">
                            @csrf
                            <input type="hidden" name="service_id" value="{{ $service->id }}">
                            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white text-center py-8 px-6 rounded-lg shadow-lg text-xl font-bold transition duration-300">
                                {{ $service->name }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </main>
        
        <footer class="bg-gray-200 p-4 text-center text-gray-600">
            &copy; {{ date('Y') }} Sistema di Gestione Code
        </footer>
    </div>
</body>
</html>