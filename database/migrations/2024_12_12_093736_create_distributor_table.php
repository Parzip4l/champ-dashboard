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
        Schema::create('distributor', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama distributor
            $table->string('email')->nullable(); // Email distributor (boleh kosong)
            $table->string('phone')->nullable(); // Nomor HP distributor (boleh kosong)
            $table->string('city'); // Kota/Kabupaten
            $table->string('province'); // Provinsi
            $table->text('address_details'); // Detail alamat
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor');
    }
};
