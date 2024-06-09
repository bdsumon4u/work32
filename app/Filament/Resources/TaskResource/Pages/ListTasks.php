<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'today' => Tab::make('Today')
                ->modifyQueryUsing(function ($query) {
                    $query->whereBetween('created_at', [
                        now()->startOfDay(),
                        now()->endOfDay(),
                    ]);
                }),
            'yesterday' => Tab::make('Yesterday')
                ->modifyQueryUsing(function ($query) {
                    $query->whereBetween('created_at', [
                        now()->subDay()->startOfDay(),
                        now()->subDay()->endOfDay(),
                    ]);
                }),
            'this_month' => Tab::make('This Month')
                ->modifyQueryUsing(function ($query) {
                    $query->whereBetween('created_at', [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ]);
                }),
        ];
    }
}
