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
        Schema::table('orders', function (Blueprint $group) {
            $group->unsignedBigInteger('user_id')->nullable()->change();
            $group->string('customer_name')->nullable()->after('user_id');
            $group->string('customer_phone')->nullable()->after('customer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $group) {
            $group->unsignedBigInteger('user_id')->nullable(false)->change();
            $group->dropColumn(['customer_name', 'customer_phone']);
        });
    }
};
