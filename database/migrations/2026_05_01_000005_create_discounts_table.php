<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');  // e.g. "Diskon Senin - Cuci Lipat"
            $table->enum('type', ['harian', 'promo', 'member'])->default('harian');

            // null = applies to all services; set to specific service if limited
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('cascade');

            // For type='harian': which day of week (Monday, Tuesday, etc.)
            $table->string('day_of_week')->nullable();

            // Discount percentage (e.g. 20.00 = 20%)
            $table->decimal('percentage', 5, 2)->default(0);

            // For type='promo': date range validity
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
