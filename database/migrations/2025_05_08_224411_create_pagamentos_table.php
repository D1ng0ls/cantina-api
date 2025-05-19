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
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->decimal('valor_total', 10, 2);
            $table->enum('status', ['pendente', 'em_processamento', 'pago', 'recusado', 'cancelado', 'estornado'])->default('pendente');
            $table->enum('forma_pagamento', ['pix', 'debito', 'credito'])->default('pix');
            $table->foreignId('pedido_id')->constrained('users')->onDelete('cascade');;
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
