<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
      Schema::create('staff', function (Blueprint $table) {

    $table->id('staff_id');


    $table->foreignId('dep_id')->nullable()

        ->constrained('departments','dep_id')
        ->cascadeOnDelete();

    $table->string('name');
    $table->string('phone');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('image')->nullable();

    $table->enum('role', [
        'general_manager',
        'supervisor',
        'service_manager',
        'employee'
    ])->default('employee');

    $table->boolean('is_active')->default(1);

    // 🔥 حالة الموظف
    $table->enum('status', [
        'available',
        'busy',
        'offline',
        'on_break',
        'overloaded'
    ])->default('available');

    // 🔥 الحمل الحالي (أساس التوزيع)
    $table->integer('service_load')->default(0);
    $table->integer('max_load')->default(50);

   $table->text('fcm_token')->nullable();
    $table->timestamps();
});    }


    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};