<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    use HasWidgetShield;
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $start = Carbon::parse($this->filters['start'] ?? now()->startOfYear())->startOfDay();
        $end = Carbon::parse($this->filters['end'] ?? now()->endOfYear())->endOfDay();

        $isSuperAdmin = request()->user()->hasRole(Utils::getSuperAdminName());
        $IDs = $isSuperAdmin ? $this->filters['user'] : [auth()->id()];
        $query = Task::query()
            ->whereBetween('created_at', [$start, $end])
            ->when($IDs, fn ($query) => $query->whereIn('user_id', $IDs))
            ->when($isSuperAdmin && $this->filters['service'], function ($query) {
                return $query->whereIn('service_id', $this->filters['service']);
            });

        return [
            Stat::make('Total Tasks', $query->count()),
            Stat::make('Total USD', $query->sum('amount_usd')),
            Stat::make('Total BDT', $query->sum('amount_bdt')),
        ];
    }
}
