<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('name'); // Company name or individual name
            $table->enum('type', ['individual', 'company'])->default('company');
            $table->string('contact_person')->nullable(); // For companies - main contact
            $table->string('client_code')->unique(); // Auto-generated unique code (e.g., CLI001)
            
            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            
            // Address Information
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            
            // Business Information
            $table->string('tax_number')->nullable(); // GST/VAT/Tax ID
            $table->text('notes')->nullable(); // Internal notes about client
            
            // Financial Summary (calculated fields for quick access)
            $table->decimal('total_billed', 15, 2)->default(0); // Total ever billed
            $table->decimal('total_received', 15, 2)->default(0); // Total payments received
            $table->decimal('current_balance', 15, 2)->default(0); // Outstanding amount
            
            // Status and Settings
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->json('billing_preferences')->nullable(); // Payment terms, preferred invoice format, etc.
            
            // Ownership
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Freelancer who owns this client
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['client_code']);
            $table->index(['email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};