<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Subject lines (and future copy) need an English side too. Additive only. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_templates', 'translations')) {
                $table->json('translations')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            if (Schema::hasColumn('notification_templates', 'translations')) {
                $table->dropColumn('translations');
            }
        });
    }
};
