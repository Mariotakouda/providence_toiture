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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone_normalized', 30)->nullable()->after('phone')->index();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('phone_normalized', 30)->nullable()->after('phone')->index();
            $table->string('city', 100)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['phone_normalized']);
            $table->dropColumn('phone_normalized');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['phone_normalized']);
            $table->dropColumn(['phone_normalized', 'city']);
        });
    }
};
