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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // 'login', 'checkin', 'checkout', 'create_employee', 'update_employee', etc.
            $table->string('resource_type')->nullable(); // 'attendance', 'employee', 'outlet', etc.
            $table->unsignedBigInteger('resource_id')->nullable(); // ID of the affected resource
            $table->text('description')->nullable(); // Human-readable description
            $table->json('old_values')->nullable(); // Previous values for updates
            $table->json('new_values')->nullable(); // New values for updates
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('risk_level')->default('low'); // 'low', 'medium', 'high', 'critical'
            $table->json('metadata')->nullable(); // Additional context data
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index(['risk_level', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
