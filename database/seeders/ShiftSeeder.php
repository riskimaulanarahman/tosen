<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Morning Shift',
                'start_time' => '07:00',
                'end_time' => '15:00',
                'break_duration' => 60,
                'is_overnight' => false,
                'color_code' => '#10B981',
                'description' => 'Regular morning shift with 1 hour break',
                'is_active' => true,
            ],
            [
                'name' => 'Afternoon Shift',
                'start_time' => '15:00',
                'end_time' => '23:00',
                'break_duration' => 60,
                'is_overnight' => false,
                'color_code' => '#3B82F6',
                'description' => 'Regular afternoon shift with 1 hour break',
                'is_active' => true,
            ],
            [
                'name' => 'Evening Shift',
                'start_time' => '23:00',
                'end_time' => '07:00',
                'break_duration' => 60,
                'is_overnight' => true,
                'color_code' => '#8B5CF6',
                'description' => 'Overnight shift with 1 hour break',
                'is_active' => true,
            ],
            [
                'name' => 'Night Shift',
                'start_time' => '19:00',
                'end_time' => '03:00',
                'break_duration' => 60,
                'is_overnight' => true,
                'color_code' => '#6366F1',
                'description' => 'Night shift with 1 hour break',
                'is_active' => true,
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::create($shift);
        }
    }
}
