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
        Schema::create('online_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->string('type', 20)->default('quote');
            $table->string('status', 20)->default('new')->index();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('phone_normalized', 30)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('address')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('consent_at');
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('closed_reason')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('online_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('sku_snapshot', 50)->nullable();
            $table->string('unit_snapshot', 20)->nullable();
            $table->decimal('quantity', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_request_items');
        Schema::dropIfExists('online_requests');
    }
};
