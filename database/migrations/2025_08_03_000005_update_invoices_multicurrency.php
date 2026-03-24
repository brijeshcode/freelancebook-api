<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Replace currency string with FK
            $table->dropColumn('currency');

            $table->foreignId('currency_id')->nullable()->after('exchange_rate')->constrained('currencies')->nullOnDelete();

            // Add calculation_type alongside existing exchange_rate
            $table->enum('calculation_type', ['multiply', 'divide'])->default('multiply')->after('currency_id');

            // Subtotal and tax in base currency (total_amount_base_currency already exists)
            $table->decimal('subtotal_base_currency', 15, 2)->default(0)->after('subtotal');
            $table->decimal('tax_amount_base_currency', 15, 2)->default(0)->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn([
                'currency_id',
                'calculation_type',
                'subtotal_base_currency',
                'tax_amount_base_currency',
            ]);
            $table->string('currency', 3);
        });
    }
};
