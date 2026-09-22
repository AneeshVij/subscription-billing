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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
            ->constrained()
            ->cascadeOnDelete();

            $table->foreignId('plan_id')
            ->constrained();

            $table->string('description');
            $table->unsignedBigInteger('units')->default(0);
            $table->unsignedBigInteger('average_units')->default(0);
            $table->decimal('rate', 12,4)->default(0);
            $table->decimal('amount', 12,4)->default(0);
            $table->date('period_start');
            $table->date('period_end');
            
            $table->timestamps();

            $table->index([
                'invoice_id',
                'period_start',
                'period_end'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
