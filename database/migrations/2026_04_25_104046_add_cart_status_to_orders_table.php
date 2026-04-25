<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'cart' to orders status enum
        // Using DB::statement because change() on enum is tricky in some Laravel versions/DBs
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('cart', 'pending', 'dijemput', 'diproses', 'selesai', 'diantar') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'dijemput', 'diproses', 'selesai', 'diantar') NOT NULL DEFAULT 'pending'");
    }
};
