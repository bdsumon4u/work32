<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;

class Performance extends ChartWidget
{
    use HasWidgetShield;
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected static ?string $heading = 'Performance Chart';

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $start = Carbon::parse($this->filters['start'] ?? now()->startOfYear())->startOfDay();
        $end = Carbon::parse($this->filters['end'] ?? now()->endOfYear())->endOfDay();

        $timely = function (Trend $trend) use ($start, $end) {
            if ($start->diffInYears($end) >= 1) {
                return $trend->perYear();
            }
            if ($start->diffInMonths($end) >= 1) {
                return $trend->perMonth();
            }
            if ($start->diffInDays($end) >= 1) {
                return $trend->perDay();
            }

            return $trend->perHour();
        };

        $isSuperAdmin = request()->user()->hasRole(Utils::getSuperAdminName());
        $IDs = $isSuperAdmin ? $this->filters['user'] : [auth()->id()];
        $users = User::query()
            ->when($IDs, fn ($query) => $query->whereIn('id', $IDs))
            ->get()->map(fn (User $user) => [
                'label' => $user->name,
                'data' => $timely(
                    Trend::query(
                        Task::query()
                            ->when($isSuperAdmin && $this->filters['service'], function ($query) {
                                return $query->whereIn('service_id', $this->filters['service']);
                            })
                            ->whereBelongsTo($user)
                    )
                        ->between($start, $end)
                )
                    ->count(),
            ]);

        return [
            'datasets' => $users->map(fn (array $user) => [
                'label' => $user['label'],
                'data' => $user['data']->map(fn (TrendValue $value) => $value->aggregate),
                'backgroundColor' => $this->getRandomColor(),
                'borderColor' => $this->getRandomColor(),
            ]),
            'labels' => $users->map->data->flatten()->map->date->unique()->sort()->map(function ($date) use ($start, $end) {
                $carbon = Carbon::parse($date);

                if ($start->diffInYears($end) >= 1) {
                    return $carbon->format('Y');
                }
                if ($start->diffInMonths($end) >= 1) {
                    return $carbon->format('M Y');
                }
                if ($start->diffInDays($end) >= 1) {
                    return $carbon->format('d M');
                }

                return $carbon->format('h:i A');
            }),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getRandomColor(): string
    {
        return '#'.str_pad(dechex(random_int(0x000000, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }
}
