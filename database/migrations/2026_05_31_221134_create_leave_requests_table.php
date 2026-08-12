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
       Schema::create('leave_requests', function (Blueprint $table) {
    $table->id('leave_id');
    $table->foreignId('staff_id')->constrained('staff','staff_id')->cascadeOnDelete();
   
    $table->string('type');
    $table->date('start_date');
    $table->date('end_date');
    $table->text('reason');
    $table->enum('status',['pending','approved','rejected'])->default('pending');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
