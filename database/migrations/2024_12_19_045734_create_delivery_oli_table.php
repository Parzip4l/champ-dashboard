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
        Schema::create('delivery_oli', function (Blueprint $table) {
            $table->id();
            $table->string('tanggal');
            $table->string('pengirim');
            $table->string('jenis_oli');
            $table->string('jumlah');
            $table->string('receive_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_oli');
    }
};
