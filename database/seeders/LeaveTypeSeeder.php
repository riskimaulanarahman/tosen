<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'AL',
                'max_days_per_year' => 12,
                'requires_approval' => true,
                'is_paid' => true,
                'description' => 'Regular annual leave for personal reasons',
                'color_code' => '#3B82F6',
                'is_active' => true,
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SL',
                'max_days_per_year' => 10,
                'requires_approval' => true,
                'is_paid' => true,
                'description' => 'Leave due to illness or medical reasons',
                'color_code' => '#10B981',
                'is_active' => true,
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'ML',
                'max_days_per_year' => 90,
                'requires_approval' => true,
                'is_paid' => true,
                'description' => 'Leave for pregnancy and childbirth',
                'color_code' => '#EC4899',
                'is_active' => true,
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'PL',
                'max_days_per_year' => 7,
                'requires_approval' => true,
                'is_paid' => true,
                'description' => 'Leave for fathers during childbirth',
                'color_code' => '#6366F1',
                'is_active' => true,
            ],
            [
                'name' => 'Unpaid Leave',
                'code' => 'UL',
                'max_days_per_year' => 5,
                'requires_approval' => true,
                'is_paid' => false,
                'description' => 'Leave without pay for personal reasons',
                'color_code' => '#6B7280',
                'is_active' => true,
            ],
            [
                'name' => 'Emergency Leave',
                'code' => 'EL',
                'max_days_per_year' => 3,
                'requires_approval' => false,
                'is_paid' => true,
                'description' => 'Emergency leave for urgent situations',
                'color_code' => '#EF4444',
                'is_active' => true,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::create($leaveType);
        }
    }
}
