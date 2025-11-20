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
            if (!Schema::hasColumn('users', 'base_salary')) {
                $table->decimal('base_salary', 12, 2)->nullable()->after('role');
            }
        });

        Schema::table('outlets', function (Blueprint $table) {
            if (!Schema::hasColumn('outlets', 'default_salary')) {
                $table->decimal('default_salary', 12, 2)->nullable()->after('radius');
            }
            if (!Schema::hasColumn('outlets', 'use_tax')) {
                $table->boolean('use_tax')->default(true)->after('default_salary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'base_salary')) {
                $table->dropColumn('base_salary');
            }
        });

        Schema::table('outlets', function (Blueprint $table) {
            foreach (['use_tax', 'default_salary'] as $column) {
                if (Schema::hasColumn('outlets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
