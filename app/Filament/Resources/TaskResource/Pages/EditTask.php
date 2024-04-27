<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('verify')
                ->label('Verify')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['is_verified' => true]))
                ->visible(fn () => request()->user()->hasRole(Utils::getSuperAdminName()) && ! $this->record->is_verified),
            Actions\DeleteAction::make(),
            Actions\CreateAction::make()
                ->label('New task')
                ->url(fn () => CreateTask::getUrl()),
        ];
    }
}
