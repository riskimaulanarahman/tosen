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
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('basic_rate', 10, 2);
            $table->decimal('overtime_rate', 10, 2);
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['name', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
