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
        if (!Schema::hasTable('attendances') || !Schema::hasColumn('attendances', 'check_in_date')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_user_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('attendances') || !Schema::hasColumn('attendances', 'check_in_date')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['user_id', 'check_in_date'], 'attendances_user_date_unique');
        });
    }
};
