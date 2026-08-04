<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * v1→v2 migration tracking. legacy_id makes the importer idempotent (re-runnable via
     * updateOrCreate). legacy_department preserves the pre-resolution department snapshot
     * for the rows where it diverges from the employee's current department — the historical
     * fact of who was entitled to approve, per legacy-data-migration-plan.md risk #4/#5.
     * legacy_actors preserves v1's requestor/preparer/approver names as plain text, since v1
     * actor ids don't map to real v2 user accounts (risk #6) and pan_requests' participant
     * columns are live FKs, not free-text history.
     */
    public function up(): void
    {
        Schema::table('pan_requests', function (Blueprint $table) {
            $table->unsignedInteger('legacy_id')->nullable()->unique()->after('id');
            $table->string('legacy_department')->nullable()->after('department_id');
            $table->json('legacy_actors')->nullable()->after('legacy_department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pan_requests', function (Blueprint $table) {
            $table->dropColumn(['legacy_id', 'legacy_department', 'legacy_actors']);
        });
    }
};
