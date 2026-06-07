<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {

            $table->enum('role', [
                'general_manager',
                'supervisor',
                'employee'
            ])->default('employee');

            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')
                ->nullable()
                ->after('role');

            $table->foreign('created_by')
                ->references('staff_id')
                ->on('staff')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {

            $table->dropForeign(['created_by']);

            $table->dropColumn([
                'role',
                'is_active',
                'created_by'
            ]);
        });
    }
};