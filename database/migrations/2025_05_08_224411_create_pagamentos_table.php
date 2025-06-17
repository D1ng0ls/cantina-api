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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_value', 10, 2);
            $table->enum('status', ['pending', 'processing', 'paid', 'declined', 'canceled', 'refunded'])->default('pending');
            $table->enum('method', ['pix', 'debit', 'credit', 'cash'])->default('pix');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');;
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
