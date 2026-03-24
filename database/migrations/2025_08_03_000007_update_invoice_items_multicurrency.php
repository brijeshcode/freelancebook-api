<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            // Items inherit currency/rate from their invoice.
            // Store base currency prices so reporting works without joining invoices.
            $table->decimal('unit_price_base_currency', 15, 2)->nullable()->after('unit_price');
            $table->decimal('total_price_base_currency', 15, 2)->nullable()->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price_base_currency', 'total_price_base_currency']);
        });
    }
};
