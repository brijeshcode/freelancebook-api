<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop old inconsistent currency fields
            $table->dropColumn(['budget_currency', 'payment_currency']);

            // Single currency for the project (budget + payments use same currency)
            $table->foreignId('currency_id')->nullable()->after('budget')->constrained('currencies')->nullOnDelete();

            // Snapshot of rate at time of project creation (copied from currency_rates)
            $table->decimal('exchange_rate', 15, 6)->nullable()->after('currency_id');
            $table->enum('calculation_type', ['multiply', 'divide'])->default('multiply')->after('exchange_rate');

            // Budget in base currency
            $table->decimal('budget_base_currency', 10, 2)->nullable()->after('calculation_type');

            // Running total of payments in base currency (mirrors total_paid but converted)
            $table->decimal('total_paid_base_currency', 10, 2)->default(0)->after('total_paid');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn([
                'currency_id',
                'exchange_rate',
                'calculation_type',
                'budget_base_currency',
                'total_paid_base_currency',
            ]);
            $table->string('budget_currency', 3)->default('USD');
            $table->string('payment_currency', 3)->default('USD');
        });
    }
};
