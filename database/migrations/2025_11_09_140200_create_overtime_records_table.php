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
        Schema::create('overtime_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->integer('overtime_minutes');
            $table->decimal('rate_multiplier', 3, 2); // 1.5x, 2x, etc.
            $table->decimal('overtime_amount', 12, 2);
            $table->enum('overtime_type', ['regular', 'weekend', 'holiday'])->default('regular');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['payroll_record_id', 'attendance_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_records');
    }
};
