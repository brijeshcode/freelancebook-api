<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('freelancer_id')->constrained('users')->onDelete('cascade');
            
            // Payment Details
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3); // Original payment currency
            $table->decimal('exchange_rate', 10, 6)->default(1.000000); // Rate to freelancer's base currency
            $table->decimal('amount_base_currency', 15, 2); // Converted amount
            
            // Transaction Info
            $table->date('payment_date');
            $table->enum('payment_method', [
                'bank_transfer', 
                'paypal', 
                'stripe', 
                'western_union',
                'cash', 
                'check', 
                'crypto', 
                'other'
            ])->default('bank_transfer');
            
            $table->string('transaction_reference')->nullable(); // Bank ref, PayPal ID, etc.
            $table->text('notes')->nullable();
            
            // Status & Verification
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            
            // File Attachments (payment receipts only)
            $table->json('receipt_attachments')->nullable(); // Store receipt file paths/URLs
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['client_id', 'payment_date']);
            $table->index(['freelancer_id', 'status']);
            $table->index('payment_date');
            $table->index('transaction_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};