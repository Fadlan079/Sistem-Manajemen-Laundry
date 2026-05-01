<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // FK to services — so we can join directly instead of parsing item_name
            $table->foreignId('service_id')->nullable()->after('order_id')
                  ->constrained('services')->onDelete('set null');

            // Change qty from integer to decimal to support kg weights (e.g. 2.5 kg)
            $table->decimal('qty', 8, 2)->default(0)->change();

            // Estimated weight option (only for kg-based services, before weighing)
            $table->enum('estimated_weight_option', [
                'kurang_dari_3',
                '3_sampai_5',
                '5_sampai_10',
                'lebih_dari_10',
            ])->nullable()->after('qty');

            // Actual weight set by operator after weighing (kg-based services)
            $table->decimal('actual_weight', 8, 2)->nullable()->after('estimated_weight_option');

            // Whether a discount was applied on this item
            $table->boolean('use_discount')->default(false)->after('price');

            // The discount amount that was deducted (in Rupiah)
            $table->decimal('discount_amount', 10, 2)->default(0)->after('use_discount');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn([
                'service_id',
                'estimated_weight_option',
                'actual_weight',
                'use_discount',
                'discount_amount',
            ]);
            $table->integer('qty')->default(0)->change();
        });
    }
};
