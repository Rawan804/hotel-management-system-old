<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
Schema::create('fixed_tasks', function (Blueprint $table) {

    $table->id();

    $table->foreignId('dep_id')
        ->constrained('departments', 'dep_id')
        ->cascadeOnDelete();

   /* $table->foreignId('staff_id')
        ->constrained('staff', 'staff_id')
        ->cascadeOnDelete();
*/
    $table->string('name_ar');
    $table->string('name_en');

    // 🔥 مهم: وزن المهمة بدل الوقت
    $table->integer('weight')->default(1);

    $table->boolean('is_active')->default(true);

    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_tasks');
    }
};