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
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('cart','dibuat','antri','diterima','pending','dijemput','diproses','selesai','diantar','dibatalkan') NOT NULL DEFAULT 'dibuat'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('cart','dibuat','diterima','pending','dijemput','diproses','selesai','diantar','dibatalkan') NOT NULL DEFAULT 'dibuat'");
        }
    }
};
