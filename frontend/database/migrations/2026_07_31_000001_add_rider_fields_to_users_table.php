<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_mode')->default('RIDER')->after('email');
            $table->string('rider_status')->default('ACTIVE')->after('account_mode');
            $table->decimal('current_balance', 10, 2)->default(0)->after('rider_status');
            $table->decimal('lifetime_spend', 10, 2)->default(0)->after('current_balance');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['account_mode', 'rider_status', 'current_balance', 'lifetime_spend']);
        });
    }
};
