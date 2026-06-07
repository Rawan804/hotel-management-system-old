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
      Schema::create('bookings', function (Blueprint $table) {
    $table->id('book_id');
    $table->foreignId('room_id')->constrained('rooms','room_id');
    $table->foreignId('customer_id')->constrained('customers','customer_id');
    $table->date('startDate');
    $table->date('endDate');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
