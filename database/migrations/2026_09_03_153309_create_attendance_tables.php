<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['CHECK_IN', 'CHECK_OUT']);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('accuracy')->nullable();
            $table->integer('distance')->nullable(); // distance to location in meters
            $table->string('status'); // VALID, REJECTED
            $table->text('reason')->nullable(); // If rejected
            $table->timestamp('event_time');
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->foreignId('check_in_event_id')->nullable()->constrained('attendance_events')->onDelete('set null');
            $table->foreignId('check_out_event_id')->nullable()->constrained('attendance_events')->onDelete('set null');
            $table->string('status'); // HADIR, TERLAMBAT, TIDAK_HADIR, BELUM_CHECKOUT
            $table->timestamps();
            
            $table->unique(['user_id', 'date']);
        });


    }

    public function down(): void
    {

        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_events');
    }
};
