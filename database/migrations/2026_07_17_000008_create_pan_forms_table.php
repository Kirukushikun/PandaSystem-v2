<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The prepared paperwork (one per PAN, created at HR Preparation).
     * action_reference is the ordered JSON the print view consumes:
     * [{field, from, to}, …] — six fixed fields first (section, place, head,
     * position, joblevel, basic), leavecredits only for Regularization,
     * dynamic allowance rows appended.
     */
    public function up(): void
    {
        Schema::create('pan_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pan_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('date_hired');
            $table->string('employment_status');       // EmploymentStatus enum
            $table->date('doe_from');                  // date of effectivity
            $table->date('doe_to')->nullable();        // allowances with an end date → expiry reminders
            $table->string('wage_no')->nullable();     // Wage Order only
            $table->json('action_reference');
            $table->text('remarks')->nullable();
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pan_forms');
    }
};
