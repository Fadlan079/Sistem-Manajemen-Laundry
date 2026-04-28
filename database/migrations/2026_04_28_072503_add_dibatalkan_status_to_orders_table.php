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
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('cart', 'pending', 'dijemput', 'diproses', 'selesai', 'diantar', 'dibatalkan') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('cart', 'pending', 'dijemput', 'diproses', 'selesai', 'diantar') NOT NULL DEFAULT 'pending'");
    }
};
