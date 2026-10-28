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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('delivery_fee', 15, 2)->default(0)->after('tax_total');
            // Un devis ne peut donner qu'une seule commande.
            $table->unique('quote_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('discount', 15, 2)->default(0)->after('unit_price');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('discount');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['discount', 'discount_percent', 'tax_rate']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['quote_id']);
            $table->dropColumn('delivery_fee');
        });
    }
};
