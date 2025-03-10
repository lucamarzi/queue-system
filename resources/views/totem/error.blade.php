<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Errore - Sistema di Gestione Code</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full text-center">
        <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        
        <h1 class="text-2xl font-bold mb-4">Errore</h1>
        
        <p class="text-gray-700 mb-6">{{ $message }}</p>
        
        <div class="mt-6">
            <a href="/" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                Torna alla Home
            </a>
        </div>
    </div>
</body>
</html>