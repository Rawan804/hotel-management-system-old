<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('fixed_task_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('fixed_task_id')
                ->constrained('fixed_tasks')
                ->cascadeOnDelete();

            $table->string('name_ar');
            $table->string('name_en');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_task_items');
    }
};