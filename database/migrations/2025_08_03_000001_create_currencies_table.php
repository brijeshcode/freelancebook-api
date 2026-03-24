<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();       // ISO 4217: USD, EUR, LBP, INR
            $table->string('name');                     // US Dollar, Lebanese Pound
            $table->string('symbol', 10);               // $, €, ل.ل, ₹
            $table->unsignedTinyInteger('decimal_places')->default(2); // JPY=0, KWD=3, most=2
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
