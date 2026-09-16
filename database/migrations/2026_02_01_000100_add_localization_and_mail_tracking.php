<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only — safe to run against production data.
 *
 * 1. `translations` JSON columns carry the English copy of every editable
 *    piece of content (quiz, results, landing) next to the Arabic original.
 * 2. notification_logs gains real delivery tracking so failures are visible
 *    in the admin instead of dying silently in the log file.
 * 3. sales_statuses can opt into notifying the client when reached.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['quiz_questions', 'quiz_options', 'result_rules', 'landing_pages', 'events'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'translations')) {
                    $blueprint->json('translations')->nullable();
                }
            });
        }

        Schema::table('notification_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_logs', 'type')) {
                $table->string('type', 40)->default('lead')->after('template_key');
            }
            if (! Schema::hasColumn('notification_logs', 'locale')) {
                $table->string('locale', 5)->nullable()->after('channel');
            }
            if (! Schema::hasColumn('notification_logs', 'mailer')) {
                $table->string('mailer', 30)->nullable()->after('locale');
            }
            if (! Schema::hasColumn('notification_logs', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('notification_logs', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('sent_at');
            }
            if (! Schema::hasColumn('notification_logs', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(1)->after('failed_at');
            }
            if (! Schema::hasColumn('notification_logs', 'meta')) {
                $table->json('meta')->nullable()->after('error');
            }
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
            $table->index('template_key');
        });

        Schema::table('sales_statuses', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_statuses', 'notify_client')) {
                $table->boolean('notify_client')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('sales_statuses', 'translations')) {
                $table->json('translations')->nullable();
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'locale')) {
                return; // column already exists from the core migration
            }
        });
    }

    public function down(): void
    {
        foreach (['quiz_questions', 'quiz_options', 'result_rules', 'landing_pages', 'events', 'sales_statuses'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'translations')) {
                    $blueprint->dropColumn('translations');
                }
            });
        }

        Schema::table('sales_statuses', function (Blueprint $table) {
            $table->dropColumn('notify_client');
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['template_key']);
            $table->dropColumn(['type', 'locale', 'mailer', 'sent_at', 'failed_at', 'attempts', 'meta']);
        });
    }
};
