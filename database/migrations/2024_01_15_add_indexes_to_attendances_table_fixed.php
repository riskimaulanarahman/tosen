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
        // Only add indexes if attendances table exists
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                // Check if index doesn't exist before adding
                if (!Schema::hasIndex('attendances', 'idx_user_checkin_date')) {
                    $table->index(['user_id', 'check_in_date'], 'idx_user_checkin_date');
                }
                
                // Check if index doesn't exist before adding
                if (!Schema::hasIndex('attendances', 'idx_attendance_status')) {
                    $table->index('attendance_status', 'idx_attendance_status');
                }
                
                // Check if index doesn't exist before adding
                if (!Schema::hasIndex('attendances', 'idx_created_at')) {
                    $table->index('created_at', 'idx_created_at');
                }
            });
        }

        // Only add indexes if users table exists and has the required columns
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role') && Schema::hasColumn('users', 'outlet_id')) {
            Schema::table('users', function (Blueprint $table) {
                // Check if index doesn't exist before adding
                if (!Schema::hasIndex('users', 'idx_role_outlet')) {
                    $table->index(['role', 'outlet_id'], 'idx_role_outlet');
                }
            });
        }

        // Only add indexes if outlets table exists
        if (Schema::hasTable('outlets') && Schema::hasColumn('outlets', 'owner_id')) {
            Schema::table('outlets', function (Blueprint $table) {
                // Check if index doesn't exist before adding
                if (!Schema::hasIndex('outlets', 'idx_owner_id')) {
                    $table->index('owner_id', 'idx_owner_id');
                }
            });
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
                'attendances' => [
                    'idx_user_checkin_date',
                    'idx_attendance_status',
                    'idx_created_at',
                ],
                'users' => ['idx_role_outlet'],
                'outlets' => ['idx_owner_id'],
            ];

            foreach ($indexes as $table => $indexList) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                foreach ($indexList as $index) {
                    DB::statement(sprintf('DROP INDEX IF EXISTS "%s"', $index));
                }
            }

            return;
        }

        $indexes = [
            'attendances' => [
                'idx_user_checkin_date',
                'idx_attendance_status',
                'idx_created_at',
            ],
            'users' => ['idx_role_outlet'],
            'outlets' => ['idx_owner_id'],
        ];

        foreach ($indexes as $table => $indexList) {
            foreach ($indexList as $index) {
                $this->dropIndexIfExists($table, $index);
            }
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (!Schema::hasTable($table) || !Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index) {
            $blueprint->dropIndex($index);
        });
    }
};
