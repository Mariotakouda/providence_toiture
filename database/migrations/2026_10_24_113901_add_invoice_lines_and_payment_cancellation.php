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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('discount_total', 15, 2)->default(0)->after('subtotal');
            $table->decimal('delivery_fee', 15, 2)->default(0)->after('tax_total');
            $table->json('customer_snapshot')->nullable()->after('customer_id');
            $table->text('terms')->nullable()->after('balance_due');
            $table->text('notes')->nullable()->after('terms');
            $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable()->after('created_by');
            $table->timestamp('cancelled_at')->nullable()->after('issued_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable()->after('cancelled_by');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('notes');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable()->after('cancelled_by');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('sku_snapshot', 50)->nullable();
            $table->string('unit_snapshot', 20)->nullable();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancel_reason']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'discount_total',
                'delivery_fee',
                'customer_snapshot',
                'terms',
                'notes',
                'issued_at',
                'cancelled_at',
                'cancel_reason',
            ]);
        });
    }
};
