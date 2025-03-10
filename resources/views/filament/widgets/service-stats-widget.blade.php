<x-filament::widget>
    <x-filament::section>
        <x-slot name="heading">Statistiche per Servizio (Oggi)</x-slot>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-2 font-medium">Servizio</th>
                        <th class="px-4 py-2 font-medium text-center">In attesa</th>
                        <th class="px-4 py-2 font-medium text-center">Completati oggi</th>
                        <th class="px-4 py-2 font-medium text-center">Tempo medio attesa</th>
                        <th class="px-4 py-2 font-medium text-center">Tempo medio servizio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->getServiceStats() as $stat)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $stat['service']->name }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                    {{ $stat['waiting'] }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                    {{ $stat['completed'] }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">{{ $stat['avg_wait_time'] }} min</td>
                            <td class="px-4 py-2 text-center">{{ $stat['avg_service_time'] }} min</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament::widget>