{{-- resources/views/filament/resources/ticket-resource/log-modal.blade.php --}}
<div>
    @if($logs->isEmpty())
        <div class="text-center p-4 text-gray-500">
            Nessun log disponibile per questo ticket.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato Precedente</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nuovo Stato</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Postazione</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($logs as $log)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->status_from)
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ \App\Filament\Resources\TicketResource::getStatusBadgeColor($log->status_from) }}-100 text-{{ \App\Filament\Resources\TicketResource::getStatusBadgeColor($log->status_from) }}-800">
                                        {{ \App\Filament\Resources\TicketResource::getStatusLabel($log->status_from) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">Nuovo Ticket</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ \App\Filament\Resources\TicketResource::getStatusBadgeColor($log->status_to) }}-100 text-{{ \App\Filament\Resources\TicketResource::getStatusBadgeColor($log->status_to) }}-800">
                                    {{ \App\Filament\Resources\TicketResource::getStatusLabel($log->status_to) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $log->station ? $log->station->name : 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>