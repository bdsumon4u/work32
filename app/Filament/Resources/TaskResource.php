<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Information')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('service_id')
                                ->relationship('service', 'name')
                                ->label('Service')
                                ->searchable()
                                ->required()
                                ->preload()
                                ->createOptionForm(request()->user()->hasRole(Utils::getSuperAdminName()) ? [
                                    Forms\Components\TextInput::make('name')
                                        ->required(),
                                ] : null)
                                ->createOptionModalHeading('Create New Service')
                                ->editOptionForm(request()->user()->hasRole(Utils::getSuperAdminName()) ? [
                                    Forms\Components\TextInput::make('name')
                                        ->required(),
                                ] : null)
                                ->editOptionModalHeading('Edit Service'),
                            Forms\Components\TextInput::make('invoice_id')
                                ->label('Invoice ID')
                                ->required()
                                ->integer()
                                ->minValue(1),
                            Forms\Components\TextInput::make('amount_usd')
                                ->dehydrateStateUsing(fn ($state) => $state ?? 0)
                                ->label('Amount in USD')
                                ->prefix('USD')
                                ->numeric()
                                ->minValue(0),
                            Forms\Components\TextInput::make('amount_bdt')
                                ->label('Amount in BDT')
                                ->prefix('BDT')
                                ->required()
                                ->integer()
                                ->minValue(0),
                            Forms\Components\TextInput::make('payment_method')
                                ->label('Payment Method')
                                ->columnSpanFull()
                                ->required(),
                        ])
                            ->columns(2),
                        Forms\Components\Group::make([
                            Forms\Components\MarkdownEditor::make('description')
                                ->label('Description')
                                ->hint('Optional brief description of the task'),
                        ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (! request()->user()->hasRole(Utils::getSuperAdminName())) {
                    $query->where('user_id', auth()->id());
                }

                return $query;
            })
            ->groups([
                Tables\Grouping\Group::make('service.name')
                    ->collapsible(),
                Tables\Grouping\Group::make('user.name')
                    ->label('Person')
                    ->collapsible(),
                Tables\Grouping\Group::make('created_at')
                    ->label('Date')
                    ->date()
                    ->collapsible(),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Time')
                    ->searchable()
                    ->sortable()
                    ->date() // ->dateTime()
                    ->tooltip(fn ($record) => $record->updated_at->format(
                        Table::$defaultTimeDisplayFormat,
                    )),
                Tables\Columns\TextColumn::make('time')
                    ->label('Time')
                    ->toggleable()
                    ->getStateUsing(fn ($record) => $record->created_at->format('h:i A')),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Person')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_id')
                    ->url(fn ($record) => 'https://portal.cyber32.com/gatepass/invoices.php?action=edit&id='.$record->invoice_id)
                    ->openUrlInNewTab()
                    ->label('Invoice ID')
                    ->iconPosition('after')
                    ->icon('heroicon-o-link')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_usd')
                    ->label('USD')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->summarize(Sum::make()->label('')),
                Tables\Columns\TextColumn::make('amount_bdt')
                    ->label('BDT')
                    ->searchable()
                    ->sortable()
                    ->summarize(Sum::make()->label('')),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                static::verifiedColumn(),
            ])
            ->striped()
            ->recordUrl(null)
            ->defaultSort('id', 'desc')
            ->paginationPageOptions([25, 50, 100])
            ->filters(array_filter([
                request()->user()->hasRole(Utils::getSuperAdminName())
                    ? SelectFilter::make('user')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->multiple()
                        ->preload()
                    : null,
                SelectFilter::make('service')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->native(false),
                        DatePicker::make('created_until')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data) {
                        if ($data['created_from']) {
                            $query->where('created_at', '>=', $data['created_from']);
                        }
                        if ($data['created_until']) {
                            $query->where('created_at', '<=', $data['created_until']);
                        }
                    }),
            ]))
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('verify')
                        ->label('Verify selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => request()->user()->hasRole(Utils::getSuperAdminName()))
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_verified' => true]));

                            Notification::make()
                                ->title('Verified')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            if (! request()->user()->hasRole(Utils::getSuperAdminName())) {
                                $filtered = $records->filter(function ($record) use (&$body) {
                                    return $record->user_id === auth()->id()
                                        && ! $record->is_verified;
                                });
                            }

                            $filtered->each(fn ($record) => $record->delete());

                            $body = $filtered->count() != $records->count() ? 'Some tasks were not deleted.' : null;

                            Notification::make()
                                ->title('Deleted')
                                ->body($body)
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    private static function verifiedColumn()
    {
        if (request()->user()->hasRole(Utils::getSuperAdminName())) {
            return Tables\Columns\ToggleColumn::make('is_verified')
                ->label('Status')
                ->searchable()
                ->sortable()
                ->toggleable()
                ->alignCenter()
                ->onIcon('heroicon-o-check-circle')
                ->offIcon('heroicon-o-x-circle')
                ->onColor('success')
                ->offColor('danger')
                ->tooltip(fn ($record) => $record->is_verified ? 'Verified' : 'Pending')
                ->afterStateUpdated(function ($record) {
                    Notification::make()
                        ->title($record->is_verified ? 'Verified' : 'Unverified')
                        ->success()
                        ->send();
                });
        }

        return Tables\Columns\IconColumn::make('is_verified')
            ->label('Status')
            ->searchable()
            ->sortable()
            ->toggleable()
            ->alignCenter()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-arrow-path-rounded-square')
            ->trueColor('success')
            ->falseColor('danger')
            ->tooltip(fn ($record) => $record->is_verified ? 'Verified' : 'Pending');
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
