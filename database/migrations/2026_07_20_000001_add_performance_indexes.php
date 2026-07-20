<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for columns that are filtered/searched on every render of a queue
     * table but weren't covered by a unique/FK index: employees.name (searched
     * from four different queues), pan_requests.confidentiality_tag (filtered on
     * every Division Head / HR Preparation render), access_logs.created_at (every
     * login write + the Maintenance Logs list sort), notifications.read_at
     * (the bell's unread count runs on every page load via the layout).
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('pan_requests', function (Blueprint $table) {
            $table->index('confidentiality_tag');
        });

        Schema::table('access_logs', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('pan_requests', function (Blueprint $table) {
            $table->dropIndex(['confidentiality_tag']);
        });

        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_index');
        });
    }
};
