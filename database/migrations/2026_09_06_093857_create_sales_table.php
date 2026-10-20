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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->decimal('quantity_sold', 10, 3)->nullable();
            $table->unsignedInteger('pieces_sold')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->decimal('buying_price_override', 10, 2)->nullable();
            $table->decimal('selling_price_per_unit', 10, 2);
            $table->decimal('buying_price_per_unit', 10, 2);
            $table->decimal('quantity', 10, 3);
            $table->decimal('revenue', 12, 2);
            $table->decimal('cogs', 12, 2);
            $table->decimal('profit', 12, 2);
            $table->timestamps();

            $table->index(['item_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
