<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_staff_id')->constrained('maintenance_staff')->onDelete('cascade');
            $table->date('attendance_date');
            // present | absent | half_day | on_leave | holiday
            $table->enum('status', ['present', 'absent', 'half_day', 'on_leave', 'holiday'])->default('absent');
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // One record per staff per day — idempotent inserts via updateOrCreate
            $table->unique(['maintenance_staff_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance');
    }
};
