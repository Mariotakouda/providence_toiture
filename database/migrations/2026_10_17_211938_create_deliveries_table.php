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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number', 30)->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('planned'); // planned, in_transit, delivered, cancelled
            $table->dateTime('planned_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('receiver_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
