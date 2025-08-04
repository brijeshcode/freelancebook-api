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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); // freelancer who created this
            
            // Basic Service Information
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2); // 99,999,999.99 (base amount before tax calculations)
            $table->string('currency', 3)->default('INR'); // ISO 4217 currency codes (USD, EUR, INR, etc.)
            
            // Tax Configuration
            $table->boolean('has_tax')->default(false); // whether tax is applied to this service
            $table->string('tax_name')->nullable(); // 'GST', 'VAT', 'Sales Tax', etc.
            $table->decimal('tax_rate', 5, 2)->default(0.00); // 18.00 for 18% GST, supports up to 999.99%
            $table->enum('tax_type', ['inclusive', 'exclusive'])->default('exclusive'); // tax calculation method
            
            // Billing Configuration
            $table->enum('frequency', [
                'one-time', 
                'weekly', 
                'monthly', 
                'quarterly',
                'half-yearly', 
                'yearly'
            ])->default('one-time');
            
            $table->date('start_date');
            $table->date('next_billing_date')->nullable(); // null for one-time services
            $table->date('end_date')->nullable(); // for finite recurring services
            
            // Status Management
            $table->enum('status', [
                'draft',        // created but not active
                'active',       // ready for billing
                'paused',       // temporarily stopped
                'completed',    // finished (one-time) or stopped (recurring)
                'cancelled',    // cancelled service
                'pending_approval' // for future client approval workflow
            ])->default('draft');
            
            $table->boolean('is_active')->default(true);
            
            // Invoice Tracking
            // $table->foreignId('last_billed_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('last_billed_at')->nullable();
            $table->integer('billing_count')->default(0); // how many times this service has been billed
            
            // Additional Fields
            $table->json('tags')->nullable(); // ['hosting', 'maintenance', 'design']
            $table->text('notes')->nullable(); // internal notes
            $table->json('metadata')->nullable(); // flexible field for future extensions
            
            // Audit Fields
            $table->timestamps();
            $table->softDeletes(); // important for financial records
            
            // Indexes for performance
            $table->index(['client_id', 'status']);
            $table->index(['next_billing_date', 'is_active']);
            $table->index(['frequency', 'status']);
            $table->index(['currency', 'client_id']); // for currency-based filtering
            $table->index(['has_tax', 'tax_type']); // for tax-based filtering
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};