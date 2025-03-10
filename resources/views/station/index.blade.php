<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selezione Postazione</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-lg mx-auto">
            <h1 class="text-3xl font-bold mb-8 text-center">Seleziona una Postazione</h1>
            
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    {{ session('error') }}
                </div>
            @endif
            
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            
            <form action="{{ route('station.select') }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <label for="service" class="block text-gray-700 font-bold mb-2">Seleziona un Servizio:</label>
                    <select id="service" name="service_id" class="w-full p-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Seleziona Servizio --</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label for="station" class="block text-gray-700 font-bold mb-2">Seleziona una Postazione:</label>
                    <select id="station" name="station_id" class="w-full p-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" disabled>
                        <option value="">-- Prima seleziona un servizio --</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" id="submit-btn" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-md shadow-md transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Accedi alla Postazione
                    </button>
                </div>
            </form>
            
            <div class="mt-8 text-center">
                <a href="{{ route('filament.admin.auth.login') }}" class="text-blue-600 hover:text-blue-800 underline">
                    Accedi come Amministratore
                </a>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const serviceSelect = document.getElementById('service');
            const stationSelect = document.getElementById('station');
            const submitBtn = document.getElementById('submit-btn');
            
            // Store all stations data
            const stationsByService = {
                @foreach($serviceStations as $serviceId => $stations)
                    {{ $serviceId }}: [
                        @foreach($stations as $station)
                            {
                                id: {{ $station->id }},
                                name: "{{ $station->name }}",
                                isLocked: {{ $station->is_locked ? 'true' : 'false' }},
                                isActive: {{ $station->status === 'active' ? 'true' : 'false' }}
                            },
                        @endforeach
                    ],
                @endforeach
            };
            
            // When a service is selected, update the stations dropdown
            serviceSelect.addEventListener('change', function() {
                const serviceId = this.value;
                
                // Clear current options
                stationSelect.innerHTML = '<option value="">-- Seleziona Postazione --</option>';
                
                if (serviceId) {
                    // Enable the station select
                    stationSelect.disabled = false;
                    
                    // Get stations for this service
                    const stations = stationsByService[serviceId] || [];
                    
                    // Add available stations to dropdown
                    let availableStationsCount = 0;
                    
                    stations.forEach(station => {
                        // Skip locked or active stations
                        if (station.isLocked || station.isActive) {
                            const option = document.createElement('option');
                            option.value = '';
                            option.disabled = true;
                            option.textContent = `${station.name} - ${station.isLocked ? 'Bloccata' : 'Occupata'}`;
                            stationSelect.appendChild(option);
                        } else {
                            availableStationsCount++;
                            const option = document.createElement('option');
                            option.value = station.id;
                            option.textContent = station.name;
                            stationSelect.appendChild(option);
                        }
                    });
                    
                    if (availableStationsCount === 0) {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'Nessuna postazione disponibile per questo servizio';
                        stationSelect.appendChild(option);
                    }
                } else {
                    // Disable the station select if no service is selected
                    stationSelect.disabled = true;
                    stationSelect.innerHTML = '<option value="">-- Prima seleziona un servizio --</option>';
                }
                
                // Check if submit should be enabled
                updateSubmitButton();
            });
            
            // Enable/disable submit button based on selection
            stationSelect.addEventListener('change', updateSubmitButton);
            
            function updateSubmitButton() {
                submitBtn.disabled = !stationSelect.value;
            }
        });
    </script>
</body>
</html>