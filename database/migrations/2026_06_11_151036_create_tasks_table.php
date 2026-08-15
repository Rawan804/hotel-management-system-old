<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
 Schema::create('tasks', function (Blueprint $table) {

    $table->id();

    $table->foreignId('staff_id')
        ->constrained('staff', 'staff_id')
        ->cascadeOnDelete();

    $table->foreignId('fixed_task_id')
        ->constrained('fixed_tasks')
        ->cascadeOnDelete();

    $table->enum('status', ['pending','in_progress','completed'])
        ->default('pending');

    $table->integer('weight')->default(1);

    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};