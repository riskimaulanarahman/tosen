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
        // Only add foreign key if both tables exist and column exists
        if (Schema::hasTable('users') && Schema::hasTable('outlets') && Schema::hasColumn('users', 'outlet_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            });
        }
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
    }

    /**
     * Check if a foreign key exists for a table/column without Doctrine.
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
