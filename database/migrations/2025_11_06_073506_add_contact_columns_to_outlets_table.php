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
        Schema::table('outlets', function (Blueprint $table) {
            if (!Schema::hasColumn('outlets', 'phone')) {
                $table->string('phone')->nullable()->after('address');
            }

            if (!Schema::hasColumn('outlets', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('outlets', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('radius');
            }

            if (!Schema::hasColumn('outlets', 'description')) {
                $table->text('description')->nullable()->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            foreach (['description', 'is_active', 'email', 'phone'] as $column) {
                if (Schema::hasColumn('outlets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
