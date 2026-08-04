<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_customers', function (Blueprint $table) {
            $table->id('ser_cust_id');

            $table->foreignId('ser_id')
                ->constrained('services','ser_id')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained('customers','id')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_customers');
    }
};