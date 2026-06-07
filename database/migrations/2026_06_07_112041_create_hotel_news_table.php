<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_news', function (Blueprint $table) {

            $table->id();

            $table->string('title_ar');
            $table->string('title_en');

            $table->longText('content_ar');
            $table->longText('content_en');

            $table->string('image')->nullable();

            $table->boolean('is_pinned')
                  ->default(false);

            $table->timestamp('published_at')
                  ->nullable();

            $table->unsignedBigInteger('created_by');

            $table->timestamps();

            $table->foreign('created_by')
                  ->references('staff_id')
                  ->on('staff')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_news');
    }
};