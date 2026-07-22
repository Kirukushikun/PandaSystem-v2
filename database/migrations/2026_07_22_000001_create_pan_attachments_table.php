<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Up to 3 supporting documents per PAN instead of one. Existing single
     * attachments are carried over as the first row before the old column drops —
     * no PAN loses its document in the switch.
     */
    public function up(): void
    {
        Schema::create('pan_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pan_request_id')->constrained()->cascadeOnDelete();
            $table->string('path');           // private disk, randomized filename — never shown to users
            $table->string('original_name');  // what the uploader named it — shown + used on download
            $table->unsignedBigInteger('size')->default(0); // bytes
            $table->timestamps();
        });

        DB::table('pan_requests')->whereNotNull('attachment_path')->orderBy('id')
            ->cursor()->each(function (object $pan) {
                DB::table('pan_attachments')->insert([
                    'pan_request_id' => $pan->id,
                    'path' => $pan->attachment_path,
                    'original_name' => basename($pan->attachment_path),
                    'size' => Storage::exists($pan->attachment_path) ? Storage::size($pan->attachment_path) : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('pan_requests', function (Blueprint $table) {
            $table->dropColumn('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('pan_requests', function (Blueprint $table) {
            $table->string('attachment_path')->nullable();
        });

        DB::table('pan_attachments')->orderBy('pan_request_id')->orderBy('id')
            ->get()
            ->groupBy('pan_request_id')
            ->each(function ($rows, $panId) {
                // Only the first (oldest) attachment survives the rollback — the
                // column this restores was single-valued to begin with.
                DB::table('pan_requests')->where('id', $panId)
                    ->update(['attachment_path' => $rows->first()->path]);
            });

        Schema::dropIfExists('pan_attachments');
    }
};
