<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Singleton row (id=1) — the on/off switch and staleness threshold for the
     * temporary Proxy Approver override. Kept as its own tiny table rather than
     * a generic settings store, since this is the only configurable value in
     * the app right now (see ProxyApproverSetting::current()).
     */
    public function up(): void
    {
        Schema::create('proxy_approver_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('threshold_days')->default(14);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_approver_settings');
    }
};
