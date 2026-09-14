<?php

use App\Enums\UserSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks where each users row came from — Access Hub sync only ever touches
     * rows it owns (source = hub); manually-added accounts (the seeded admin,
     * one-off grants, anyone added by hand) are never modified or deleted by it.
     * See project-overview/hub-integration-guide.md §4.1.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('source')->default(UserSource::Manual->value)->after('is_proxy_approver');
        });

        // Explicit backfill alongside default() — driver-independent, not relying
        // on ALTER TABLE's implicit fill behavior for existing rows.
        DB::table('users')->whereNull('source')->update(['source' => UserSource::Manual->value]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
