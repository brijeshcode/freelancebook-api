<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_id')->constrained('users')->onDelete('cascade');
            
            // Base Currency & Financial Settings
            $table->string('base_currency', 3)->default('USD');
            $table->integer('invoice_due_days')->default(30); // Default payment terms
            
            // Invoice Settings
            $table->string('invoice_prefix', 10)->default('INV');
            $table->integer('next_invoice_number')->default(1);
            $table->year('invoice_year')->default(date('Y')); // Track current year for numbering
            $table->text('invoice_footer')->nullable(); // Standard footer text
            $table->json('invoice_branding')->nullable(); // Logo, colors, etc.
            
            // Tax Settings
            $table->decimal('default_tax_rate', 5, 2)->default(0.00);
            $table->string('default_tax_label')->nullable();
            $table->string('tax_number')->nullable(); // VAT number, etc.
            
            // Business Information
            $table->text('business_address')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_email')->nullable();
            $table->string('business_website')->nullable();
            
            // Notification Settings
            $table->json('notification_preferences')->nullable(); // Email, in-app settings
            
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraint
            $table->unique('freelancer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_settings');
    }
};