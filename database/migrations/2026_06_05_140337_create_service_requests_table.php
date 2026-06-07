<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
Schema::create('service_requests', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('booking_id');
    $table->unsignedBigInteger('dep_id');
    $table->unsignedBigInteger('staff_id')->nullable();
    $table->enum('status', ['pending','in_progress','done'])->default('pending');
    $table->timestamps();

    $table->foreign('booking_id')->references('book_id')->on('bookings')->onDelete('cascade');
    $table->foreign('dep_id')->references('dep_id')->on('departments')->onDelete('cascade');
    $table->foreign('staff_id')->references('staff_id')->on('staff')->onDelete('set null');
});
    }

    public function down(): void {
        Schema::dropIfExists('service_requests');
    }
};