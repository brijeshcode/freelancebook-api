<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
            
            // Item Details (stored separately to preserve invoice integrity even if service changes)
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            
            // Service Period (for recurring services)
            $table->date('service_period_start')->nullable();
            $table->date('service_period_end')->nullable();
            
            // Metadata
            $table->boolean('is_recurring')->default(false);
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['invoice_id', 'sort_order']);
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};