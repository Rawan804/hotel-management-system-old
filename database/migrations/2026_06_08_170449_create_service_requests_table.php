<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('dep_id');
            $table->unsignedBigInteger('ser_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();

            $table->text('details')->nullable();
            $table->string('location')->nullable();

            // 🔥 بدل الوقت → وزن الطلب
            $table->integer('weight')->default(1);

            $table->enum('status', ['pending', 'in_progress', 'done', 'pending_review','waiting_staff'])
                ->default('pending');

            // 🔥 tracking للتوزيع الذكي
            $table->integer('assign_attempts')->default(0);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('booking_id')
                ->references('book_id')
                ->on('bookings')
                ->onDelete('cascade');

            $table->foreign('dep_id')
                ->references('dep_id')
                ->on('departments')
                ->onDelete('cascade');

            $table->foreign('ser_id')
                ->references('ser_id')
                ->on('services')
                ->onDelete('set null');

            $table->foreign('staff_id')
                ->references('staff_id')
                ->on('staff')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};