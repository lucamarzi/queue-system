<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StationResource\Pages;
use App\Filament\Resources\StationResource\RelationManagers;
use App\Models\Station;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class StationResource extends Resource
{
    protected static ?string $model = Station::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Configurazione';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Nome'),
            Forms\Components\Textarea::make('description')
                ->maxLength(65535)
                ->label('Descrizione'),
            Forms\Components\Select::make('service_id')
                ->relationship('service', 'name')
                ->required()
                ->label('Servizio'),
            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Attiva',
                    'busy' => 'Occupata',
                    'paused' => 'In pausa',
                    'closed' => 'Chiusa',
                ])
                ->required()
                ->default('closed')
                ->label('Stato'),
            Forms\Components\Toggle::make('is_locked')
                ->required()
                ->default(false)
                ->label('Bloccata'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nome'),
                Tables\Columns\TextColumn::make('service.name')
                    ->sortable()
                    ->label('Servizio'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'busy',
                        'warning' => 'paused',
                        'gray' => 'closed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Attiva',
                        'busy' => 'Occupata',
                        'paused' => 'In pausa',
                        'closed' => 'Chiusa',
                    })
                    ->label('Stato'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Operatore'),
                Tables\Columns\IconColumn::make('is_locked')
                    ->boolean()
                    ->label('Bloccata'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Creata il'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_id')
                ->relationship('service', 'name')
                ->label('Servizio'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Attiva',
                        'paused' => 'In pausa',
                        'closed' => 'Chiusa',
                    ])
                    ->label('Stato'),
                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Bloccata'),
                ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Action per cambiare lo stato di una singola postazione
                Tables\Actions\Action::make('changeStatus')
                    ->label('Cambia stato')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Nuovo stato')
                            ->options([
                                'active' => 'Attiva',
                                'busy' => 'Occupata',
                                'paused' => 'In pausa',
                                'closed' => 'Chiusa',
                            ])
                            ->required()
                            ->default(fn (Station $record): string => $record->status),
                    ])
                    ->action(function (Station $record, array $data): void {
                        $oldStatus = $record->status;
                        $record->update(['status' => $data['status']]);
                        
                        $statusLabels = [
                            'active' => 'Attiva',
                            'paused' => 'In pausa',
                            'closed' => 'Chiusa',
                        ];
                        
                        Notification::make()
                            ->title('Stato aggiornato')
                            ->body("La postazione {$record->name} è stata cambiata da \"{$statusLabels[$oldStatus]}\" a \"{$statusLabels[$data['status']]}\".")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('unlock')
                ->label('Sblocca')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->visible(fn (Station $record): bool => $record->is_locked)
                ->action(function (Station $record): void {
                    $record->update(['is_locked' => false]);
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Bulk action per cambiare lo stato di multiple postazioni
                    Tables\Actions\BulkAction::make('bulkChangeStatus')
                        ->label('Cambia stato')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Nuovo stato')
                                ->options([
                                    'active' => 'Attiva',
                                    'busy' => 'Occupata',
                                    'paused' => 'In pausa',
                                    'closed' => 'Chiusa',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = $records->count();
                            
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                            
                            $statusLabels = [
                                'active' => 'Attiva',
                                'paused' => 'In pausa',
                                'closed' => 'Chiusa',
                            ];
                            
                            Notification::make()
                                ->title('Stato aggiornato')
                                ->body("{$count} postazioni aggiornate allo stato \"{$statusLabels[$data['status']]}\".")
                                ->success()
                                ->send();
                        }),
                        // Bulk action per bloccare multiple postazioni
                    Tables\Actions\BulkAction::make('bulkLock')
                    ->label('Blocca postazioni')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $count = $records->count();
                        
                        foreach ($records as $record) {
                            $record->update(['is_locked' => true]);
                        }
                        
                        Notification::make()
                            ->title('Postazioni bloccate')
                            ->body("{$count} postazioni sono state bloccate.")
                            ->success()
                            ->send();
                    }),
                
                // Bulk action per sbloccare multiple postazioni
                Tables\Actions\BulkAction::make('bulkUnlock')
                    ->label('Sblocca postazioni')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $count = $records->count();
                        
                        foreach ($records as $record) {
                            $record->update(['is_locked' => false]);
                        }
                        
                        Notification::make()
                            ->title('Postazioni sbloccate')
                            ->body("{$count} postazioni sono state sbloccate.")
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
            'index' => Pages\ListStations::route('/'),
            'create' => Pages\CreateStation::route('/create'),
            'edit' => Pages\EditStation::route('/{record}/edit'),
        ];
    }
}
