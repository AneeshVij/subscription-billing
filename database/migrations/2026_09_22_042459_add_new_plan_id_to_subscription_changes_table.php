<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_changes', function (Blueprint $table) {
            $table->foreignId('new_plan_id')
                ->after('old_plan_id')
                ->constrained('plans');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_changes', function (Blueprint $table) {
            $table->dropForeign(['new_plan_id']);
            $table->dropColumn('new_plan_id');
        });
    }
};