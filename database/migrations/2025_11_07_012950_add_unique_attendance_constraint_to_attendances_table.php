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
            // Add date column for unique constraint (derived from check_in_time)
            $table->date('check_in_date')->after('check_in_time')->index();
            
            // Add composite index for performance optimization
            $table->index(['user_id', 'check_in_time'], 'attendances_user_date_index');
            
            // Add unique constraint to prevent duplicate check-ins on the same day
            $table->unique(['user_id', 'check_in_date'], 'attendances_user_date_unique');
        });
        
        // Update existing records to set check_in_date from check_in_time (after table modification)
        \DB::statement('UPDATE attendances SET check_in_date = DATE(check_in_time) WHERE check_in_date IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop indexes and constraints
            $table->dropUnique('attendances_user_date_unique');
            $table->dropIndex('attendances_user_date_index');
            
            // Drop the check_in_date column
            $table->dropColumn('check_in_date');
        });
    }
};
