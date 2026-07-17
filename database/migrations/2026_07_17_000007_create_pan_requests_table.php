<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The PAN itself. ONE status column (PanStatus enum) — approval and serving
     * are never split, so illegal combinations are unrepresentable.
     *
     * Participant columns are plain FKs (not encrypted as the plan once mused):
     * queues filter on them and the UI shows "who acted" — relational integrity
     * wins. Confidentiality is enforced by PanRequestPolicy on every entry point.
     */
    public function up(): void
    {
        Schema::create('pan_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();                    // PAN-2026-00347
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('action_type');                            // ActionType enum
            $table->text('justification')->nullable();
            $table->string('attachment_path')->nullable();            // private disk, policy-gated download
            $table->string('status')->index();                        // PanStatus enum
            $table->string('confidentiality_tag')->default('untagged'); // ConfidentialityTag enum
            $table->string('origin')->default('requestor');           // PanOrigin enum — 'hr' skips Requestor/DH stages

            // participants (filled in as the PAN moves through the chain)
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete(); // null for origin='hr'
            $table->foreignId('division_head_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('hr_preparer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('hr_approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('final_approver_id')->nullable()->constrained('users')->nullOnDelete();

            // the carry-over chain: powers From-value carry-over, the employment-status
            // lock, and the "Pre-generated from PAN-…" link
            $table->foreignId('previous_pan_id')->nullable()->constrained('pan_requests')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('filed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pan_requests');
    }
};
