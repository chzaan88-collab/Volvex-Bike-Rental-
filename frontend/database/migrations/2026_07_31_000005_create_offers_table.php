<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('discount_type')->default('PERCENT');
            $table->decimal('discount_value', 8, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('offer_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('claimed_at')->useCurrent();
            $table->timestamps();
            $table->unique(['offer_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_user');
        Schema::dropIfExists('offers');
    }
};
