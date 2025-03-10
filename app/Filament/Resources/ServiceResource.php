<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Configurazione';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nome'),
                Forms\Components\TextInput::make('ticket_prefix')
                    ->required()
                    ->length(1)
                    ->alpha()
                    ->label('Prefisso Ticket')
                    ->helperText('Una lettera singola, es: A, B, C, ...'),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->label('Descrizione'),
                Forms\Components\ColorPicker::make('color_code')
                    ->label('Colore')
                    ->helperText('Scegli un colore per questo servizio')
                    ->default('#3B82F6'), // Blue-500 di default    
                Forms\Components\TextInput::make('icon_class')
                    ->label('Icona Font Awesome')
                    ->helperText('Inserisci la classe dell\'icona Font Awesome (es: fa-users, fa-building, ecc.)')
                    ->default('fa-ticket')
                    ->suffixIcon('heroicon-o-information-circle'),
                Forms\Components\Toggle::make('is_reception')
                    ->required()
                    ->label('Servizio Reception'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nome'),
                Tables\Columns\TextColumn::make('ticket_prefix')
                    ->label('Prefisso'),
                Tables\Columns\ColorColumn::make('color_code')
                    ->label('Colore'),    
                    Tables\Columns\ViewColumn::make('icon_class')
                    ->label('Icona')
                    ->view('filament.tables.columns.icon-preview'),
                    Tables\Columns\IconColumn::make('is_reception')
                    ->boolean()
                    ->label('Reception'),
                Tables\Columns\TextColumn::make('stations_count')
                    ->counts('stations')
                    ->label('Postazioni'),
                Tables\Columns\TextColumn::make('waiting_count')
                    ->label('In attesa'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Creato il'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_reception')
                    ->label('Solo Reception'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
