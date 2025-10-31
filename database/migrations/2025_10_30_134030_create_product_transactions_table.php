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
        Schema::create('product_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name')->nullable(); // Store name at time of transaction
            $table->string('product_sku')->nullable();  // Store SKU at time of
            $table->tinyInteger('transaction_type'); // 1 = Purchase, 2 = Sale, etc.
            $table->date('transaction_date')->default(now());
            $table->decimal('quantity', 18, 8);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->decimal('total_cost', 16, 4);
            $table->decimal('quantity_before', 18, 8)->nullable();
            $table->decimal('quantity_after', 18, 8)->nullable();
            $table->decimal('wac_before', 14, 4)->nullable();
            $table->decimal('wac_after', 14, 4)->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_transactions');
    }
};
