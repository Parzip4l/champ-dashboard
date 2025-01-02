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
        Schema::create('penetrasi', function (Blueprint $table) {
            $table->id();
            $table->string('batch');
            $table->string('product');
            $table->string('p_process');
            $table->string('k_process');
            $table->string('k_fng');
            $table->string('p_fng');
            $table->string('checker');
            $table->string('production_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penetrasi');
    }
};
