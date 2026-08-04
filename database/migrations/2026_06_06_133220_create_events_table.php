<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {

            $table->id();

            $table->string('title_ar');
            $table->string('title_en');

            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();

            $table->date('event_date');
            $table->time('event_time');

            $table->string('location_ar');
            $table->string('location_en');

            $table->string('image')->nullable();

            $table->string('contact_phone');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};