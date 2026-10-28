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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('movement_type', 30); // purchase, return, production, sale, breakage, internal_use, adjustment_in, adjustment_out
            $table->decimal('quantity', 12, 2); // toujours positive, le sens est donné par movement_type
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->nullableMorphs('reference'); // devis, commande, livraison, etc.
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->index(['product_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
