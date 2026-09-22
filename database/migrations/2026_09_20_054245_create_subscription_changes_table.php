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
        Schema::create('subscription_changes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')
            ->constrained()
            ->cascadeOnDelete();

            $table->foreignId('old_plan_id')
            ->constrained('plans');

            $table->datetime('effective_at');

            $table->timestamps();

            $table->index(['subscription_id', 'effective_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_changes');
    }
};
