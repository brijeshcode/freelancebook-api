<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Replace currency string with FK
            $table->dropColumn('currency');

            $table->foreignId('currency_id')->nullable()->after('amount')->constrained('currencies')->nullOnDelete();

            // Snapshot of rate at time of service creation
            $table->decimal('exchange_rate', 15, 6)->nullable()->after('currency_id');
            $table->enum('calculation_type', ['multiply', 'divide'])->default('multiply')->after('exchange_rate');

            // Amount converted to freelancer's base currency
            $table->decimal('amount_base_currency', 12, 2)->nullable()->after('calculation_type');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'calculation_type', 'amount_base_currency']);
            $table->string('currency', 3)->default('INR');
        });
    }
};
