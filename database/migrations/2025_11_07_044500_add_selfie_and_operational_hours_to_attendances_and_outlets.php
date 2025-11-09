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
            if (!Schema::hasColumn('attendances', 'check_in_accuracy')) {
                $table->decimal('check_in_accuracy', 8, 2)->nullable()->after('check_in_longitude');
            }

            if (!Schema::hasColumn('attendances', 'check_out_accuracy')) {
                $table->decimal('check_out_accuracy', 8, 2)->nullable()->after('check_out_longitude');
            }

            if (!Schema::hasColumn('attendances', 'has_check_in_selfie')) {
                $table->boolean('has_check_in_selfie')->default(false)->after('check_in_file_size');
            }

            if (!Schema::hasColumn('attendances', 'has_check_out_selfie')) {
                $table->boolean('has_check_out_selfie')->default(false)->after('check_out_file_size');
            }
        });

        Schema::table('outlets', function (Blueprint $table) {
            if (!Schema::hasColumn('outlets', 'operational_start_time')) {
                $table->time('operational_start_time')->nullable()->after('radius');
            }

            if (!Schema::hasColumn('outlets', 'operational_end_time')) {
                $table->time('operational_end_time')->nullable()->after('operational_start_time');
            }

            if (!Schema::hasColumn('outlets', 'late_tolerance_minutes')) {
                $table->integer('late_tolerance_minutes')->default(15)->after('operational_end_time');
            }

            if (!Schema::hasColumn('outlets', 'early_checkout_tolerance')) {
                $table->integer('early_checkout_tolerance')->default(10)->after('late_tolerance_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'check_in_accuracy')) {
                $table->dropColumn('check_in_accuracy');
            }

            if (Schema::hasColumn('attendances', 'check_out_accuracy')) {
                $table->dropColumn('check_out_accuracy');
            }

            if (Schema::hasColumn('attendances', 'has_check_in_selfie')) {
                $table->dropColumn('has_check_in_selfie');
            }

            if (Schema::hasColumn('attendances', 'has_check_out_selfie')) {
                $table->dropColumn('has_check_out_selfie');
            }
        });

        Schema::table('outlets', function (Blueprint $table) {
            $columns = [
                'operational_start_time',
                'operational_end_time',
                'late_tolerance_minutes',
                'early_checkout_tolerance',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('outlets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
