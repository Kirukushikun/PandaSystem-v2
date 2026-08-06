<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Carry-over needs to know when a PAN was actually approved, independent of whether
     * it's since been served/filed (CarryOverService::previousPanFor() — Approved has no
     * reject/void path in PanWorkflow, so its values are final the moment it lands there;
     * Served/Filed are just paperwork tracking after that). Mirrors the existing filed_at.
     */
    public function up(): void
    {
        Schema::table('pan_requests', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('filed_at');
            $table->timestamp('served_at')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pan_requests', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'served_at']);
        });
    }
};
