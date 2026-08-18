<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('dep_id');

            $table->renameColumn('name', 'name_en');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->renameColumn('name_en', 'name');
            $table->dropColumn('name_ar');
        });
    }
};