<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_customers', function (Blueprint $table) {
            $table->id('res_cus_id');
          $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();
            $table->foreignId('res_id')->constrained('restaurants', 'res_id')->cascadeOnDelete();

            $table->integer('person_num');
             $table->dateTime('reservation_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_customers');
    }
};