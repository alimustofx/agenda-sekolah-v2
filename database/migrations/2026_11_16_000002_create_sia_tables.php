<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agenda_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('slug', 120)->unique();
            $t->text('description')->nullable();
            $t->string('color', 9)->default('#286A91');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('agenda_days', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->date('agenda_date')->index();
            $t->text('introduction')->nullable();
            $t->text('recipients')->nullable();
            $t->text('uniform_information')->nullable();
            $t->text('general_notes')->nullable();
            $t->longText('original_source_text')->nullable();
            $t->string('status', 12)->default('draft')->index();
            $t->boolean('show_on_website')->default(true);
            $t->boolean('show_on_tv')->default(true);
            $t->timestamp('published_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('agenda_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agenda_day_id')->constrained('agenda_days')->cascadeOnDelete();
            $t->foreignId('category_id')->nullable()->constrained('agenda_categories')->nullOnDelete();
            $t->string('title', 200);
            $t->text('description')->nullable();
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();
            $t->string('time_description', 120)->nullable();
            $t->string('location', 200)->nullable();
            $t->string('responsible_label', 60)->nullable();
            $t->string('responsible_person', 200)->nullable();
            $t->text('participants')->nullable();
            $t->text('officers')->nullable();
            $t->text('additional_notes')->nullable();
            $t->string('priority', 12)->default('normal');
            $t->unsignedInteger('display_order')->default(0);
            $t->timestamps();
            $t->index(['agenda_day_id', 'display_order']);
        });

        Schema::create('agenda_item_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agenda_item_id')->constrained('agenda_items')->cascadeOnDelete();
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();
            $t->string('time_description', 120)->nullable();
            $t->timestamps();
        });

        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->text('content');
            $t->string('category', 100)->nullable();
            $t->string('priority', 12)->default('normal');
            $t->dateTime('start_at')->nullable();
            $t->dateTime('end_at')->nullable();
            $t->boolean('show_on_website')->default(true);
            $t->boolean('show_on_tv')->default(true);
            $t->boolean('is_active')->default(true);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['is_active', 'start_at', 'end_at']);
        });

        Schema::create('school_activities', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->text('description')->nullable();
            $t->string('location', 200)->nullable();
            $t->string('responsible_person', 200)->nullable();
            $t->dateTime('start_at')->nullable();
            $t->dateTime('end_at')->nullable();
            $t->string('time_description', 120)->nullable();
            $t->string('status', 12)->default('scheduled');
            $t->boolean('is_status_manual')->default(false);
            $t->boolean('show_on_website')->default(true);
            $t->boolean('show_on_tv')->default(true);
            $t->timestamps();
        });

        Schema::create('youtube_sources', function (Blueprint $t) {
            $t->id();
            $t->string('source_type', 10)->default('video');
            $t->string('title', 250);
            $t->string('channel', 200)->nullable();
            $t->string('youtube_url', 500);
            $t->string('youtube_id', 20)->nullable();
            $t->string('playlist_id', 60)->nullable();
            $t->string('thumbnail_url', 500)->nullable();
            $t->unsignedInteger('duration')->nullable();
            $t->unsignedInteger('display_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('youtube_settings', function (Blueprint $t) {
            $t->id();
            $t->boolean('playback_enabled')->default(true);
            $t->boolean('autoplay')->default(true);
            $t->boolean('muted')->default(true);
            $t->unsignedTinyInteger('volume')->default(50);
            $t->boolean('shuffle')->default(false);
            $t->boolean('loop')->default(true);
            $t->boolean('intermission_enabled')->default(true);
            $t->unsignedSmallInteger('intermission_duration')->default(20);
            $t->string('playback_mode', 12)->default('mixed');
            $t->timestamps();
        });

        Schema::create('display_settings', function (Blueprint $t) {
            $t->id();
            $t->string('layout_mode', 20)->default('split');
            $t->string('display_title', 100)->default('Agenda Hari Ini');
            $t->unsignedTinyInteger('agenda_cards_per_page')->default(3);
            $t->unsignedSmallInteger('pagination_interval')->default(15);
            $t->unsignedSmallInteger('pagination_min')->default(10);
            $t->unsignedSmallInteger('pagination_max')->default(30);
            $t->boolean('adaptive_pagination')->default(true);
            $t->boolean('video_panel_enabled')->default(true);
            $t->boolean('information_panel_enabled')->default(true);
            $t->boolean('weather_enabled')->default(false);
            $t->boolean('running_text_enabled')->default(true);
            $t->json('panel_modes')->nullable();
            $t->text('school_info_text')->nullable();
            $t->unsignedSmallInteger('refresh_interval')->default(30);
            $t->string('timezone', 40)->default('Asia/Jakarta');
            $t->timestamps();
        });

        Schema::create('running_texts', function (Blueprint $t) {
            $t->id();
            $t->string('content', 500);
            $t->unsignedSmallInteger('speed')->default(90);
            $t->string('text_color', 9)->default('#FFFFFF');
            $t->string('background_color', 9)->default('#17324D');
            $t->string('priority', 12)->default('normal');
            $t->dateTime('start_at')->nullable();
            $t->dateTime('end_at')->nullable();
            $t->unsignedInteger('display_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('weather_settings', function (Blueprint $t) {
            $t->id();
            $t->string('provider', 30)->default('open-meteo');
            $t->string('location', 200)->default('Turen, Kabupaten Malang, Jawa Timur');
            $t->decimal('latitude', 9, 6)->default(-8.174400);
            $t->decimal('longitude', 9, 6)->default(112.699700);
            $t->boolean('is_enabled')->default(false);
            $t->unsignedSmallInteger('cache_duration')->default(30);
            $t->timestamps();
        });

        Schema::create('school_settings', function (Blueprint $t) {
            $t->id();
            $t->string('school_name', 200)->default('SMA Negeri 1 Turen');
            $t->string('tagline', 200)->nullable();
            $t->string('school_logo', 300)->nullable();
            $t->text('school_address')->nullable();
            $t->string('website_url', 300)->nullable();
            $t->string('primary_color', 9)->default('#10243A');
            $t->string('secondary_color', 9)->default('#1598AD');
            $t->string('timezone', 40)->default('Asia/Jakarta');
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 40);
            $t->string('module', 40)->index();
            $t->text('description');
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('parser_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('agenda_day_id')->nullable()->constrained('agenda_days')->nullOnDelete();
            $t->longText('original_text');
            $t->string('parser_type', 20)->default('rule');
            $t->json('parsing_result')->nullable();
            $t->json('confidence_summary')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['parser_logs', 'audit_logs', 'school_settings', 'weather_settings', 'running_texts', 'display_settings', 'youtube_settings', 'youtube_sources', 'school_activities', 'announcements', 'agenda_item_schedules', 'agenda_items', 'agenda_days', 'agenda_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
