<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class ServiceCount extends ChartWidget
{
    use HasWidgetShield;
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected static ?string $heading = 'Service Chart';

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $start = Carbon::parse($this->filters['start'] ?? now()->startOfYear())->startOfDay();
        $end = Carbon::parse($this->filters['end'] ?? now()->endOfYear())->endOfDay();

        $isSuperAdmin = request()->user()->hasRole(Utils::getSuperAdminName());
        $IDs = $isSuperAdmin ? $this->filters['user'] : [auth()->id()];
        $tasks = Task::with('service')
            ->whereBetween('created_at', [$start, $end])
            ->when($IDs, fn ($query) => $query->whereIn('user_id', $IDs))
            ->when($isSuperAdmin && $this->filters['service'], function ($query) {
                return $query->whereIn('service_id', $this->filters['service']);
            })
            ->get();

        return [
            'labels' => $tasks->pluck('service.name')->unique()->values(),
            'datasets' => [
                [
                    'data' => $tasks->groupBy('service_id')->map->count()->values(),
                    'backgroundColor' => $tasks->groupBy('service_id')->map(fn () => $this->getRandomColor())->values(),
                    'hoverOffset' => 4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getRandomColor(): string
    {
        return '#'.str_pad(dechex(random_int(0x000000, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }
}
