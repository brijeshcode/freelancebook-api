<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Replace currency string with FK
            $table->dropColumn('currency');

            $table->foreignId('currency_id')->nullable()->after('exchange_rate')->constrained('currencies')->nullOnDelete();

            // Add calculation_type alongside existing exchange_rate and amount_base_currency
            $table->enum('calculation_type', ['multiply', 'divide'])->default('multiply')->after('currency_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'calculation_type']);
            $table->string('currency', 3);
        });
    }
};
