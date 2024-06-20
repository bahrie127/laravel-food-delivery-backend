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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            //name
            $table->string('name');
            //description nullable
            $table->text('description')->nullable();
            //price
            $table->integer('price');
            //stock
            $table->integer('stock');
            //is_available
            $table->boolean('is_available')->default(true);
            //is_favorite
            $table->boolean('is_favorite')->default(false);
            //image
            $table->string('image')->nullable();
            //user_id
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
