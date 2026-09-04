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
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Admin/User who did the action
            $table->string('action'); // e.g., 'APPROVE_CORRECTION', 'REJECT_CORRECTION', 'UPDATE_SETTINGS'
            $table->string('target')->nullable(); // e.g., 'User: Ahmad' or 'AttendanceCorrection: 10'
            $table->json('details')->nullable(); // JSON object for before/after changes or extra info
            $table->string('ip_address')->nullable();
            $table->timestamps();
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
