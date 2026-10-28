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
    Schema::table('quotes', function (Blueprint $table) {
        $table->text('terms')->nullable()->after('notes');
        $table->timestamp('sent_at')->nullable()->after('terms');
        $table->timestamp('responded_at')->nullable()->after('sent_at');
    });

    Schema::table('quote_items', function (Blueprint $table) {
        $table->foreignId('product_variant_id')->nullable()->after('product_id')
            ->constrained('product_variants')->nullOnDelete();
        $table->string('unit_snapshot', 20)->nullable()->after('sku_snapshot');
        $table->decimal('discount_percent', 5, 2)->default(0)->after('discount');
    });

    // Préparé pour la conversion devis → commande (lot 2).
    Schema::table('order_items', function (Blueprint $table) {
        $table->foreignId('product_variant_id')->nullable()->after('product_id')
            ->constrained('product_variants')->nullOnDelete();
        $table->string('unit_snapshot', 20)->nullable()->after('sku_snapshot');
    });
}

public function down(): void
{
    Schema::table('order_items', function (Blueprint $table) {
        $table->dropConstrainedForeignId('product_variant_id');
        $table->dropColumn('unit_snapshot');
    });

    Schema::table('quote_items', function (Blueprint $table) {
        $table->dropConstrainedForeignId('product_variant_id');
        $table->dropColumn(['unit_snapshot', 'discount_percent']);
    });

    Schema::table('quotes', function (Blueprint $table) {
        $table->dropColumn(['terms', 'sent_at', 'responded_at']);
    });
}
};
