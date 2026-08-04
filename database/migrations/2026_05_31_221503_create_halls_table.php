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
     Schema::create('halls', function (Blueprint $table) {
    $table->id('hall_id');
    $table->string('image')->nullable();
    $table->text('details_ar')->nullable();
    $table->text('details_en')->nullable();
    $table->string('name_ar')->nullable();
    $table->string('name_en')->nullable();
    $table->integer('capacity');
    $table->decimal('price',10,2);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
