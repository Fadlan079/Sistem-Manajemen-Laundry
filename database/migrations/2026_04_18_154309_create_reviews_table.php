<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('rating')->unsigned()->between(1, 5);
            $table->text('comment')->nullable();
            $table->timestamps();

            // One review per order
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
