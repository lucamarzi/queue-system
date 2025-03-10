<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Service;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Operazioni';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\TextInput::make('ticket_number')
                ->required()
                ->maxLength(255)
                ->label('Numero Ticket'),
            Forms\Components\Select::make('type')
                ->options([
                    'info' => 'Informazioni',
                    'service' => 'Servizio',
                ])
                ->required()
                ->label('Tipo'),
            Forms\Components\Select::make('status')
                ->options([
                    'waiting' => 'In attesa',
                    'called' => 'Chiamato',
                    'in_progress' => 'In servizio',
                    'completed' => 'Completato',
                    'abandoned' => 'Abbandonato',
                ])
                ->required()
                ->label('Stato'),
            Forms\Components\Select::make('service_id')
                ->relationship('service', 'name')
                ->required()
                ->label('Servizio iniziale'),
            Forms\Components\Select::make('current_service_id')
                ->options(fn () => Service::pluck('name', 'id')->toArray())
                ->required()
                ->label('Servizio corrente'),
            Forms\Components\DateTimePicker::make('first_called_at')
                ->label('Prima chiamata'),
            Forms\Components\DateTimePicker::make('completed_at')
                ->label('Completato il'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->searchable()
                    ->label('Numero Ticket'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'info',
                        'success' => 'service',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'info' => 'Informazioni',
                        'service' => 'Servizio',
                    })
                    ->label('Tipo'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'waiting',
                        'primary' => 'called',
                        'success' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'abandoned',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'waiting' => 'In attesa',
                        'called' => 'Chiamato',
                        'in_progress' => 'In servizio',
                        'completed' => 'Completato',
                        'abandoned' => 'Abbandonato',
                    })
                    ->label('Stato'),
                Tables\Columns\TextColumn::make('currentService.name')
                    ->label('Servizio corrente'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Creato il'),
                Tables\Columns\TextColumn::make('first_called_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Prima chiamata'),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Completato il'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'waiting' => 'In attesa',
                        'called' => 'Chiamato',
                        'in_progress' => 'In servizio',
                        'completed' => 'Completato',
                        'abandoned' => 'Abbandonato',
                    ])
                    ->label('Stato'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'info' => 'Informazioni',
                        'service' => 'Servizio',
                    ])
                    ->label('Tipo'),
                Tables\Filters\SelectFilter::make('current_service_id')
                    ->relationship('currentService', 'name')
                    ->label('Servizio corrente'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Da'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('A'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->label('Data di creazione'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Action per visualizzare i log del ticket
                Tables\Actions\Action::make('viewLogs')
                ->label('Visualizza Log')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info')
                ->modalHeading(fn (Ticket $record) => "Cronologia Ticket #{$record->ticket_number}")
                ->modalWidth('5xl')
                ->modalContent(function (Ticket $record): View {
                    // Carica i log con le relazioni
                    $logs = $record->logs()
                        ->with(['station'])
                        ->orderBy('created_at', 'desc')
                        ->get();
                    
                    return view('filament.resources.ticket-resource.log-modal', [
                        'logs' => $logs,
                        'ticket' => $record,
                    ]);
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Bulk action per cambiare lo stato di più ticket
                    Tables\Actions\BulkAction::make('bulkChangeStatus')
                        ->label('Cambia stato')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Nuovo stato')
                                ->options([
                                    'waiting' => 'In attesa',
                                    'called' => 'Chiamato',
                                    'in_progress' => 'In servizio',
                                    'completed' => 'Completato',
                                    'abandoned' => 'Abbandonato',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('note')
                                ->label('Nota (opzionale)')
                                ->placeholder('Aggiungi una nota per spiegare il motivo del cambio di stato')
                                ->maxLength(1000),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = $records->count();
                            $newStatus = $data['status'];
                            $now = now();
                            
                            // Prepariamo i log per l'inserimento in blocco
                            $logs = [];
                            
                            foreach ($records as $record) {
                                $oldStatus = $record->status;
                                
                                // Se lo stato è completato o abbandonato, impostiamo completed_at
                                if (($newStatus === 'completed' || $newStatus === 'abandoned') && $record->completed_at === null) {
                                    $record->completed_at = $now;
                                }
                                
                                $record->status = $newStatus;
                                $record->save();
                                
                                // Prepara il log
                                $logs[] = [
                                    'ticket_id' => $record->id,
                                    'status_from' => $oldStatus,
                                    'status_to' => $newStatus,
                                    'station_id' => null, // Modifica manuale dall'admin
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }
                            
                            // Inserimento in blocco dei log
                            if (!empty($logs)) {
                                \App\Models\TicketLog::insert($logs);
                            }
                            
                            $statusLabels = [
                                'waiting' => 'In attesa',
                                'called' => 'Chiamato',
                                'in_progress' => 'In servizio',
                                'completed' => 'Completato',
                                'abandoned' => 'Abbandonato',
                            ];
                            
                            Notification::make()
                                ->title('Stato aggiornato')
                                ->body("{$count} ticket aggiornati allo stato \"{$statusLabels[$newStatus]}\".")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }

    
    /**
     * Restituisce l'etichetta leggibile per uno stato
     */
    public static function getStatusLabel(?string $status): string
    {
        if (!$status) {
            return 'N/A';
        }
        
        return match ($status) {
            'waiting' => 'In attesa',
            'called' => 'Chiamato',
            'in_progress' => 'In servizio',
            'completed' => 'Completato',
            'abandoned' => 'Abbandonato',
            default => $status,
        };
    }

    /**
     * Restituisce il colore del badge per uno stato
     */
    public static function getStatusBadgeColor(?string $status): string
    {
        if (!$status) {
            return 'gray';
        }
        
        return match ($status) {
            'waiting' => 'yellow',
            'called' => 'blue',
            'in_progress' => 'green',
            'completed' => 'green',
            'abandoned' => 'red',
            default => 'gray',
        };
    }
}
