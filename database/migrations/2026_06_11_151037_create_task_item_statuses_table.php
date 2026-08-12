<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {Schema::create('task_item_statuses', function (Blueprint $table) {
    $table->id();

    $table->foreignId('task_id')
        ->constrained('tasks') // 👈 بيربط على id تلقائياً
        ->cascadeOnDelete();

    $table->foreignId('fixed_task_item_id')
        ->constrained('fixed_task_items')
        ->cascadeOnDelete();

    $table->boolean('is_done')->default(false);

    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('task_item_statuses');
    }
};