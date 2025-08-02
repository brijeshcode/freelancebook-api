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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to clients table
            $table->foreignId('client_id')
                  ->constrained('clients')
                  ->onDelete('cascade');
            
            // Foreign key to freelancers (users) table
            $table->foreignId('freelancer_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Basic project information
            $table->string('name');
            $table->decimal('budget', 10, 2)->nullable(); // Allow null for projects without set budget
            $table->string('budget_currency', 3)->default('USD'); // ISO currency code
            $table->text('notes')->nullable(); // Optional notes
            
            // Optional detailed description
            $table->longText('project_details')->nullable();
            
            // Project dates
            $table->date('start_date')->nullable(); // When project actually starts
            $table->date('end_date')->nullable(); // When project actually ends
            $table->date('deadline')->nullable(); // Target completion date
            
            // Time tracking
            $table->decimal('estimated_hours', 8, 2)->nullable(); // Expected time investment
            $table->decimal('actual_hours', 8, 2)->default(0); // Actual time spent
            
            // Payment tracking
            $table->decimal('total_paid', 10, 2)->default(0); // Running total of payments received
            $table->string('payment_currency', 3)->default('USD'); // Currency for payments
            
            // Project status
            $table->enum('status', [
                'prospective',  // Expecting but not confirmed
                'planned',      // Confirmed but not started
                'active',       // Currently working on
                'completed',    // Successfully finished
                'on_hold',      // Temporarily paused
                'cancelled'     // Cancelled/abandoned
            ])->default('planned');
            
            // Tracking fields
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index(['client_id', 'freelancer_id']);
            $table->index(['status']);
            $table->index(['deadline']);
            $table->index(['start_date']);
            $table->index(['end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};