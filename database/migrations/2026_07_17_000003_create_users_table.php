<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PANDA never stores passwords — login identity comes from the external company
     * system (ExternalAuthService); this table holds only the local profile and the
     * 8 fixed permission booleans (5 stage permissions + 3 flags). No spatie/permission.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->unique(); // id in the company system
            $table->string('username')->unique();                // display handle, e.g. kreyes
            $table->string('email')->unique();                   // org-standard login identity
            $table->string('name');
            $table->string('position')->nullable();
            $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();

            // 5 stage permissions
            $table->boolean('is_requestor')->default(false);
            $table->boolean('is_division_head')->default(false);
            $table->boolean('is_hr_preparer')->default(false);
            $table->boolean('is_hr_approver')->default(false);
            $table->boolean('is_final_approver')->default(false);

            // 3 flags
            $table->boolean('is_hr_head')->default(false);  // Manila PANs at HR Preparation
            $table->boolean('is_dh_head')->default(false);  // Manila PANs at division stage, all departments
            $table->boolean('is_admin')->default(false);    // administration screens

            $table->string('esign_path')->nullable();       // e-signature used on printed PANs (private disk)
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
