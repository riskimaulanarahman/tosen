<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE payroll_periods
            MODIFY COLUMN status ENUM('draft', 'active', 'completed', 'paid', 'archived') NOT NULL DEFAULT 'draft'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE payroll_periods
            MODIFY COLUMN status ENUM('draft', 'active', 'completed', 'archived') NOT NULL DEFAULT 'draft'
        ");
    }
};
