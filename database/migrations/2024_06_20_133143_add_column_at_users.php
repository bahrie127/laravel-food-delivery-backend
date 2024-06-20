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
        Schema::table('users', function (Blueprint $table) {
            //phone
            $table->string('phone')->nullable();
            //address
            $table->string('address')->nullable();
            //roles
            $table->string('roles')->default('user');
            //license_plate
            $table->string('license_plate')->nullable();
            //restaurant_name
            $table->string('restaurant_name')->nullable();
            //restaurant_address
            $table->string('restaurant_address')->nullable();
            //photo
            $table->string('photo')->nullable();
            //latlong
            $table->string('latlong')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->dropColumn('address');
            $table->dropColumn('roles');
            $table->dropColumn('license_plate');
            $table->dropColumn('restaurant_name');
            $table->dropColumn('restaurant_address');
            $table->dropColumn('photo');
            $table->dropColumn('latlong');
        });
    }
};
