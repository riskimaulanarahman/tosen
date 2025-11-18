<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkInDate = Carbon::instance(fake()->dateTimeBetween('-1 month', 'now'));
        $checkInTime = (clone $checkInDate)->setTime(8, 0, 0);
        $checkOutTime = (clone $checkInTime)->addHours(9)->addMinutes(rand(0, 30));
        
        return [
            'user_id' => User::factory(),
            'outlet_id' => 1, // Will be overridden in tests
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'check_in_date' => $checkInDate->format('Y-m-d'),
            'check_in_latitude' => fake()->latitude(-8.5, -8.7),
            'check_in_longitude' => fake()->longitude(115.1, 115.3),
            'check_out_latitude' => fake()->latitude(-8.5, -8.7),
            'check_out_longitude' => fake()->longitude(115.1, 115.3),
            'check_in_accuracy' => fake()->randomFloat(5, 10, 50),
            'check_out_accuracy' => fake()->randomFloat(5, 10, 50),
            'status' => 'checked_out',
            'notes' => fake()->optional(0.3)->sentence(),
            'attendance_status' => fake()->randomElement(['on_time', 'late', 'early_checkout', 'overtime']),
            'late_minutes' => fake()->optional(0.7)->numberBetween(1, 60),
            'early_checkout_minutes' => fake()->optional(0.3)->numberBetween(1, 120),
            'overtime_minutes' => fake()->optional(0.4)->numberBetween(1, 180),
            'work_duration_minutes' => fake()->numberBetween(480, 600), // 8-10 hours
            'attendance_score' => fake()->randomFloat(60, 100, 2),
            'is_paid_leave' => fake()->boolean(20), // 20% chance
            'leave_reason' => fake()->optional(0.2)->randomElement(['Sakit', 'Cuti tahunan', 'Cuti melahirkan', 'Izin pribadi']),
            'computed_at' => now(),
            'has_check_in_selfie' => true,
            'has_check_out_selfie' => true,
            'check_in_file_size' => fake()->numberBetween(50000, 500000), // 50KB - 500KB
            'check_out_file_size' => fake()->numberBetween(50000, 500000),
        ];
    }

    /**
     * Indicate that the attendance is on time.
     */
    public function onTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_status' => 'on_time',
            'late_minutes' => 0,
        ]);
    }

    /**
     * Indicate that the attendance is late.
     */
    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_status' => 'late',
            'late_minutes' => fake()->numberBetween(5, 60),
        ]);
    }

    /**
     * Indicate that the attendance has early checkout.
     */
    public function earlyCheckout(): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_status' => 'early_checkout',
            'early_checkout_minutes' => fake()->numberBetween(15, 120),
        ]);
    }

    /**
     * Indicate that the attendance has overtime.
     */
    public function overtime(): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_status' => 'overtime',
            'overtime_minutes' => fake()->numberBetween(30, 240),
        ]);
    }

    /**
     * Indicate that the attendance is a leave.
     */
    public function leave(): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_status' => 'leave',
            'is_paid_leave' => true,
            'leave_reason' => fake()->randomElement(['Sakit', 'Cuti tahunan', 'Cuti melahirkan', 'Izin pribadi']),
        ]);
    }

    /**
     * Create attendance for a specific date.
     */
    public function forDate($date): static
    {
        $checkInTime = \Carbon\Carbon::parse($date)->setTime(8, 0, 0);
        $checkOutTime = (clone $checkInTime)->addHours(9)->addMinutes(rand(0, 30));
        
        return $this->state(fn (array $attributes) => [
            'check_in_date' => $date,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
        ]);
    }

    /**
     * Create attendance for a specific user.
     */
    public function forUser($userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Create attendance for a specific outlet.
     */
    public function forOutlet($outletId): static
    {
        return $this->state(fn (array $attributes) => [
            'outlet_id' => $outletId,
        ]);
    }
}
