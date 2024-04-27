<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Task::factory()
            ->count(100)
            ->state(function (array $attributes) {
                return [
                    'user_id' => User::inRandomOrder()->first()->id,
                    'service_id' => Service::inRandomOrder()->first()->id,
                    'created_at' => $date = now()->subDays(mt_rand(1, 100)),
                    'updated_at' => $date,
                ];
            })
            ->create();
    }
}
