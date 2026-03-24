<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();

            // The human-readable forex rate (always a "nice" number)
            // e.g. USD→INR: rate=83.50, LBP→USD: rate=89500
            $table->decimal('rate', 15, 6);

            // How to apply this rate to get base_currency amount:
            //   multiply → base_amount = amount * rate  (e.g. USD invoice, base INR: 100 * 83.50 = 8350)
            //   divide   → base_amount = amount / rate  (e.g. LBP invoice, base USD: 1,000,000 / 89500 = 11.17)
            $table->enum('calculation_type', ['multiply', 'divide'])->default('multiply');

            $table->boolean('is_active')->default(true); // only one active rate per currency at a time (enforced in app)

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['currency_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
