<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Amministrazione';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Nome'),
            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('username')
                ->required()
                ->maxLength(255)
                ->label('Nome utente')
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')
                ->password()
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation): bool => $operation === 'create')
                ->label('Password'),
            Forms\Components\Select::make('role')
                ->options([
                    'superadmin' => 'Super Admin',
                    'admin' => 'Amministratore',
                ])
                ->required()
                ->label('Ruolo')
                ->visible(fn (): bool => auth()->user()->isSuperAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->label('Nome'),
                Tables\Columns\TextColumn::make('username')
                ->searchable(),
                Tables\Columns\TextColumn::make('email')
                ->searchable(),
                Tables\Columns\TextColumn::make('role')
                ->badge()
                ->colors([
                    'danger' => 'superadmin',
                    'warning' => 'admin',
                ])
                ->label('Ruolo'),
                Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label('Creato il')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                ->options([
                    'superadmin' => 'Super Admin',
                    'admin' => 'Amministratore',
                ])
                ->label('Ruolo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->visible(fn (User $record): bool => 
                    auth()->user()->isSuperAdmin() && $record->id !== auth()->id()
                ),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Gli utenti non superadmin possono vedere solo operatori e admin (non altri superadmin)
        if (!auth()->user()->isSuperAdmin()) {
            $query->where('role', '!=', 'superadmin');
        }
        
        return $query;
    }
}
