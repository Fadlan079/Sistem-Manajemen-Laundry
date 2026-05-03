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
        // Orders Table: Remove 'menunggu'
        DB::table('orders')->where('status', 'menunggu')->update(['status' => 'dibuat']);
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('cart','dibuat','antri','diterima','dijemput','diproses','selesai','diantar','dibatalkan') NOT NULL DEFAULT 'dibuat'");

        // Payments Table: 'menunggu' -> 'pending'
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','menunggu','paid') NOT NULL DEFAULT 'pending'");
        DB::table('payments')->where('status', 'menunggu')->update(['status' => 'pending']);
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','paid') NOT NULL DEFAULT 'pending'");

        // Deliveries Table: 'menunggu' -> 'pending'
        DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('pending','menunggu','dijemput','diantar','selesai') NOT NULL DEFAULT 'pending'");
        DB::table('deliveries')->where('status', 'menunggu')->update(['status' => 'pending']);
        DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('pending','dijemput','diantar','selesai') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Deliveries Table: 'pending' -> 'menunggu'
        DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('pending','menunggu','dijemput','diantar','selesai') NOT NULL DEFAULT 'menunggu'");
        DB::table('deliveries')->where('status', 'pending')->update(['status' => 'menunggu']);
        DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('menunggu','dijemput','diantar','selesai') NOT NULL DEFAULT 'menunggu'");

        // Payments Table: 'pending' -> 'menunggu'
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','menunggu','paid') NOT NULL DEFAULT 'menunggu'");
        DB::table('payments')->where('status', 'pending')->update(['status' => 'menunggu']);
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('menunggu','paid') NOT NULL DEFAULT 'menunggu'");

        // Orders Table: Add 'menunggu' back
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('cart','dibuat','antri','diterima','menunggu','dijemput','diproses','selesai','diantar','dibatalkan') NOT NULL DEFAULT 'dibuat'");
    }
};
