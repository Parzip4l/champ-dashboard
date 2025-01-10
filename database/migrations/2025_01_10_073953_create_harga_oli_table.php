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
        Schema::create('setting_oli', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_oli'); // Jenis oli
            $table->integer('harga'); // Harga oli
            $table->string('updated_by'); // Diupdate oleh (ID pengguna)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_oli');
    }
};
