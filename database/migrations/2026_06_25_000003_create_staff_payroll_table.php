<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_payroll', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_staff_id')->constrained('maintenance_staff')->onDelete('cascade');
            $table->date('payroll_month');                              // stored as first-of-month e.g. 2026-06-01
            $table->string('salary_type', 20)->default('monthly');      // snapshot at time of generation

            // Attendance breakdown for the month
            $table->integer('total_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('half_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->integer('holiday_days')->default(0);

            // Salary snapshots (so payslips stay accurate after salary changes)
            $table->decimal('basic_salary_snapshot', 10, 2)->default(0);
            $table->decimal('allowances_snapshot', 10, 2)->default(0);

            // Calculated amounts
            $table->decimal('gross_salary', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);           // absence deductions
            $table->decimal('net_salary', 10, 2)->default(0);

            // Payment tracking — marking PAID locks attendance for the month
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->text('payment_remarks')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Idempotent generation: one payroll per staff per month
            $table->unique(['maintenance_staff_id', 'payroll_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_payroll');
    }
};
