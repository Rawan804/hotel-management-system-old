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
    Schema::table('staff', function (Blueprint $table) {
        $table->string('invitation_token', 80)->nullable()->unique();
        $table->timestamp('invitation_accepted_at')->nullable();
    });
}

public function down(): void
{
    Schema::table('staff', function (Blueprint $table) {
        $table->dropColumn(['invitation_token', 'invitation_accepted_at']);
    });
}
};
