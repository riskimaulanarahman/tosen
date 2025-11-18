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
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('attendances', 'checkout_remarks')) {
                $table->text('checkout_remarks')->nullable()->after('notes');
            }
            
            if (!Schema::hasColumn('attendances', 'has_check_in_selfie')) {
                $table->boolean('has_check_in_selfie')->default(false)->after('check_out_file_size');
            }
            
            if (!Schema::hasColumn('attendances', 'has_check_out_selfie')) {
                $table->boolean('has_check_out_selfie')->default(false)->after('has_check_in_selfie');
            }
            
            if (!Schema::hasColumn('attendances', 'check_in_date')) {
                $table->date('check_in_date')->nullable()->after('check_in_time');
            }
            
            if (!Schema::hasColumn('attendances', 'is_overtime')) {
                $table->boolean('is_overtime')->default(false)->after('has_check_out_selfie');
            }
        });

        Schema::table('outlets', function (Blueprint $table) {
            // Add missing overtime_config column if it doesn't exist
            if (!Schema::hasColumn('outlets', 'overtime_config')) {
                $table->json('overtime_config')->nullable()->after('overtime_threshold_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $columns = [
                'checkout_remarks',
                'has_check_in_selfie', 
                'has_check_out_selfie',
                'is_overtime'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('outlets', 'overtime_config')) {
            Schema::table('outlets', function (Blueprint $table) {
                $table->dropColumn('overtime_config');
            });
        }
    }
};
