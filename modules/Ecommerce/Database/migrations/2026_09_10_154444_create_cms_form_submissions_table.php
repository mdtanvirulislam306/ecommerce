<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cms_page_id')->constrained('cms_pages')->cascadeOnDelete();
            $table->string('widget_id');
            $table->string('type');
            $table->json('payload');
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['cms_page_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_form_submissions');
    }
};
