@extends('layouts.operator')

@section('content')
<div class="flex items-center justify-center h-screen bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full text-center">
        <svg class="w-16 h-16 text-yellow-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        
        <h1 class="text-2xl font-bold mb-4 text-gray-800">Nessuna postazione assegnata</h1>
        
        <p class="text-gray-600 mb-6">
            Non hai alcuna postazione assegnata al tuo account. Contatta l'amministratore per configurare la tua postazione.
        </p>
        
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                Logout
            </button>
        </form>
    </div>
</div>
@endsection

{{-- resources/views/layouts/operator.blade.php --}}
<!DOCTYPE html>
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
                <span class="mr-4">{{ Auth::user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-3 py-1 rounded">
                        Logout
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