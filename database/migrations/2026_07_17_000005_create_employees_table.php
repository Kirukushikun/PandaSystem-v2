<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The master roster every PAN is raised against (Admin owns it; HR Preparation
     * reads it). Soft deletes: removing an employee never deletes PAN history —
     * they simply can't be selected for new PANs. Deletion while an ongoing PAN
     * exists is blocked in EmployeePolicy + query, never just the UI.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no')->unique(); // EMP-10233 style
            $table->string('name');
            $table->foreignId('farm_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('position');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
