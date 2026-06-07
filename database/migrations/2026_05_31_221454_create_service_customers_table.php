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
     Schema::create('service_customers', function (Blueprint $table) {
    $table->id('ser_cust_id');
    $table->foreignId('ser_id')->constrained('services','ser_id');
    $table->foreignId('customer_id')->constrained('customers','customer_id');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_customers');
    }
};
