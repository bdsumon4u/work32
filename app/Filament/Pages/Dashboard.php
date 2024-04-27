<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TaskResource\Pages\CreateTask;
use App\Models\Service;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as DashboardPage;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Contracts\View\View;

class Dashboard extends DashboardPage
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        if (! request()->user()->hasRole(Utils::getSuperAdminName())) {
            return $form;
        }

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('user')
                            ->options(User::pluck('name', 'id')->toArray())
                            ->searchable()
                            ->multiple(),
                        Select::make('service')
                            ->options(Service::pluck('name', 'id')->toArray())
                            ->searchable()
                            ->multiple(),
                        DatePicker::make('start')
                            ->label('Start Date')
                            ->native(false),
                        DatePicker::make('end')
                            ->label('End Date')
                            ->native(false),
                    ])
                    ->columns(4),
            ]);
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New task')
                ->url(fn () => CreateTask::getUrl()),
        ];
    }

    public function getFooter(): ?View
    {
        return view('filament.pages.dashboard-footer');
    }
}
