<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_staff', function (Blueprint $table) {
            $table->string('cnic', 20)->nullable()->after('phone');
            $table->date('joining_date')->nullable()->after('cnic');
            $table->enum('shift', ['morning', 'evening', 'night', 'full_day'])->default('full_day')->after('joining_date');
            $table->enum('salary_type', ['monthly', 'daily'])->default('monthly')->after('shift');
            $table->decimal('basic_salary', 10, 2)->nullable()->after('salary_type');   // used when salary_type = monthly
            $table->decimal('daily_rate', 10, 2)->nullable()->after('basic_salary');    // used when salary_type = daily
            $table->decimal('allowances', 10, 2)->default(0)->after('daily_rate');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_staff', function (Blueprint $table) {
            $table->dropColumn(['cnic', 'joining_date', 'shift', 'salary_type', 'basic_salary', 'daily_rate', 'allowances']);
        });
    }
};
