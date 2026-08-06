<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_subscribed')->default(false);
            $table->timestamp('subscription_ends_at')->nullable();
            $table->string('plan_type')->default('free'); // free, monthly, yearly
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_subscribed', 'subscription_ends_at', 'plan_type']);
        });
    }
};