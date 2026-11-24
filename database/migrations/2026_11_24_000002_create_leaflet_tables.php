<?php

use App\Enums\AdviceSection;
use App\Enums\ConsentPurpose;
use App\Enums\LeafletFormat;
use App\Enums\ProcedureStatus;
use App\Enums\TimelinePhase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->string('icon_path');
            $table->string('color', 7);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('summary');
            $table->string('color', 7)->nullable();
            $table->string('illustration_path');
            $table->string('cover_illustration_path')->nullable();
            $table->enum('status', array_column(ProcedureStatus::cases(), 'value'))->default('draft');
            $table->json('review_note');
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('search_keywords');
            $table->json('follow_up');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('timeline_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete();
            $table->enum('phase', array_column(TimelinePhase::cases(), 'value'));
            $table->json('marker');
            $table->json('title');
            $table->json('body');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('advice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete();
            $table->enum('section', array_column(AdviceSection::cases(), 'value'));
            $table->json('text');
            $table->string('merge_key')->nullable()->index();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->index(['procedure_id', 'section', 'sort']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->timestamps();
        });

        Schema::create('saved_leaflets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2)->default('et');
            $table->json('procedure_ids');
            $table->text('first_name')->nullable();
            $table->text('procedure_date')->nullable();
            $table->enum('format', array_column(LeafletFormat::cases(), 'value'))->default('a5');
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('purpose', array_column(ConsentPurpose::cases(), 'value'));
            $table->string('text_version');
            $table->string('locale', 2)->default('et');
            $table->timestamp('given_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['consents', 'saved_leaflets', 'settings', 'advice_items', 'timeline_steps', 'procedures', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
