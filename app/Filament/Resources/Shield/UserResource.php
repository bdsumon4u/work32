<?php

namespace App\Filament\Resources\Shield;

use App\Filament\Resources\Shield\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Contracts\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Shield';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Personal Information')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->key('name')
                                ->autofocus()
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->required()
                                ->unique(static::getModel(), 'email', ignoreRecord: auth()->user()?->email)
                                ->maxLength(255)
                                ->debounce(),
                        ])
                        ->columns(['lg' => 1 + ($form->getOperation() === 'edit')]),
                    Forms\Components\Section::make('Change Password')
                        ->visibleOn('edit')
                        ->schema([
                            Forms\Components\TextInput::make('password')
                                ->label('New password')
                                ->password()
                                ->confirmed()
                                ->minLength(8),

                            Forms\Components\TextInput::make('password_confirmation')
                                ->password()
                                ->dehydrated(false),
                        ])
                        ->columns(['lg' => 2]),
                ])
                    ->columnSpan(['lg' => 2]),
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Authentication / Authorization')
                        ->schema([
                            Forms\Components\Select::make('roles')
                                ->relationship('roles', 'name')
                                ->getOptionLabelFromRecordUsing(fn (Role $record) => Str::headline($record->name))
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->native(false)
                                ->required(),
                            Forms\Components\TextInput::make('password')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->visibleOn('create'),
                        ]),
                ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->listWithLineBreaks()
                    ->bulleted(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
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
}
