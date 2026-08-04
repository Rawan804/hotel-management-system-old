<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create('reservations', function (Blueprint $table) {
    $table->id('resev_id');
    $table->foreignId('hall_id')->constrained('halls','hall_id');
    $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
  
    $table->dateTime('start_time');
    $table->dateTime('end_time');
    $table->enum('status',['pending','confirmed','canceled'])->default('pending');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
