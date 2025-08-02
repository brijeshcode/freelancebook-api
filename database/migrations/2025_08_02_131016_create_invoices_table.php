<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('freelancer_id')->constrained('users')->onDelete('cascade');
            
            // Invoice Details
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('paid');
            
            // Currency & Financial
            $table->string('currency', 3); // ISO currency code (USD, EUR, etc.)
            $table->decimal('exchange_rate', 10, 6)->default(1.000000); // Rate to freelancer's base currency
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount_base_currency', 15, 2)->default(0.00); // Converted amount
            
            // Tax Information
            $table->decimal('tax_rate', 5, 2)->default(0.00); // Percentage
            $table->string('tax_label')->nullable(); // "VAT", "GST", etc.
            
            // Metadata
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['client_id', 'invoice_date']);
            $table->index(['freelancer_id', 'status']);
            $table->index('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};