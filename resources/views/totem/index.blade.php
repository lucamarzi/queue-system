<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema di Gestione Code - Totem</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Per assicurarsi che l'area principale occupi tutto lo spazio disponibile */
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        .button-container {
            display: grid;
            grid-template-rows: repeat(auto-fit, minmax(200px, 1fr));
            max-height: 100%;
            width: 100%;
            gap: 1.5rem;
        }
        
        @media (min-width: 768px) {
            .button-container {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                grid-auto-rows: 1fr;
            }
        }
        
        .service-button {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .service-button:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .service-icon {
            font-size: 6rem;
            margin-bottom: 1rem;
        }
        
        .service-name {
            font-size: 4rem;
            font-weight: bold;
            margin-bottom: 0.75rem;
        }
        
        .service-description {
            font-size: 2rem;
            opacity: 0.9;
        }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col">
    <header class="bg-blue-600 text-white p-5 shadow-md">
        <h1 class="text-4xl font-bold text-center">Benvenuto</h1>
        <p class="text-center text-2xl mt-2">Seleziona un servizio per richiedere un ticket</p>
    </header>
    
    <main class="flex-grow p-4 md:p-6 flex flex-col">
        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-xl">
                {{ session('error') }}
            </div>
        @endif
        
        <div class="button-container flex-grow">
            @foreach($receptionServices as $service)
                <form action="{{ route('totem.create-ticket') }}" method="POST" class="h-full">
                    @csrf
                    <input type="hidden" name="service_id" value="{{ $service->id }}">
                    <button type="submit" 
                        class="service-button w-full h-full text-white rounded-lg shadow-lg"
                        style="background-color: {{ $service->color }};">
                        
                        <i class="service-icon fas {{ $service->icon }}"></i>
                        
                        <span class="service-name">{{ $service->name }}</span>
                        
                        @if($service->description)
                            <span class="service-description">{{ $service->description }}</span>
                        @endif
                    </button>
                </form>
            @endforeach
        </div>
    </main>
    
    <footer class="bg-gray-200 p-4 text-center text-gray-600 text-lg">
        &copy; {{ date('Y') }} Sistema di Gestione Code
    </footer>
</body>
</html>