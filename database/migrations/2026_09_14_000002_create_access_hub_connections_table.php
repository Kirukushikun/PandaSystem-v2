<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Singleton row (id=1) — this install's Access Hub connection. client_id
     * null means "never enrolled" (see AccessHubConnection::isEnrolled()).
     * client_secret is encrypted at rest via the model's cast, never plaintext.
     */
    public function up(): void
    {
        Schema::create('access_hub_connections', function (Blueprint $table) {
            $table->id();
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_hub_connections');
    }
};
