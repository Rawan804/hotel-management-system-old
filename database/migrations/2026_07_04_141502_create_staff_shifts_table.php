<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('staff_shifts', function (Blueprint $table) {

    $table->id();

    $table->foreignId('staff_id')
        ->constrained('staff', 'staff_id')
        ->cascadeOnDelete();

    // يوم الأسبوع
    $table->enum('day_of_week', [
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday'
    ]);

    $table->time('start_time');

    $table->time('end_time');

    $table->boolean('is_active')
        ->default(true);

    $table->timestamps();

});}

    public function down(): void
    {
        Schema::dropIfExists('staff_shifts');
    }
};