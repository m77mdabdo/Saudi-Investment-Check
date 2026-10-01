<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Walk-up registrations captured at an event (QR ▸ landing ▸ welcome form).
 *
 * Deliberately NOT stored as leads: a lead is a quiz result (score, result rule,
 * one lead_answers row per question, and a NOT NULL company), none of which a
 * registration has. Keeping them apart also stops them polluting the leads
 * filters, the funnel metrics and the 30-column leads export.
 *
 * Additive only — no existing table is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Same attribution the quiz journey records, via VisitorContext.
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('qr_source_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('phone', 32);
            $table->string('phone_country', 8)->nullable();
            $table->string('email')->nullable();

            // Relative path on the PRIVATE 'local' disk. Never a public URL.
            $table->string('photo_path')->nullable();

            $table->string('source', 60)->nullable();
            $table->string('device', 20)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('platform', 40)->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('session_hash', 64)->nullable();

            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
