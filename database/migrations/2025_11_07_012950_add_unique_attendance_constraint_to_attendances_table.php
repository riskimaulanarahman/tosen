<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'check_in_date')) {
                // Store derived check-in date for reporting needs
                $table->date('check_in_date')->nullable()->after('check_in_time');
                $table->index('check_in_date', 'attendances_check_in_date_index');
                return;
            }

            if (!Schema::hasIndex('attendances', 'attendances_check_in_date_index')) {
                $table->index('check_in_date', 'attendances_check_in_date_index');
            }
        });
        
        if (Schema::hasColumn('attendances', 'check_in_date')) {
            // Update existing records to set check_in_date from check_in_time (after table modification)
            \DB::statement('UPDATE attendances SET check_in_date = DATE(check_in_time) WHERE check_in_date IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = Schema::getConnection()->getDriverName();

        if ($connection === 'sqlite') {
            $indexes = [
                'attendances_check_in_date_index',
            ];

            foreach ($indexes as $index) {
                DB::statement(sprintf('DROP INDEX IF EXISTS "%s"', $index));
            }

            if (Schema::hasColumn('attendances', 'check_in_date')) {
                Schema::table('attendances', function (Blueprint $table) {
                    $table->dropColumn('check_in_date');
                });
            }

            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            // Drop indexes and constraints only if they exist
            if (Schema::hasIndex('attendances', 'attendances_check_in_date_index')) {
                $table->dropIndex('attendances_check_in_date_index');
            }
           
            // Drop the check_in_date column if it exists
            if (Schema::hasColumn('attendances', 'check_in_date')) {
                $table->dropColumn('check_in_date');
            }
        });
    }
};
