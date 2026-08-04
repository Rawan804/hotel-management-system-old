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
    Schema::create('restaurants', function (Blueprint $table) {
        $table->id('res_id');
        $table->string('name_en')->nullable(); // تعديل الاسم للإنجليزي مباشرة
        $table->string('name_ar')->nullable(); // إضافة الاسم العربي
        $table->string('image')->nullable();
        $table->text('details_en')->nullable(); // تحويل التكست لـ string وتسميته إنجليزي
        $table->text('details_ar')->nullable(); // إضافة التفاصيل العربي
        $table->timestamps();
    });
}
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
