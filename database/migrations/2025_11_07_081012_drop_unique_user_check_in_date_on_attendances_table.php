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
            if (Schema::hasIndex('attendances', 'attendances_user_date_unique')) {
                $table->dropUnique('attendances_user_date_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Unique constraint deliberately not restored to keep schema simpler
        return;
    }
};
