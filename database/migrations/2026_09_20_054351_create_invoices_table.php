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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')
            ->constrained()
            ->cascadeOnDelete();

            $table->foreignId('customer_id')
            ->constrained()
            ->cascadeOnDelete();

            $table->foreignId('subscription_id')
            ->constrained();

            $table->date('billing_start');
            $table->date('billing_end');
            $table->decimal('base_amount', 12,2)->default(0);
            $table->decimal('average_amount', 12,2)->default(0);
            $table->decimal('total_amount', 12,2)->default(0);

            $table->string('status')->default('generated');

            $table->timestamps();

            $table->index([
                'customer_id',
                'billing_start',
                'billing_end'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
