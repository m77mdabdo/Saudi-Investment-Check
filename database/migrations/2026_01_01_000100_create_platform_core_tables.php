<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('sales')->after('email');
            $table->string('google_id')->nullable()->unique()->after('role');
            $table->string('avatar')->nullable()->after('google_id');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->string('status', 20)->default('active'); // active|upcoming|archived
            $table->boolean('is_default')->default(false);
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('cta_settings')->nullable();
            $table->timestamps();
            $table->index(['status', 'is_default']);
        });

        Schema::create('qr_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('campaign')->nullable();
            $table->string('medium')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('scans')->default(0);
            $table->timestamps();
            $table->index(['event_id', 'is_active']);
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('type', 20)->default('single'); // single|multiple|text|textarea
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('icon', 20)->nullable();
            $table->string('placeholder')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_scored')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'position']);
        });

        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('description')->nullable();
            $table->string('icon', 20)->nullable();
            $table->integer('score')->default(0);
            $table->boolean('requires_detail')->default(false);
            $table->string('detail_label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['quiz_question_id', 'key']);
            $table->index(['quiz_question_id', 'position']);
        });

        Schema::create('result_rules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();            // ready|needs_prep|early
            $table->string('classification', 40);        // Hot Lead|Warm Lead|Early Lead
            $table->string('indicator', 20)->default('green'); // green|amber|coral
            $table->unsignedInteger('min_score');
            $table->unsignedInteger('max_score');
            $table->string('headline');
            $table->text('main_text')->nullable();
            $table->text('body')->nullable();
            $table->text('highlight')->nullable();
            $table->json('bullets')->nullable();
            $table->string('primary_cta_label')->nullable();
            $table->string('primary_cta_url')->nullable();
            $table->string('secondary_cta_label')->nullable();
            $table->string('secondary_cta_url')->nullable();
            $table->text('disclaimer')->nullable();
            $table->string('image_query')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('sales_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('color', 20)->default('slate');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('qr_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('company');
            $table->string('whatsapp', 32);
            $table->string('whatsapp_country', 8)->nullable();
            $table->string('email')->nullable();
            $table->boolean('consent')->default(false);
            $table->timestamp('consent_at')->nullable();

            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('max_score')->default(12);
            $table->string('result_key', 40)->nullable();
            $table->string('classification', 40)->nullable();
            $table->foreignId('result_rule_id')->nullable()->constrained()->nullOnDelete();

            $table->string('sales_status', 40)->default('new');
            $table->timestamp('status_changed_at')->nullable();

            $table->string('source', 60)->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('device', 20)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('platform', 40)->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('session_hash', 64)->nullable();

            $table->json('answers_summary')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['result_key', 'created_at']);
            $table->index(['sales_status']);
            $table->index(['event_id', 'created_at']);
            $table->index(['session_hash']);
        });

        Schema::create('lead_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_question_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quiz_option_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question_key');
            $table->string('question_title');
            $table->string('option_key')->nullable();
            $table->text('answer_label')->nullable();
            $table->text('answer_text')->nullable();
            $table->integer('score')->default(0);
            $table->timestamps();
            $table->index(['lead_id', 'question_key']);
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->default('note'); // note|status|assignment|system
            $table->text('body');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('qr_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_hash', 64)->nullable();
            $table->string('source', 60)->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('device', 20)->nullable();
            $table->string('browser', 40)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['name', 'created_at']);
            $table->index(['session_hash', 'name']);
            $table->index(['event_id', 'name']);
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();   // admin_new_lead|customer_result
            $table->string('audience', 20)->default('admin'); // admin|customer
            $table->string('name');
            $table->string('subject');
            $table->longText('body');
            $table->string('recipients')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_key')->nullable();
            $table->string('channel', 20)->default('mail');
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->string('status', 20)->default('sent'); // sent|failed|skipped
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->default('lead');
            $table->string('title');
            $table->string('body')->nullable();
            $table->string('url')->nullable();
            $table->string('icon', 20)->nullable();
            $table->string('level', 20)->default('info'); // info|success|warning
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['read_at', 'created_at']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('string'); // string|text|bool|int|json|url
            $table->string('group', 40)->default('general');
            $table->string('label')->nullable();
            $table->string('hint')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['group', 'position']);
        });

        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique()->default('default');
            $table->string('name')->default('Landing');
            $table->json('content')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('seo_image')->nullable();
            $table->string('hero_image_query')->nullable();
            $table->string('hero_image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('slot')->index();          // hero|result_ready|auth|...
            $table->string('provider', 20)->default('pexels');
            $table->string('external_id')->nullable();
            $table->string('query')->nullable();
            $table->string('url', 1000);
            $table->string('thumb_url', 1000)->nullable();
            $table->string('photographer')->nullable();
            $table->string('photographer_url', 500)->nullable();
            $table->string('avg_color', 20)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('landing_pages');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('lead_answers');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('sales_statuses');
        Schema::dropIfExists('result_rules');
        Schema::dropIfExists('quiz_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('qr_sources');
        Schema::dropIfExists('events');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'google_id', 'avatar', 'is_active', 'last_login_at']);
        });
    }
};
