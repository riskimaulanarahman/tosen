<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'check_in_selfie_path')) {
                $table->string('check_in_selfie_path')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('attendances', 'check_out_selfie_path')) {
                $table->string('check_out_selfie_path')->nullable()->after('check_in_selfie_path');
            }
            if (!Schema::hasColumn('attendances', 'check_in_thumbnail_path')) {
                $table->string('check_in_thumbnail_path')->nullable()->after('check_out_selfie_path');
            }
            if (!Schema::hasColumn('attendances', 'check_out_thumbnail_path')) {
                $table->string('check_out_thumbnail_path')->nullable()->after('check_in_thumbnail_path');
            }
            if (!Schema::hasColumn('attendances', 'attendance_status')) {
                $table->enum('attendance_status', ['on_time', 'late', 'early_checkout', 'overtime', 'absent', 'holiday', 'leave'])->default('on_time')->after('check_out_thumbnail_path');
            }
            if (!Schema::hasColumn('attendances', 'late_minutes')) {
                $table->integer('late_minutes')->nullable()->after('attendance_status');
            }
            if (!Schema::hasColumn('attendances', 'early_checkout_minutes')) {
                $table->integer('early_checkout_minutes')->nullable()->after('late_minutes');
            }
            if (!Schema::hasColumn('attendances', 'overtime_minutes')) {
                $table->integer('overtime_minutes')->nullable()->after('early_checkout_minutes');
            }
            if (!Schema::hasColumn('attendances', 'work_duration_minutes')) {
                $table->integer('work_duration_minutes')->nullable()->after('overtime_minutes');
            }
            if (!Schema::hasColumn('attendances', 'attendance_score')) {
                $table->decimal('attendance_score', 5, 2)->default(100.00)->after('work_duration_minutes');
            }
            if (!Schema::hasColumn('attendances', 'is_paid_leave')) {
                $table->boolean('is_paid_leave')->default(false)->after('attendance_score');
            }
            if (!Schema::hasColumn('attendances', 'leave_reason')) {
                $table->text('leave_reason')->nullable()->after('is_paid_leave');
            }
            if (!Schema::hasColumn('attendances', 'computed_at')) {
                $table->timestamp('computed_at')->nullable()->after('leave_reason');
            }
            if (!Schema::hasColumn('attendances', 'selfie_deleted_at')) {
                $table->timestamp('selfie_deleted_at')->nullable()->after('computed_at');
            }
            if (!Schema::hasColumn('attendances', 'check_in_file_size')) {
                $table->integer('check_in_file_size')->nullable()->after('selfie_deleted_at');
            }
            if (!Schema::hasColumn('attendances', 'check_out_file_size')) {
                $table->integer('check_out_file_size')->nullable()->after('check_in_file_size');
            }
        });

        // Add missing operational columns to outlets if not exists
        Schema::table('outlets', function (Blueprint $table) {
            if (!Schema::hasColumn('outlets', 'work_days')) {
                $table->json('work_days')->nullable()->after('early_checkout_tolerance');
            }
            if (!Schema::hasColumn('outlets', 'timezone')) {
                $table->string('timezone', 50)->default('Asia/Jakarta')->after('work_days');
            }
            if (!Schema::hasColumn('outlets', 'grace_period_minutes')) {
                $table->integer('grace_period_minutes')->default(5)->after('timezone');
            }
            if (!Schema::hasColumn('outlets', 'overtime_threshold_minutes')) {
                $table->integer('overtime_threshold_minutes')->default(60)->after('grace_period_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_selfie_path',
                'check_out_selfie_path',
                'check_in_thumbnail_path',
                'check_out_thumbnail_path',
                'attendance_status',
                'late_minutes',
                'early_checkout_minutes',
                'overtime_minutes',
                'work_duration_minutes',
                'attendance_score',
                'is_paid_leave',
                'leave_reason',
                'computed_at',
                'selfie_deleted_at',
                'check_in_file_size',
                'check_out_file_size'
            ]);
        });

        Schema::table('outlets', function (Blueprint $table) {
            $columns = ['work_days', 'timezone', 'grace_period_minutes', 'overtime_threshold_minutes'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('outlets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
