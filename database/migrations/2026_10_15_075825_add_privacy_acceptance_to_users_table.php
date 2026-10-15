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
        Schema::table('users', function (Blueprint $table) {
            // Existing accounts must not be assigned a consent they have not given.
            $table->timestamp('privacy_policy_accepted_at')->nullable();
            $table->string('privacy_policy_version', 64)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['privacy_policy_accepted_at', 'privacy_policy_version']);
        });
    }
};
