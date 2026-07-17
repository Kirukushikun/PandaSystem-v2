<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The full back-and-forth history: every return, dispute, rejection, void,
     * and unserved marking, with its mandatory reason. from/to are PanStatus values.
     */
    public function up(): void
    {
        Schema::create('pan_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pan_request_id')->constrained()->cascadeOnDelete();
            $table->string('action');                  // the PanWorkflow action, e.g. return_to_requestor
            $table->string('from_status');             // PanStatus enum
            $table->string('to_status');               // PanStatus enum
            $table->string('reason');                  // preset or custom — always required here
            $table->text('details')->nullable();
            $table->foreignId('returned_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pan_returns');
    }
};
