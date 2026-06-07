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
      Schema::create('complaints', function (Blueprint $table) {
    $table->id('com_id');
    $table->foreignId('staff_id')->constrained('staff','staff_id')->cascadeOnDelete();
    $table->string('title');
    $table->text('description');
    $table->enum('status',['pending','resolved'])->default('pending');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
