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
            // Add composite index for user_id and check_in_date for pivot table queries
            $table->index(['user_id', 'check_in_date'], 'idx_user_checkin_date');
            
            // Add index for attendance_status for filtering
            $table->index('attendance_status', 'idx_attendance_status');
            
            // Add index for created_at for date range queries
            $table->index('created_at', 'idx_created_at');
        });

        Schema::table('users', function (Blueprint $table) {
            // Add composite index for role and outlet_id for employee queries
            $table->index(['role', 'outlet_id'], 'idx_role_outlet');
        });

        Schema::table('outlets', function (Blueprint $table) {
            // Add index for owner_id for owner queries
            $table->index('owner_id', 'idx_owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_user_checkin_date');
            $table->dropIndex('idx_attendance_status');
            $table->dropIndex('idx_created_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_role_outlet');
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->dropIndex('idx_owner_id');
        });
    }
};
