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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('employee')->after('password');
            }

            if (!Schema::hasColumn('users', 'outlet_id')) {
                $table->foreignId('outlet_id')->nullable()->after('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if ($this->hasForeignKey('users', 'outlet_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['outlet_id']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'outlet_id')) {
                $table->dropColumn('outlet_id');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }

    /**
     * Determine if a foreign key exists for the given table/column without Doctrine.
     */
    private function hasForeignKey(string $table, string $column): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            return false;
        }

        $result = $connection->selectOne(
            'SELECT CONSTRAINT_NAME AS constraint_name FROM information_schema.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            [$connection->getDatabaseName(), $table, $column]
        );

        return !is_null($result);
    }
};
