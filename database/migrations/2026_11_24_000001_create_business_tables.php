<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('city');
            $table->string('slug')->unique();
            $table->string('status')->default('draft'); // draft|ready|published
            $table->json('resolution')->nullable(); // field => chosen fact id
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // link|google|social|pdf|pricelist|photo|search|manual
            $table->string('url')->nullable();
            $table->string('path')->nullable(); // storage path for uploads
            $table->string('status')->default('pending'); // pending|ok|blocked|failed
            $table->longText('raw_text')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('facts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('field'); // phone|email|address|hours|service|price|description|name|city
            $table->text('value');
            $table->text('snippet')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->boolean('flagged_injection')->default(false);
            $table->timestamps();
        });

        Schema::create('site_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('kind'); // hero|about|services|prices|gallery|contact|footer
            $table->unsignedInteger('position')->default(0);
            $table->json('content');
            $table->json('fact_keys')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_sections');
        Schema::dropIfExists('facts');
        Schema::dropIfExists('sources');
        Schema::dropIfExists('projects');
    }
};
