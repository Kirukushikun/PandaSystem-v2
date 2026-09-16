<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Captures the department an employee is moving TO, for the action types
     * where that's a real possibility (Lateral Transfer, Change of Position,
     * Promotion — see ActionType::mayChangeDepartment()). Nullable: most PANs
     * of those types don't actually change department, and every other action
     * type never shows this field at all. On Final Approval, if set, it's
     * written back to employees.department_id — see GivesFinalApproval.
     */
    public function up(): void
    {
        Schema::table('pan_forms', function (Blueprint $table) {
            $table->foreignId('new_department_id')->nullable()->after('employment_status')
                ->constrained('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pan_forms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('new_department_id');
        });
    }
};
