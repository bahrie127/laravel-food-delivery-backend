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
            //order_id
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            //payment type
            $table->string('payment_type');
            //payment provider
            $table->string('payment_provider')->nullable();
            //amount
            $table->integer('amount');
            //status
            $table->string('status')->default('pending');
            //xendit id
            $table->string('xendit_id')->nullable();
            $table->timestamps();
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
